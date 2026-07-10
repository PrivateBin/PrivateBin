<?php declare(strict_types=1);
/**
 * PrivateBin Installation Script
 *
 * Guides through initial setup: database configuration, admin account creation,
 * and generates the conf.php configuration file. Can be re-run to reconfigure.
 *
 * IMPORTANT: Delete this file after installation is complete!
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 */

$configFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cfg' . DIRECTORY_SEPARATOR . 'conf.php';

// load existing config if present (for pre-filling the form on re-run)
$existing = array();
$isReconfigure = false;
if (is_readable($configFile)) {
    $existing = parse_ini_file($configFile, true);
    if (!$existing) {
        $existing = array();
    }
    $isReconfigure = isset($existing['main']);
}

// helper to read existing config value
function cfgVal(array $existing, string $section, string $key, $default = '') {
    return $existing[$section][$key] ?? $default;
}

$error   = '';
$success = false;
$migrateResult = '';
$step    = isset($_POST['step']) ? (int) $_POST['step'] : 1;

// process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2:
            // test database connection and generate config
            $dbType    = $_POST['db_type'] ?? 'sqlite';
            $dbHost    = trim($_POST['db_host'] ?? '');
            $dbPort    = trim($_POST['db_port'] ?? '');
            $dbName    = trim($_POST['db_name'] ?? '');
            $dbUser    = trim($_POST['db_user'] ?? '');
            $dbPass    = $_POST['db_pass'] ?? '';
            $tblPrefix = trim($_POST['tbl_prefix'] ?? '');

            $siteName  = trim($_POST['site_name'] ?? 'PrivateBin');
            $authEnabled       = !empty($_POST['auth_enabled']);
            $requireCreate     = !empty($_POST['require_login_create']);
            $requireRead       = !empty($_POST['require_login_read']);
            $allowRegistration = !empty($_POST['allow_registration']);
            $requireApproval   = !empty($_POST['require_approval']);
            $adminEmail        = trim($_POST['admin_email'] ?? '');

            $adminUser  = trim($_POST['admin_username'] ?? '');
            $adminPass  = $_POST['admin_password'] ?? '';
            $adminPass2 = $_POST['admin_password2'] ?? '';

            $migrateData = !empty($_POST['migrate_data']);

            // URL shortener settings
            $shortenerType    = $_POST['shortener_type'] ?? 'none';
            $shortenerDirect  = trim($_POST['shortener_direct'] ?? '');
            $yourlsApiUrl     = trim($_POST['yourls_apiurl'] ?? '');
            $yourlsSignature  = trim($_POST['yourls_signature'] ?? '');
            $shlinkApiUrl     = trim($_POST['shlink_apiurl'] ?? '');
            $shlinkApiKey     = trim($_POST['shlink_apikey'] ?? '');
            $urlshortenerwarning = !empty($_POST['urlshortenerwarning']);

            // build DSN
            $dsn = '';
            switch ($dbType) {
                case 'sqlite':
                    $dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
                    if (!is_dir($dataDir)) {
                        mkdir($dataDir, 0700, true);
                    }
                    $dsn = 'sqlite:' . $dataDir . DIRECTORY_SEPARATOR . 'db.sq3';
                    $dbUser = null;
                    $dbPass = null;
                    break;
                case 'mysql':
                    $port = $dbPort ?: '3306';
                    $dsn  = "mysql:host={$dbHost};port={$port};dbname={$dbName};charset=UTF8";
                    break;
                case 'pgsql':
                    $port = $dbPort ?: '5432';
                    $dsn  = "pgsql:host={$dbHost};port={$port};dbname={$dbName}";
                    break;
            }

            // test connection
            try {
                $opts = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                );
                if ($dbType === 'mysql' && defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                    $opts[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET SESSION sql_mode='ANSI_QUOTES'";
                }
                $pdo = new PDO($dsn, $dbUser, $dbPass, $opts);
            } catch (PDOException $e) {
                $error = 'Database connection failed: ' . $e->getMessage();
                $step  = 1;
                break;
            }

            // validate admin credentials if auth is enabled and this is first install or user provided them
            if ($authEnabled && !empty($adminUser)) {
                if (strlen($adminUser) < 3) {
                    $error = 'Admin username must be at least 3 characters.';
                    $step  = 1;
                    break;
                }
                if (!preg_match('/^[a-zA-Z0-9_\-\.]{3,64}$/', $adminUser)) {
                    $error = 'Admin username can only contain letters, numbers, underscore, dash, and dot.';
                    $step  = 1;
                    break;
                }
                if (strlen($adminPass) < 8) {
                    $error = 'Admin password must be at least 8 characters.';
                    $step  = 1;
                    break;
                }
                if ($adminPass !== $adminPass2) {
                    $error = 'Passwords do not match.';
                    $step  = 1;
                    break;
                }
            }

            // read additional settings from form
            $discussion        = !empty($_POST['discussion']);
            $password          = !empty($_POST['password_feature']);
            $fileupload        = !empty($_POST['fileupload']);
            $burnafterreading  = !empty($_POST['burnafterreading']);
            $qrcode            = !empty($_POST['qrcode']);
            $emailFeature      = !empty($_POST['email_feature']);
            $languageselection = !empty($_POST['languageselection']);
            $defaultformatter  = $_POST['defaultformatter'] ?? 'plaintext';
            $template          = $_POST['template'] ?? 'bootstrap5';
            $sizelimit         = (int) ($_POST['sizelimit'] ?? 10000000);
            $ratelimit         = (int) ($_POST['ratelimit'] ?? 10);
            $defaultexpire     = $_POST['defaultexpire'] ?? '1week';
            $compression       = $_POST['compression'] ?? 'zlib';

            // determine urlshortener value based on type selection
            $urlshortener = '';
            switch ($shortenerType) {
                case 'yourls':
                    $urlshortener = '${basepath}?shortenviayourls&link=';
                    break;
                case 'shlink':
                    $urlshortener = '${basepath}?shortenviashlink&link=';
                    break;
                case 'direct':
                    $urlshortener = $shortenerDirect;
                    break;
            }

            // load old data for migration if requested
            $oldStore = null;
            if ($migrateData && $isReconfigure) {
                try {
                    define('PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
                    define('PUBLIC_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
                    require PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
                    $oldConfig = new PrivateBin\Configuration();
                    $oldModel  = new PrivateBin\Model($oldConfig);
                    $oldStore  = $oldModel->getStore();
                } catch (Exception $e) {
                    // old config may be broken, skip migration
                    $migrateResult = 'Warning: Could not load old configuration for migration: ' . $e->getMessage();
                    $oldStore = null;
                }
            }

            // generate configuration file
            $conf = ";<?php http_response_code(403); /*\n";
            $conf .= "; config file for PrivateBin\n";
            $conf .= "; generated by install.php on " . date('Y-m-d H:i:s') . "\n\n";

            $conf .= "[main]\n";
            $conf .= "name = \"" . addslashes($siteName) . "\"\n";
            $conf .= "discussion = " . ($discussion ? 'true' : 'false') . "\n";
            $conf .= "opendiscussion = false\n";
            $conf .= "password = " . ($password ? 'true' : 'false') . "\n";
            $conf .= "fileupload = " . ($fileupload ? 'true' : 'false') . "\n";
            $conf .= "burnafterreadingselected = " . ($burnafterreading ? 'true' : 'false') . "\n";
            $conf .= "defaultformatter = \"" . addslashes($defaultformatter) . "\"\n";
            $conf .= "sizelimit = $sizelimit\n";
            $conf .= "template = \"" . addslashes($template) . "\"\n";
            $conf .= "languageselection = " . ($languageselection ? 'true' : 'false') . "\n";
            $conf .= "qrcode = " . ($qrcode ? 'true' : 'false') . "\n";
            $conf .= "email = " . ($emailFeature ? 'true' : 'false') . "\n";
            $conf .= "compression = \"" . addslashes($compression) . "\"\n";
            if ($urlshortener) {
                $conf .= "urlshortener = \"" . addslashes($urlshortener) . "\"\n";
            }
            $conf .= "urlshortenerwarning = " . ($urlshortenerwarning ? 'true' : 'false') . "\n\n";

            $conf .= "[auth]\n";
            $conf .= "enabled = " . ($authEnabled ? 'true' : 'false') . "\n";
            $conf .= "require_login_to_create = " . ($requireCreate ? 'true' : 'false') . "\n";
            $conf .= "require_login_to_read = " . ($requireRead ? 'true' : 'false') . "\n";
            $conf .= "allow_registration = " . ($allowRegistration ? 'true' : 'false') . "\n";
            $conf .= "require_approval = " . ($requireApproval ? 'true' : 'false') . "\n";
            if ($adminEmail) {
                $conf .= "admin_email = \"" . addslashes($adminEmail) . "\"\n";
            }
            $conf .= "session_timeout = 3600\n\n";

            $conf .= "[expire]\n";
            $conf .= "default = \"" . addslashes($defaultexpire) . "\"\n\n";

            $conf .= "[expire_options]\n";
            $conf .= "5min = 300\n";
            $conf .= "10min = 600\n";
            $conf .= "1hour = 3600\n";
            $conf .= "1day = 86400\n";
            $conf .= "1week = 604800\n";
            $conf .= "1month = 2592000\n";
            $conf .= "1year = 31536000\n";
            $conf .= "never = 0\n\n";

            $conf .= "[formatter_options]\n";
            $conf .= "plaintext = \"Plain Text\"\n";
            $conf .= "syntaxhighlighting = \"Source Code\"\n";
            $conf .= "markdown = \"Markdown\"\n\n";

            $conf .= "[traffic]\n";
            $conf .= "limit = $ratelimit\n\n";

            $conf .= "[purge]\n";
            $conf .= "limit = 300\n";
            $conf .= "batchsize = 10\n\n";

            $conf .= "[model]\n";
            $conf .= "class = \"Database\"\n\n";

            $conf .= "[model_options]\n";
            $conf .= "dsn = \"" . addslashes($dsn) . "\"\n";
            $conf .= "tbl = \"" . addslashes($tblPrefix) . "\"\n";
            $conf .= "usr = \"" . addslashes($dbUser ?? '') . "\"\n";
            $conf .= "pwd = \"" . addslashes($dbPass ?? '') . "\"\n";
            $conf .= "opt[12] = true\n\n";

            if ($shortenerType === 'yourls' && ($yourlsApiUrl || $yourlsSignature)) {
                $conf .= "[yourls]\n";
                $conf .= "apiurl = \"" . addslashes($yourlsApiUrl) . "\"\n";
                $conf .= "signature = \"" . addslashes($yourlsSignature) . "\"\n\n";
            }

            if ($shortenerType === 'shlink' && ($shlinkApiUrl || $shlinkApiKey)) {
                $conf .= "[shlink]\n";
                $conf .= "apiurl = \"" . addslashes($shlinkApiUrl) . "\"\n";
                $conf .= "apikey = \"" . addslashes($shlinkApiKey) . "\"\n\n";
            }

            $conf .= "; */ ?>\n";

            // write configuration
            $cfgDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'cfg';
            if (!is_dir($cfgDir)) {
                mkdir($cfgDir, 0750, true);
            }
            if (!is_writable($cfgDir)) {
                $error = 'Cannot write to cfg/ directory. Please check permissions.';
                $step  = 1;
                break;
            }

            if (file_put_contents($configFile, $conf) === false) {
                $error = 'Failed to write configuration file.';
                $step  = 1;
                break;
            }

            // bootstrap PrivateBin to create the database tables and admin user
            if (!defined('PATH')) {
                define('PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
                define('PUBLIC_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
                require PATH . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
            }

            try {
                $configuration = new PrivateBin\Configuration();
                $model         = new PrivateBin\Model($configuration);
                $store         = $model->getStore();

                // create admin user if auth is enabled and credentials provided
                if ($authEnabled && !empty($adminUser) && !empty($adminPass)) {
                    $auth = new PrivateBin\Auth\Auth($configuration, $store);
                    // only create if user doesn't already exist
                    if (!method_exists($store, 'readUser') || !$store->readUser($adminUser)) {
                        $result = $auth->createUser($adminUser, $adminPass, PrivateBin\Auth\User::ROLE_ADMIN);
                        if (!$result['success']) {
                            $error = 'Configuration saved but admin creation failed: ' . $result['message'];
                            $step  = 1;
                            break;
                        }
                    }
                }

                // migrate data from old store to new store
                if ($migrateData && $oldStore !== null && $oldStore !== $store) {
                    $migrateResult = migrateData($oldStore, $store);
                }
            } catch (Exception $e) {
                $error = 'Configuration saved but database initialization failed: ' . $e->getMessage();
                $step  = 1;
                break;
            }

            $success = true;
            break;
    }
}

/**
 * Migrate data (users, pastes) from old store to new store
 */
function migrateData($oldStore, $newStore): string {
    $results = array();

    // migrate users
    if (method_exists($oldStore, 'listUsers') && method_exists($newStore, 'createUser')) {
        $users = $oldStore->listUsers();
        $userCount = 0;
        foreach ($users as $userData) {
            $username = $userData['username'] ?? '';
            if (!$username) continue;
            // check if user already exists in new store
            $existingUser = $newStore->readUser($username);
            if (!$existingUser) {
                try {
                    $newStore->createUser($username, $userData);
                    $userCount++;
                } catch (Exception $e) {
                    // skip duplicates
                }
            }
        }
        $results[] = "Migrated $userCount user(s).";
    }

    if (empty($results)) {
        return 'No data to migrate (paste migration is not supported as pastes are encrypted per-instance).';
    }

    return implode(' ', $results);
}

// detect current shortener type from existing config
$currentShortenerType = 'none';
if ($isReconfigure) {
    $currentUrlShortener = cfgVal($existing, 'main', 'urlshortener', '');
    if (strpos($currentUrlShortener, 'shortenviayourls') !== false) {
        $currentShortenerType = 'yourls';
    } elseif (strpos($currentUrlShortener, 'shortenviashlink') !== false) {
        $currentShortenerType = 'shlink';
    } elseif (!empty($currentUrlShortener)) {
        $currentShortenerType = 'direct';
    }
}

// detect current db type from existing config
$currentDbType = 'sqlite';
if ($isReconfigure) {
    $currentDsn = cfgVal($existing, 'model_options', 'dsn', '');
    if (strpos($currentDsn, 'mysql:') === 0) {
        $currentDbType = 'mysql';
    } elseif (strpos($currentDsn, 'pgsql:') === 0) {
        $currentDbType = 'pgsql';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PrivateBin Installation</title>
    <link rel="stylesheet" href="../css/bootstrap5/bootstrap-5.3.8.css">
    <link rel="stylesheet" href="../css/bootstrap5/privatebin.css">
    <link rel="stylesheet" href="../css/common.css">
    <style>
        .install-container { max-width: 640px; margin: 40px auto; padding: 0 1rem; }
        .form-section { background: var(--bs-body-bg, white); border-radius: 8px; padding: 2rem; margin-bottom: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .form-section h5 { margin-bottom: 1rem; border-bottom: 1px solid var(--bs-border-color, #eee); padding-bottom: 0.5rem; }
    </style>
</head>
<body>
<div class="install-container">
    <h2 class="mb-4 text-center">🔒 PrivateBin <?php echo $isReconfigure ? 'Reconfiguration' : 'Installation'; ?></h2>

<?php if ($isReconfigure && $step === 1 && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
    <div class="alert alert-warning">
        <strong>⚠️ Existing configuration detected.</strong>
        The form below is pre-filled with your current settings. Changes will overwrite <code>cfg/conf.php</code>.
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="form-section">
        <div class="alert alert-success">
            <h5 class="alert-heading">✅ <?php echo $isReconfigure ? 'Reconfiguration' : 'Installation'; ?> Complete!</h5>
            <p>PrivateBin has been configured and the database tables have been created.</p>
            <?php if (!empty($adminUser) && !empty($adminPass)): ?>
            <p>Admin account <strong><?php echo htmlspecialchars($adminUser); ?></strong> has been created.</p>
            <?php endif; ?>
            <?php if ($migrateResult): ?>
            <p><strong>Migration:</strong> <?php echo htmlspecialchars($migrateResult); ?></p>
            <?php endif; ?>
            <hr>
            <p class="mb-0"><strong>⚠️ Security:</strong> Delete the entire <code>install/</code> folder immediately!</p>
        </div>
        <a href="../" class="btn btn-primary w-100">Open PrivateBin</a>
    </div>

<?php else: ?>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <input type="hidden" name="step" value="2">

        <div class="form-section">
            <h5>📋 General Settings</h5>
            <div class="mb-3">
                <label for="site_name" class="form-label">Site Name</label>
                <input type="text" class="form-control" id="site_name" name="site_name"
                    value="<?php echo htmlspecialchars($_POST['site_name'] ?? cfgVal($existing, 'main', 'name', 'PrivateBin')); ?>">
            </div>
        </div>

        <div class="form-section">
            <h5>⚙️ Features</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" name="discussion" value="1"
                            <?php echo (!empty($_POST['discussion']) || (!isset($_POST['step']) && cfgVal($existing, 'main', 'discussion', '1'))) ? 'checked' : ''; ?>>
                        <label class="form-check-label">Enable discussions / comments</label>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" name="password_feature" value="1"
                            <?php echo (!empty($_POST['password_feature']) || (!isset($_POST['step']) && cfgVal($existing, 'main', 'password', '1'))) ? 'checked' : ''; ?>>
                        <label class="form-check-label">Enable paste passwords</label>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" name="fileupload" value="1"
                            <?php echo (!empty($_POST['fileupload']) || (!isset($_POST['step']) && cfgVal($existing, 'main', 'fileupload', ''))) ? 'checked' : ''; ?>>
                        <label class="form-check-label">Enable file upload</label>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" name="burnafterreading" value="1"
                            <?php echo (!empty($_POST['burnafterreading']) || (!isset($_POST['step']) && cfgVal($existing, 'main', 'burnafterreadingselected', ''))) ? 'checked' : ''; ?>>
                        <label class="form-check-label">Pre-select burn after reading</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" name="qrcode" value="1"
                            <?php echo (!empty($_POST['qrcode']) || (!isset($_POST['step']) && cfgVal($existing, 'main', 'qrcode', '1'))) ? 'checked' : ''; ?>>
                        <label class="form-check-label">Enable QR code sharing</label>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" name="email_feature" value="1"
                            <?php echo (!empty($_POST['email_feature']) || (!isset($_POST['step']) && cfgVal($existing, 'main', 'email', '1'))) ? 'checked' : ''; ?>>
                        <label class="form-check-label">Enable email sharing</label>
                    </div>
                    <div class="mb-2 form-check">
                        <input type="checkbox" class="form-check-input" name="languageselection" value="1"
                            <?php echo (!empty($_POST['languageselection']) || (!isset($_POST['step']) && cfgVal($existing, 'main', 'languageselection', ''))) ? 'checked' : ''; ?>>
                        <label class="form-check-label">Enable language selection</label>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Default format</label>
                    <?php $curFmt = $_POST['defaultformatter'] ?? cfgVal($existing, 'main', 'defaultformatter', 'plaintext'); ?>
                    <select class="form-select form-select-sm" name="defaultformatter">
                        <option value="plaintext" <?php echo $curFmt === 'plaintext' ? 'selected' : ''; ?>>Plain Text</option>
                        <option value="syntaxhighlighting" <?php echo $curFmt === 'syntaxhighlighting' ? 'selected' : ''; ?>>Source Code</option>
                        <option value="markdown" <?php echo $curFmt === 'markdown' ? 'selected' : ''; ?>>Markdown</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Template</label>
                    <?php $curTpl = $_POST['template'] ?? cfgVal($existing, 'main', 'template', 'bootstrap5'); ?>
                    <select class="form-select form-select-sm" name="template">
                        <option value="bootstrap5" <?php echo $curTpl === 'bootstrap5' ? 'selected' : ''; ?>>Bootstrap 5</option>
                        <option value="bootstrap" <?php echo $curTpl === 'bootstrap' ? 'selected' : ''; ?>>Bootstrap 3</option>
                        <option value="bootstrap-dark" <?php echo $curTpl === 'bootstrap-dark' ? 'selected' : ''; ?>>Dark</option>
                        <option value="bootstrap-compact" <?php echo $curTpl === 'bootstrap-compact' ? 'selected' : ''; ?>>Compact</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Compression</label>
                    <?php $curComp = $_POST['compression'] ?? cfgVal($existing, 'main', 'compression', 'zlib'); ?>
                    <select class="form-select form-select-sm" name="compression">
                        <option value="zlib" <?php echo $curComp === 'zlib' ? 'selected' : ''; ?>>zlib (recommended)</option>
                        <option value="none" <?php echo $curComp === 'none' ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Size limit (bytes)</label>
                    <input type="number" class="form-control form-control-sm" name="sizelimit"
                        value="<?php echo htmlspecialchars($_POST['sizelimit'] ?? cfgVal($existing, 'main', 'sizelimit', '10000000')); ?>">
                    <div class="form-text">Default: 10 MB</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Rate limit (seconds)</label>
                    <input type="number" class="form-control form-control-sm" name="ratelimit"
                        value="<?php echo htmlspecialchars($_POST['ratelimit'] ?? cfgVal($existing, 'traffic', 'limit', '10')); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Default expiration</label>
                    <?php $curExp = $_POST['defaultexpire'] ?? cfgVal($existing, 'expire', 'default', '1week'); ?>
                    <select class="form-select form-select-sm" name="defaultexpire">
                        <option value="5min" <?php echo $curExp === '5min' ? 'selected' : ''; ?>>5 minutes</option>
                        <option value="10min" <?php echo $curExp === '10min' ? 'selected' : ''; ?>>10 minutes</option>
                        <option value="1hour" <?php echo $curExp === '1hour' ? 'selected' : ''; ?>>1 hour</option>
                        <option value="1day" <?php echo $curExp === '1day' ? 'selected' : ''; ?>>1 day</option>
                        <option value="1week" <?php echo $curExp === '1week' ? 'selected' : ''; ?>>1 week</option>
                        <option value="1month" <?php echo $curExp === '1month' ? 'selected' : ''; ?>>1 month</option>
                        <option value="1year" <?php echo $curExp === '1year' ? 'selected' : ''; ?>>1 year</option>
                        <option value="never" <?php echo $curExp === 'never' ? 'selected' : ''; ?>>Never</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5>🔗 URL Shortener</h5>
            <?php $curShortener = $_POST['shortener_type'] ?? $currentShortenerType; ?>
            <div class="mb-3">
                <label class="form-label">Shortener Type</label>
                <select class="form-select" id="shortener_type" name="shortener_type" onchange="toggleShortenerFields()">
                    <option value="none" <?php echo $curShortener === 'none' ? 'selected' : ''; ?>>None</option>
                    <option value="yourls" <?php echo $curShortener === 'yourls' ? 'selected' : ''; ?>>YOURLS (server-side proxy)</option>
                    <option value="shlink" <?php echo $curShortener === 'shlink' ? 'selected' : ''; ?>>Shlink (server-side proxy)</option>
                    <option value="direct" <?php echo $curShortener === 'direct' ? 'selected' : ''; ?>>Direct URL (custom API)</option>
                </select>
            </div>

            <div id="shortener-yourls-fields" style="display:none;">
                <div class="mb-3">
                    <label class="form-label">YOURLS API URL</label>
                    <input type="text" class="form-control form-control-sm" name="yourls_apiurl"
                        value="<?php echo htmlspecialchars($_POST['yourls_apiurl'] ?? cfgVal($existing, 'yourls', 'apiurl', '')); ?>"
                        placeholder="https://yourls.example.com/yourls-api.php">
                </div>
                <div class="mb-3">
                    <label class="form-label">YOURLS Signature (access key)</label>
                    <input type="text" class="form-control form-control-sm" name="yourls_signature"
                        value="<?php echo htmlspecialchars($_POST['yourls_signature'] ?? cfgVal($existing, 'yourls', 'signature', '')); ?>"
                        placeholder="Your YOURLS signature key">
                </div>
                <div class="form-text mb-3">The shortener URL will automatically be set to <code>${basepath}?shortenviayourls&amp;link=</code></div>
            </div>

            <div id="shortener-shlink-fields" style="display:none;">
                <div class="mb-3">
                    <label class="form-label">Shlink API URL</label>
                    <input type="text" class="form-control form-control-sm" name="shlink_apiurl"
                        value="<?php echo htmlspecialchars($_POST['shlink_apiurl'] ?? cfgVal($existing, 'shlink', 'apiurl', '')); ?>"
                        placeholder="https://shlink.example.com/rest/v3/short-urls">
                </div>
                <div class="mb-3">
                    <label class="form-label">Shlink API Key</label>
                    <input type="text" class="form-control form-control-sm" name="shlink_apikey"
                        value="<?php echo htmlspecialchars($_POST['shlink_apikey'] ?? cfgVal($existing, 'shlink', 'apikey', '')); ?>"
                        placeholder="Your Shlink API key">
                </div>
                <div class="form-text mb-3">The shortener URL will automatically be set to <code>${basepath}?shortenviashlink&amp;link=</code></div>
            </div>

            <div id="shortener-direct-fields" style="display:none;">
                <div class="mb-3">
                    <label class="form-label">Shortener API URL</label>
                    <?php
                    $curDirectUrl = '';
                    if ($currentShortenerType === 'direct') {
                        $curDirectUrl = cfgVal($existing, 'main', 'urlshortener', '');
                    }
                    ?>
                    <input type="text" class="form-control form-control-sm" name="shortener_direct"
                        value="<?php echo htmlspecialchars($_POST['shortener_direct'] ?? $curDirectUrl); ?>"
                        placeholder="https://shortener.example.com/api?link=">
                    <div class="form-text">The paste URL will be appended to this URL.</div>
                </div>
            </div>

            <div id="shortener-warning-field" style="display:none;">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="urlshortenerwarning" value="1"
                        <?php echo (!empty($_POST['urlshortenerwarning']) || (!isset($_POST['step']) && cfgVal($existing, 'main', 'urlshortenerwarning', '1'))) ? 'checked' : ''; ?>>
                    <label class="form-check-label">Show warning that shortener may expose decrypt key</label>
                    <div class="form-text">Disable if you control both PrivateBin and the shortener.</div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h5>🗄️ Database Configuration</h5>
            <?php $curDbType = $_POST['db_type'] ?? $currentDbType; ?>
            <div class="mb-3">
                <label for="db_type" class="form-label">Database Type</label>
                <select class="form-select" id="db_type" name="db_type" onchange="toggleDbFields()">
                    <option value="sqlite" <?php echo $curDbType === 'sqlite' ? 'selected' : ''; ?>>SQLite (recommended for small instances)</option>
                    <option value="mysql" <?php echo $curDbType === 'mysql' ? 'selected' : ''; ?>>MySQL / MariaDB</option>
                    <option value="pgsql" <?php echo $curDbType === 'pgsql' ? 'selected' : ''; ?>>PostgreSQL</option>
                </select>
                <div class="form-text">SQLite requires no separate server — a file is created in the <code>data/</code> directory.</div>
            </div>

            <div id="db-server-fields" style="display:none;">
                <?php
                // parse host/port from existing DSN
                $curDbHost = 'localhost';
                $curDbPort = '';
                $curDbName = 'privatebin';
                if ($isReconfigure) {
                    $curDsn = cfgVal($existing, 'model_options', 'dsn', '');
                    if (preg_match('/host=([^;]+)/', $curDsn, $m)) $curDbHost = $m[1];
                    if (preg_match('/port=([^;]+)/', $curDsn, $m)) $curDbPort = $m[1];
                    if (preg_match('/dbname=([^;]+)/', $curDsn, $m)) $curDbName = $m[1];
                }
                ?>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="db_host" class="form-label">Host</label>
                        <input type="text" class="form-control" id="db_host" name="db_host"
                            value="<?php echo htmlspecialchars($_POST['db_host'] ?? $curDbHost); ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="db_port" class="form-label">Port</label>
                        <input type="text" class="form-control" id="db_port" name="db_port"
                            value="<?php echo htmlspecialchars($_POST['db_port'] ?? $curDbPort); ?>" placeholder="Default">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="db_name" class="form-label">Database Name</label>
                    <input type="text" class="form-control" id="db_name" name="db_name"
                        value="<?php echo htmlspecialchars($_POST['db_name'] ?? $curDbName); ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="db_user" class="form-label">Username</label>
                        <input type="text" class="form-control" id="db_user" name="db_user"
                            value="<?php echo htmlspecialchars($_POST['db_user'] ?? cfgVal($existing, 'model_options', 'usr', '')); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="db_pass" class="form-label">Password</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass"
                            value="<?php echo htmlspecialchars($_POST['db_pass'] ?? cfgVal($existing, 'model_options', 'pwd', '')); ?>">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="tbl_prefix" class="form-label">Table Prefix (optional)</label>
                <input type="text" class="form-control" id="tbl_prefix" name="tbl_prefix"
                    value="<?php echo htmlspecialchars($_POST['tbl_prefix'] ?? cfgVal($existing, 'model_options', 'tbl', '')); ?>" placeholder="e.g. pb_">
                <div class="form-text">Useful if sharing a database with other applications.</div>
            </div>

<?php if ($isReconfigure): ?>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="migrate_data" name="migrate_data" value="1">
                <label class="form-check-label" for="migrate_data">
                    <strong>Migrate data to new database</strong>
                    <div class="form-text">Transfer users from current database to the new one. Only works when changing databases.</div>
                </label>
            </div>
<?php endif; ?>
        </div>

        <div class="form-section">
            <h5>🔐 Authentication</h5>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="auth_enabled" name="auth_enabled" value="1"
                    <?php echo (!empty($_POST['auth_enabled']) || (!isset($_POST['step']) && cfgVal($existing, 'auth', 'enabled', ''))) ? 'checked' : ''; ?> onchange="toggleAuthFields()">
                <label class="form-check-label" for="auth_enabled">Enable built-in user authentication</label>
            </div>

            <div id="auth-fields" style="display:none;">
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="require_login_create" name="require_login_create" value="1"
                        <?php echo (!empty($_POST['require_login_create']) || (!isset($_POST['step']) && cfgVal($existing, 'auth', 'require_login_to_create', '1'))) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="require_login_create">Require login to create pastes</label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="require_login_read" name="require_login_read" value="1"
                        <?php echo (!empty($_POST['require_login_read']) || (!isset($_POST['step']) && cfgVal($existing, 'auth', 'require_login_to_read', ''))) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="require_login_read">Require login to read pastes</label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="allow_registration" name="allow_registration" value="1"
                        <?php echo (!empty($_POST['allow_registration']) || (!isset($_POST['step']) && cfgVal($existing, 'auth', 'allow_registration', ''))) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="allow_registration">Allow self-registration</label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="require_approval" name="require_approval" value="1"
                        <?php echo (!empty($_POST['require_approval']) || (!isset($_POST['step']) && cfgVal($existing, 'auth', 'require_approval', ''))) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="require_approval">Require admin approval for new users</label>
                </div>
                <div class="mb-3">
                    <label for="admin_email" class="form-label">Admin email (for notifications)</label>
                    <input type="email" class="form-control form-control-sm" id="admin_email" name="admin_email"
                        value="<?php echo htmlspecialchars($_POST['admin_email'] ?? cfgVal($existing, 'auth', 'admin_email', '')); ?>"
                        placeholder="admin@example.com">
                </div>

                <hr>
                <h6>Admin Account <?php echo $isReconfigure ? '<small class="text-muted">(leave blank to keep existing)</small>' : ''; ?></h6>
                <div class="mb-3">
                    <label for="admin_username" class="form-label">Admin Username</label>
                    <input type="text" class="form-control" id="admin_username" name="admin_username"
                        value="<?php echo htmlspecialchars($_POST['admin_username'] ?? ($isReconfigure ? '' : 'admin')); ?>" autocomplete="off">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="admin_password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="admin_password" name="admin_password" autocomplete="new-password">
                        <div class="form-text">Minimum 8 characters</div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="admin_password2" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="admin_password2" name="admin_password2" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-100">
            <?php echo $isReconfigure ? 'Save Configuration' : 'Install PrivateBin'; ?>
        </button>
    </form>

    <script>
    function toggleDbFields() {
        var type = document.getElementById('db_type').value;
        document.getElementById('db-server-fields').style.display = type === 'sqlite' ? 'none' : 'block';
    }
    function toggleAuthFields() {
        var enabled = document.getElementById('auth_enabled').checked;
        document.getElementById('auth-fields').style.display = enabled ? 'block' : 'none';
    }
    function toggleShortenerFields() {
        var type = document.getElementById('shortener_type').value;
        document.getElementById('shortener-yourls-fields').style.display = type === 'yourls' ? 'block' : 'none';
        document.getElementById('shortener-shlink-fields').style.display = type === 'shlink' ? 'block' : 'none';
        document.getElementById('shortener-direct-fields').style.display = type === 'direct' ? 'block' : 'none';
        document.getElementById('shortener-warning-field').style.display = type !== 'none' ? 'block' : 'none';
    }
    toggleDbFields();
    toggleAuthFields();
    toggleShortenerFields();
    </script>
<?php endif; ?>
</div>
</body>
</html>
