<?php declare(strict_types=1);
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 */

namespace PrivateBin;

use Exception;
use PrivateBin\Auth\Auth;
use PrivateBin\Auth\User;
use PrivateBin\Exception\JsonException;
use PrivateBin\Exception\TranslatedException;
use PrivateBin\Persistence\ServerSalt;
use PrivateBin\Persistence\TrafficLimiter;
use PrivateBin\Proxy\AbstractProxy;
use PrivateBin\Proxy\ShlinkProxy;
use PrivateBin\Proxy\YourlsProxy;

/**
 * Controller
 *
 * Puts it all together.
 */
class Controller
{
    /**
     * version
     *
     * @const string
     */
    const VERSION = '2.0.4';

    /**
     * minimal required PHP version
     *
     * @const string
     */
    const MIN_PHP_VERSION = '7.4.0';

    /**
     * show the same error message if the document expired or does not exist
     *
     * @const string
     */
    const GENERIC_ERROR = 'Document does not exist, has expired or has been deleted.';

    /**
     * configuration
     *
     * @access private
     * @var    Configuration
     */
    private $_conf;

    /**
     * error message
     *
     * @access private
     * @var    string
     */
    private $_error = '';

    /**
     * status message
     *
     * @access private
     * @var    string
     */
    private $_status = '';

    /**
     * status message
     *
     * @access private
     * @var    bool
     */
    private $_is_deleted = false;

    /**
     * JSON message
     *
     * @access private
     * @var    string
     */
    private $_json = '';

    /**
     * Factory of instance models
     *
     * @access private
     * @var    model
     */
    private $_model;

    /**
     * authentication handler
     *
     * @access private
     * @var    Auth
     */
    private $_auth;

    /**
     * request
     *
     * @access private
     * @var    request
     */
    private $_request;

    /**
     * URL base
     *
     * @access private
     * @var    string
     */
    private $_urlBase;

    /**
     * constructor
     *
     * initializes and runs PrivateBin
     *
     * @param ?Configuration $config
     *
     * @access public
     * @throws Exception
     */
    public function __construct(?Configuration $config = null)
    {
        if (version_compare(PHP_VERSION, self::MIN_PHP_VERSION) < 0) {
            error_log(I18n::_('%s requires php %s or above to work. Sorry.', I18n::_('PrivateBin'), self::MIN_PHP_VERSION));
            return;
        }
        if (strlen(PATH) < 0 && substr(PATH, -1) !== DIRECTORY_SEPARATOR) {
            error_log(I18n::_('%s requires the PATH to end in a "%s". Please update the PATH in your index.php.', I18n::_('PrivateBin'), DIRECTORY_SEPARATOR));
            return;
        }

        // load config (using ini file by default) & initialize required classes
        $this->_conf = $config ?? new Configuration();
        $this->_init();

        switch ($this->_request->getOperation()) {
            case 'create':
                if ($this->_requireAuth('create')) {
                    break;
                }
                $this->_create();
                break;
            case 'delete':
                $this->_delete(
                    $this->_request->getParam('pasteid'),
                    $this->_request->getParam('deletetoken')
                );
                break;
            case 'read':
                if ($this->_requireAuth('read')) {
                    break;
                }
                $this->_read($this->_request->getParam('pasteid'));
                break;
            case 'jsonld':
                $this->_jsonld($this->_request->getParam('jsonld'));
                return;
            case 'yourlsproxy':
                $this->_shortenerproxy(new YourlsProxy($this->_conf, $this->_request->getParam('link')));
                break;
            case 'shlinkproxy':
                $this->_shortenerproxy(new ShlinkProxy($this->_conf, $this->_request->getParam('link')));
                break;
            case 'auth':
                $this->_handleAuth();
                break;
        }

        $this->_setCacheHeaders();

        // output JSON or HTML
        if ($this->_request->isJsonApiCall()) {
            header('Content-type: ' . Request::MIME_JSON);
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
            header('Access-Control-Allow-Headers: X-Requested-With, Content-Type');
            header('X-Uncompressed-Content-Length: ' . strlen($this->_json));
            header('Access-Control-Expose-Headers: X-Uncompressed-Content-Length');
            echo $this->_json;
        } else {
            $this->_view();
        }
    }

    /**
     * initialize PrivateBin
     *
     * @access private
     * @throws Exception
     */
    private function _init()
    {
        $this->_model   = new Model($this->_conf);
        $this->_request = new Request;
        $this->_urlBase = $this->_request->getRequestUri();
        $this->_auth    = new Auth($this->_conf, $this->_model->getStore());

        // attempt to resume existing session
        $this->_auth->authenticate();

        $this->_setDefaultLanguage();
        $this->_setDefaultTemplate();
    }

    /**
     * Set default language
     *
     * @access private
     * @throws Exception
     */
    private function _setDefaultLanguage()
    {
        $lang = $this->_conf->getKey('languagedefault');
        I18n::setLanguageFallback($lang);
        // force default language, if language selection is disabled and a default is set
        if (!$this->_conf->getKey('languageselection') && strlen($lang) === 2) {
            $_COOKIE['lang'] = $lang;
            setcookie('lang', $lang, array('SameSite' => 'Lax', 'Secure' => true));
        }
    }

    /**
     * Set default template
     *
     * @access private
     * @throws Exception
     */
    private function _setDefaultTemplate()
    {
        $templates = $this->_conf->getKey('availabletemplates');
        $template  = $this->_conf->getKey('template');
        if (!in_array($template, $templates, true)) {
            $templates[] = $template;
        }
        TemplateSwitcher::setAvailableTemplates($templates);
        TemplateSwitcher::setTemplateFallback($template);

        // force default template, if template selection is disabled
        if (!$this->_conf->getKey('templateselection') && array_key_exists('template', $_COOKIE)) {
            unset($_COOKIE['template']); // ensure value is not re-used in template switcher
            $expiredInAllTimezones = time() - 86400;
            setcookie('template', '', array('expires' => $expiredInAllTimezones, 'SameSite' => 'Lax', 'Secure' => true));
        }
    }

    /**
     * Check if auth is required for the given operation and deny if not authenticated
     *
     * @access private
     * @param string $operation 'create' or 'read'
     * @return bool true if access was denied (caller should break)
     */
    private function _requireAuth(string $operation): bool
    {
        if (!$this->_auth->isEnabled()) {
            return false;
        }

        $required = ($operation === 'create')
            ? $this->_auth->requiresLoginToCreate()
            : $this->_auth->requiresLoginToRead();

        if (!$required) {
            return false;
        }

        if ($this->_auth->getCurrentUser() !== null) {
            return false;
        }

        if ($this->_request->isJsonApiCall()) {
            $this->_json_error(I18n::_('Authentication required.'));
        } else {
            $this->_error = 'Please log in to access this resource.';
        }
        return true;
    }

    /**
     * Handle authentication API calls (login, logout, register, admin user management)
     *
     * @access private
     */
    private function _handleAuth(): void
    {
        $action = $this->_request->getParam('auth_action', '');

        // special case: initial setup when no users exist
        if ($action === 'setup' && $this->_auth->isEnabled() && !$this->_auth->hasUsers()) {
            $this->_authSetup();
            return;
        }

        switch ($action) {
            case 'login':
                $this->_authLogin();
                break;
            case 'logout':
                $this->_authLogout();
                break;
            case 'register':
                $this->_authRegister();
                break;
            case 'status':
                $this->_authStatus();
                break;
            case 'users':
                $this->_authListUsers();
                break;
            case 'create_user':
                $this->_authCreateUser();
                break;
            case 'delete_user':
                $this->_authDeleteUser();
                break;
            case 'change_password':
                $this->_authChangePassword();
                break;
            case 'change_role':
                $this->_authChangeRole();
                break;
            case 'toggle_active':
                $this->_authToggleActive();
                break;
            default:
                $this->_json_error(I18n::_('Invalid authentication action.'));
        }
    }

    /**
     * Handle initial admin setup (first user creation)
     *
     * @access private
     */
    private function _authSetup(): void
    {
        $username = $this->_request->getParam('username', '');
        $password = $this->_request->getParam('password', '');

        $result = $this->_auth->createUser($username, $password, User::ROLE_ADMIN);
        if ($result['success']) {
            // auto-login the new admin
            $this->_auth->login($username, $password);
            $user       = $this->_auth->getCurrentUser();
            $this->_json = Json::encode(array(
                'status'   => 0,
                'username' => $user->getUsername(),
                'role'     => $user->getRole(),
                'csrf'     => $this->_auth->getSession()->getCsrfToken(),
            ));
        } else {
            $this->_json_error(I18n::_($result['message']));
        }
    }

    /**
     * Handle login request
     *
     * @access private
     */
    private function _authLogin(): void
    {
        $username = $this->_request->getParam('username', '');
        $password = $this->_request->getParam('password', '');

        $result = $this->_auth->login($username, $password);
        if ($result['success']) {
            $user       = $this->_auth->getCurrentUser();
            $this->_json = Json::encode(array(
                'status'   => 0,
                'username' => $user->getUsername(),
                'role'     => $user->getRole(),
                'csrf'     => $this->_auth->getSession()->getCsrfToken(),
            ));
        } else {
            $this->_json_error(I18n::_($result['message']));
        }
    }

    /**
     * Handle logout request
     *
     * @access private
     */
    private function _authLogout(): void
    {
        $this->_auth->logout();
        $this->_json = Json::encode(array('status' => 0));
    }

    /**
     * Handle registration request
     *
     * @access private
     */
    private function _authRegister(): void
    {
        $username = $this->_request->getParam('username', '');
        $password = $this->_request->getParam('password', '');

        $result = $this->_auth->register($username, $password);
        if ($result['success']) {
            // auto-login after registration
            $this->_auth->login($username, $password);
            $user       = $this->_auth->getCurrentUser();
            $this->_json = Json::encode(array(
                'status'   => 0,
                'username' => $user->getUsername(),
                'role'     => $user->getRole(),
                'csrf'     => $this->_auth->getSession()->getCsrfToken(),
            ));
        } else {
            $this->_json_error(I18n::_($result['message']));
        }
    }

    /**
     * Return current auth status
     *
     * @access private
     */
    private function _authStatus(): void
    {
        $user = $this->_auth->getCurrentUser();
        if ($user !== null) {
            $this->_json = Json::encode(array(
                'status'   => 0,
                'loggedin' => true,
                'username' => $user->getUsername(),
                'role'     => $user->getRole(),
                'csrf'     => $this->_auth->getSession()->getCsrfToken(),
            ));
        } else {
            $this->_json = Json::encode(array(
                'status'   => 0,
                'loggedin' => false,
            ));
        }
    }

    /**
     * List all users (admin only)
     *
     * @access private
     */
    private function _authListUsers(): void
    {
        if (!$this->_isAdmin()) {
            $this->_json_error(I18n::_('Admin access required.'));
            return;
        }

        $users    = $this->_auth->listUsers();
        $userData = array();
        foreach ($users as $user) {
            $userData[] = array(
                'username'   => $user->getUsername(),
                'role'       => $user->getRole(),
                'is_active'  => $user->isActive(),
                'created_at' => $user->getCreatedAt(),
                'last_login' => $user->getLastLogin(),
            );
        }

        $this->_json = Json::encode(array(
            'status' => 0,
            'users'  => $userData,
        ));
    }

    /**
     * Create a user (admin only)
     *
     * @access private
     */
    private function _authCreateUser(): void
    {
        if (!$this->_isAdmin()) {
            $this->_json_error(I18n::_('Admin access required.'));
            return;
        }

        $this->_validateCsrf();

        $username = $this->_request->getParam('username', '');
        $password = $this->_request->getParam('password', '');
        $role     = $this->_request->getParam('role', User::ROLE_USER);

        $result = $this->_auth->createUser($username, $password, $role);
        if ($result['success']) {
            $this->_json = Json::encode(array('status' => 0));
        } else {
            $this->_json_error(I18n::_($result['message']));
        }
    }

    /**
     * Delete a user (admin only)
     *
     * @access private
     */
    private function _authDeleteUser(): void
    {
        if (!$this->_isAdmin()) {
            $this->_json_error(I18n::_('Admin access required.'));
            return;
        }

        $this->_validateCsrf();

        $username = $this->_request->getParam('username', '');

        // prevent self-deletion
        $currentUser = $this->_auth->getCurrentUser();
        if ($currentUser !== null && $currentUser->getUsername() === $username) {
            $this->_json_error(I18n::_('Cannot delete your own account.'));
            return;
        }

        $result = $this->_auth->deleteUser($username);
        if ($result['success']) {
            $this->_json = Json::encode(array('status' => 0));
        } else {
            $this->_json_error(I18n::_($result['message']));
        }
    }

    /**
     * Change a user's password (admin or own account)
     *
     * @access private
     */
    private function _authChangePassword(): void
    {
        $currentUser = $this->_auth->getCurrentUser();
        if ($currentUser === null) {
            $this->_json_error(I18n::_('Authentication required.'));
            return;
        }

        $this->_validateCsrf();

        $username    = $this->_request->getParam('username', '');
        $newPassword = $this->_request->getParam('new_password', '');

        // non-admins can only change their own password
        if (!$currentUser->isAdmin() && $currentUser->getUsername() !== $username) {
            $this->_json_error(I18n::_('Admin access required.'));
            return;
        }

        // for own password change, verify current password
        if ($currentUser->getUsername() === $username) {
            $currentPassword = $this->_request->getParam('current_password', '');
            if (!$currentUser->verifyPassword($currentPassword)) {
                $this->_json_error(I18n::_('Current password is incorrect.'));
                return;
            }
        }

        $result = $this->_auth->changePassword($username, $newPassword);
        if ($result['success']) {
            $this->_json = Json::encode(array('status' => 0));
        } else {
            $this->_json_error(I18n::_($result['message']));
        }
    }

    /**
     * Change a user's role (admin only)
     *
     * @access private
     */
    private function _authChangeRole(): void
    {
        if (!$this->_isAdmin()) {
            $this->_json_error(I18n::_('Admin access required.'));
            return;
        }

        $this->_validateCsrf();

        $username = $this->_request->getParam('username', '');
        $role     = $this->_request->getParam('role', '');

        $result = $this->_auth->changeRole($username, $role);
        if ($result['success']) {
            $this->_json = Json::encode(array('status' => 0));
        } else {
            $this->_json_error(I18n::_($result['message']));
        }
    }

    /**
     * Toggle a user's active status (admin only)
     *
     * @access private
     */
    private function _authToggleActive(): void
    {
        if (!$this->_isAdmin()) {
            $this->_json_error(I18n::_('Admin access required.'));
            return;
        }

        $this->_validateCsrf();

        $username = $this->_request->getParam('username', '');
        $active   = (bool) $this->_request->getParam('active', '');

        $result = $this->_auth->setUserActive($username, $active);
        if ($result['success']) {
            $this->_json = Json::encode(array('status' => 0));
        } else {
            $this->_json_error(I18n::_($result['message']));
        }
    }

    /**
     * Check if current user is admin
     *
     * @access private
     * @return bool
     */
    private function _isAdmin(): bool
    {
        $user = $this->_auth->getCurrentUser();
        return $user !== null && $user->isAdmin();
    }

    /**
     * Validate CSRF token from request
     *
     * @access private
     */
    private function _validateCsrf(): void
    {
        $token = $this->_request->getParam('csrf_token', '');
        if (!$this->_auth->getSession()->validateCsrfToken($token)) {
            $this->_json_error(I18n::_('Invalid security token. Please refresh and try again.'));
        }
    }

    /**
     * Turn off browser caching
     *
     * @access private
     */
    private function _setCacheHeaders()
    {
        // set headers to disable caching
        $time = gmdate('D, d M Y H:i:s \G\M\T');
        header('Cache-Control: no-store, no-cache, no-transform, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: ' . $time);
        header('Last-Modified: ' . $time);
        header('Vary: Accept');
    }

    /**
     * Store new paste or comment
     *
     * POST contains:
     * JSON encoded object with mandatory keys:
     *   v = 2 (version)
     *   adata (array)
     *   ct (base64 encoded, encrypted text)
     * meta (optional):
     *   expire = expiration delay (never,5min,10min,1hour,1day,1week,1month,1year,burn) (default:1week)
     * parentid (optional) = in discussions, which comment this comment replies to.
     * pasteid (optional) = in discussions, which paste this comment belongs to.
     *
     * @access private
     * @throws Exception
     * @return string
     */
    private function _create()
    {
        // Ensure last paste from visitors IP address was more than configured amount of seconds ago.
        ServerSalt::setStore($this->_model->getStore());
        TrafficLimiter::setConfiguration($this->_conf);
        TrafficLimiter::setStore($this->_model->getStore());
        try {
            TrafficLimiter::canPass();
        } catch (TranslatedException $e) {
            $this->_json_error($e->getMessage());
            return;
        }

        $data      = $this->_request->getData();
        $isComment = array_key_exists('pasteid', $data) &&
            !empty($data['pasteid']) &&
            array_key_exists('parentid', $data) &&
            !empty($data['parentid']);
        if (!FormatV2::isValid($data, $isComment)) {
            $this->_json_error(I18n::_('Invalid data.'));
            return;
        }
        $sizelimit = $this->_conf->getKey('sizelimit');
        // Ensure content is not too big.
        if (strlen($data['ct']) > $sizelimit) {
            $this->_json_error(
                I18n::_(
                    'Document is limited to %s of encrypted data.',
                    Filter::formatHumanReadableSize($sizelimit)
                )
            );
            return;
        }

        // The user posts a comment.
        if ($isComment) {
            $paste = $this->_model->getPaste($data['pasteid']);
            if ($paste->exists()) {
                try {
                    $comment = $paste->getComment($data['parentid']);
                    $comment->setData($data);
                    $comment->store();
                    $this->_json_result($comment->getId());
                } catch (Exception $e) {
                    $this->_json_error($e->getMessage());
                }
            } else {
                $this->_json_error(I18n::_('Invalid data.'));
            }
        }
        // The user posts a standard paste.
        else {
            try {
                $this->_model->purge();
                $paste = $this->_model->getPaste();
                $paste->setData($data);
                $paste->store();
                $this->_json_result($paste->getId(), array('deletetoken' => $paste->getDeleteToken()));
            } catch (Exception $e) {
                $this->_json_error($e->getMessage());
            }
        }
    }

    /**
     * Delete an existing document
     *
     * @access private
     * @param  string $dataid
     * @param  string $deletetoken
     */
    private function _delete($dataid, $deletetoken)
    {
        try {
            $paste = $this->_model->getPaste($dataid);
            if ($paste->exists()) {
                // accessing this method ensures that the document would be
                // deleted if it has already expired
                $paste->get();
                if (hash_equals($paste->getDeleteToken(), $deletetoken)) {
                    // Document exists and deletion token is valid: Delete the it.
                    $paste->delete();
                    $this->_status     = 'Document was properly deleted.';
                    $this->_is_deleted = true;
                } else {
                    $this->_error = 'Wrong deletion token. Document was not deleted.';
                }
            } else {
                $this->_error = self::GENERIC_ERROR;
            }
        } catch (TranslatedException $e) {
            $this->_error = $e->getMessage();
        }
        if ($this->_request->isJsonApiCall()) {
            if (empty($this->_error)) {
                $this->_json_result($dataid);
            } else {
                $this->_json_error(I18n::_($this->_error));
            }
        }
    }

    /**
     * Read an existing document, only allowed via a JSON API call
     *
     * @access private
     * @param  string $dataid
     */
    private function _read($dataid)
    {
        if (!$this->_request->isJsonApiCall()) {
            return;
        }

        try {
            $paste = $this->_model->getPaste($dataid);
            if ($paste->exists()) {
                $data = $paste->get();
                if (array_key_exists('salt', $data['meta'])) {
                    unset($data['meta']['salt']);
                }
                $this->_json_result($dataid, (array) $data);
            } else {
                $this->_json_error(I18n::_(self::GENERIC_ERROR));
            }
        } catch (TranslatedException $e) {
            $this->_json_error($e->getMessage());
        }
    }

    /**
     * Display frontend.
     *
     * @access private
     * @throws Exception
     */
    private function _view()
    {
        header('Content-Security-Policy: ' . $this->_conf->getKey('cspheader'));
        header('Cross-Origin-Resource-Policy: same-origin');
        header('Cross-Origin-Embedder-Policy: require-corp');
        // disabled, because it prevents links from a document to the same site to
        // be opened. Didn't work with `same-origin-allow-popups` either.
        // See issue https://github.com/PrivateBin/PrivateBin/issues/970 for details.
        // header('Cross-Origin-Opener-Policy: same-origin');
        header('Permissions-Policy: browsing-topics=()');
        header('Referrer-Policy: no-referrer');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: deny');

        // label all the expiration options
        $expire = array();
        foreach ($this->_conf->getSection('expire_options') as $time => $seconds) {
            $expire[$time] = ($seconds === 0) ? I18n::_(ucfirst($time)) : Filter::formatHumanReadableTime($time);
        }

        // translate all the formatter options
        $formatters = array_map('PrivateBin\\I18n::_', $this->_conf->getSection('formatter_options'));

        // set language cookie if that functionality was enabled
        $languageselection = '';
        if ($this->_conf->getKey('languageselection')) {
            $languageselection = I18n::getLanguage();
            setcookie('lang', $languageselection, array('SameSite' => 'Lax', 'Secure' => true));
        }

        // set template cookie if that functionality was enabled
        $templateselection = '';
        if ($this->_conf->getKey('templateselection')) {
            $templateselection = TemplateSwitcher::getTemplate();
            setcookie('template', $templateselection, array('SameSite' => 'Lax', 'Secure' => true));
        }

        // strip policies that are unsupported in meta tag
        $metacspheader = str_replace(
            array(
                'frame-ancestors \'none\'; ',
                '; sandbox allow-same-origin allow-scripts allow-forms allow-modals allow-downloads',
            ),
            '',
            $this->_conf->getKey('cspheader')
        );

        $page = new View;
        $page->assign('CSPHEADER', $metacspheader);
        $page->assign('ERROR', I18n::_($this->_error));
        $page->assign('NAME', $this->_conf->getKey('name'));
        if (in_array($this->_request->getOperation(), array('shlinkproxy', 'yourlsproxy'), true)) {
            $page->assign('SHORTURL', $this->_status);
            $page->draw('shortenerproxy');
            return;
        }
        $page->assign('BASEPATH', I18n::_($this->_conf->getKey('basepath')));
        $page->assign('STATUS', I18n::_($this->_status));
        $page->assign('ISDELETED', $this->_is_deleted);
        $page->assign('VERSION', self::VERSION);
        $page->assign('DISCUSSION', $this->_conf->getKey('discussion'));
        $page->assign('OPENDISCUSSION', $this->_conf->getKey('opendiscussion'));
        $page->assign('MARKDOWN', array_key_exists('markdown', $formatters));
        $page->assign('SYNTAXHIGHLIGHTING', array_key_exists('syntaxhighlighting', $formatters));
        $page->assign('SYNTAXHIGHLIGHTINGTHEME', $this->_conf->getKey('syntaxhighlightingtheme'));
        $page->assign('FORMATTER', $formatters);
        $page->assign('FORMATTERDEFAULT', $this->_conf->getKey('defaultformatter'));
        $page->assign('INFO', I18n::_(str_replace("'", '"', $this->_conf->getKey('info'))));
        $page->assign('NOTICE', I18n::_($this->_conf->getKey('notice')));
        $page->assign('BURNAFTERREADINGSELECTED', $this->_conf->getKey('burnafterreadingselected'));
        $page->assign('PASSWORD', $this->_conf->getKey('password'));
        $page->assign('FILEUPLOAD', $this->_conf->getKey('fileupload'));
        $page->assign('LANGUAGESELECTION', $languageselection);
        $page->assign('LANGUAGES', I18n::getLanguageLabels(I18n::getAvailableLanguages()));
        $page->assign('TEMPLATESELECTION', $templateselection);
        $page->assign('TEMPLATES', TemplateSwitcher::getAvailableTemplates());
        $page->assign('EXPIRE', $expire);
        $page->assign('EXPIREDEFAULT', $this->_conf->getKey('default', 'expire'));
        $page->assign('URLSHORTENER', $this->_conf->getKey('urlshortener'));
        $page->assign('SHORTENBYDEFAULT', $this->_conf->getKey('shortenbydefault'));
        $page->assign('QRCODE', $this->_conf->getKey('qrcode'));
        $page->assign('EMAIL', $this->_conf->getKey('email'));
        $page->assign('HTTPWARNING', $this->_conf->getKey('httpwarning'));
        $page->assign('HTTPSLINK', 'https://' . $this->_request->getHost() . $this->_request->getRequestUri());
        $page->assign('COMPRESSION', $this->_conf->getKey('compression'));
        $page->assign('SRI', $this->_conf->getSection('sri'));

        // authentication state
        $page->assign('AUTH_ENABLED', $this->_auth->isEnabled());
        $page->assign('AUTH_ALLOW_REGISTRATION', $this->_auth->allowsRegistration());
        $page->assign('AUTH_REQUIRE_LOGIN_CREATE', $this->_auth->requiresLoginToCreate());
        $page->assign('AUTH_REQUIRE_LOGIN_READ', $this->_auth->requiresLoginToRead());
        $page->assign('AUTH_NEEDS_SETUP', $this->_auth->isEnabled() && !$this->_auth->hasUsers());
        $currentUser = $this->_auth->getCurrentUser();
        $page->assign('AUTH_USER', $currentUser ? $currentUser->getUsername() : '');
        $page->assign('AUTH_ROLE', $currentUser ? $currentUser->getRole() : '');
        $page->assign('AUTH_CSRF', $this->_auth->getSession()->getCsrfToken());

        $page->draw(TemplateSwitcher::getTemplate());
    }

    /**
     * outputs requested JSON-LD context
     *
     * @access private
     * @param string $type
     */
    private function _jsonld($type)
    {
        if (!in_array($type, array(
            'comment',
            'commentmeta',
            'paste',
            'pastemeta',
            'types',
        ))) {
            $type = '';
        }
        $content = '{}';
        $file    = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . $type . '.jsonld';
        if (is_readable($file)) {
            $content = str_replace(
                '?jsonld=',
                $this->_urlBase . '?jsonld=',
                file_get_contents($file)
            );
        }
        if ($type === 'types') {
            $content = str_replace(
                implode('", "', array_keys($this->_conf->getDefaults()['expire_options'])),
                implode('", "', array_keys($this->_conf->getSection('expire_options'))),
                $content
            );
        }

        header('Content-type: application/ld+json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET');
        echo $content;
    }

    /**
     * prepares JSON encoded error message
     *
     * @access private
     * @param  string $error
     * @throws JsonException
     */
    private function _json_error($error)
    {
        $result = array(
            'status'  => 1,
            'message' => $error,
        );
        $this->_json = Json::encode($result);
    }

    /**
     * prepares JSON encoded result message
     *
     * @access private
     * @param  string $dataid
     * @param  array $other
     * @throws JsonException
     */
    private function _json_result($dataid, $other = array())
    {
        $result = array(
            'status' => 0,
            'id'     => $dataid,
            'url'    => $this->_urlBase . '?' . $dataid,
        ) + $other;
        $this->_json = Json::encode($result);
    }

    /**
     * Proxies a link using the specified proxy class, and updates the status or error with the response.
     *
     * @access private
     * @param AbstractProxy $proxy The instance of the proxy class.
     */
    private function _shortenerproxy(AbstractProxy $proxy)
    {
        if ($proxy->isError()) {
            $this->_error = $proxy->getError();
        } else {
            $this->_status = $proxy->getUrl();
        }
    }
}
