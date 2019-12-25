<?php
use PrivateBin\I18n;
$isCpct = substr($template, 9, 8) === '-compact';
$isDark = substr($template, 9, 5) === '-dark';
$isPage = substr($template, -5) === '-page';
?><!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex" />
		<meta name="google" content="notranslate">
		<title><?php echo I18n::_($NAME); ?></title>
<?php
if (!$isDark):
?>
		<link type="text/css" rel="stylesheet" href="css/bootstrap/bootstrap-3.4.1.css" />
<?php
endif;
?>
		<link type="text/css" rel="stylesheet" href="css/bootstrap/bootstrap-theme-3.4.1.css" />
<?php
if ($isDark):
?>
		<link type="text/css" rel="stylesheet" href="css/bootstrap/darkstrap-0.9.3.css" />
<?php
endif;
?>
		<link type="text/css" rel="stylesheet" href="css/bootstrap/privatebin.css?<?php echo rawurlencode($VERSION); ?>" />
<?php
if ($SYNTAXHIGHLIGHTING):
?>
		<link type="text/css" rel="stylesheet" href="css/prettify/prettify.css?<?php echo rawurlencode($VERSION); ?>" />
<?php
    if (strlen($SYNTAXHIGHLIGHTINGTHEME)):
?>
		<link type="text/css" rel="stylesheet" href="css/prettify/<?php echo rawurlencode($SYNTAXHIGHLIGHTINGTHEME); ?>.css?<?php echo rawurlencode($VERSION); ?>" />
<?php
    endif;
endif;
?>
		<noscript><link type="text/css" rel="stylesheet" href="css/noscript.css" /></noscript>
		<script type="text/javascript" data-cfasync="false" src="js/jquery-3.4.1.js" integrity="sha512-bnIvzh6FU75ZKxp0GXLH9bewza/OIw6dLVh9ICg0gogclmYGguQJWl8U30WpbsGTqbIiAwxTsbe76DErLq5EDQ==" crossorigin="anonymous"></script>
<?php
if ($QRCODE):
?>
		<script async type="text/javascript" data-cfasync="false" src="js/kjua-0.6.0.js" integrity="sha512-GEEIHvphDt1NmaxzX8X1ZkBiGKXCv+Ofzwi8SMEH5wQVWqdGIvBO/fnxxKZ90RU1bVp6srS68nHIpZo6iVcG9g==" crossorigin="anonymous"></script>
<?php
endif;
if ($ZEROBINCOMPATIBILITY):
?>
		<script type="text/javascript" data-cfasync="false" src="js/base64-1.7.js" integrity="sha512-JdwsSP3GyHR+jaCkns9CL9NTt4JUJqm/BsODGmYhBcj5EAPKcHYh+OiMfyHbcDLECe17TL0hjXADFkusAqiYgA==" crossorigin="anonymous"></script>
<?php
endif;
?>
		<script type="text/javascript" data-cfasync="false" src="js/zlib-1.2.11.js" integrity="sha512-Yey/0yoaVmSbqMEyyff3DIu8kCPwpHvHf7tY1AuZ1lrX9NPCMg87PwzngMi+VNbe4ilCApmePeuKT869RTcyCQ==" crossorigin="anonymous"></script>
		<script type="text/javascript" data-cfasync="false" src="js/base-x-3.0.5.1.js" integrity="sha512-/zL3MWKMtl1IBF0URx3laql2jUw+rWfFFabNlILY/Qm+hUsQR/XULjUyNHkW/FkrV7A0sMQ7tsppH7sj5ht8wA==" crossorigin="anonymous"></script>
		<script type="text/javascript" data-cfasync="false" src="js/rawinflate-0.3.js" integrity="sha512-g8uelGgJW9A/Z1tB6Izxab++oj5kdD7B4qC7DHwZkB6DGMXKyzx7v5mvap2HXueI2IIn08YlRYM56jwWdm2ucQ==" crossorigin="anonymous"></script>
		<script type="text/javascript" data-cfasync="false" src="js/bootstrap-3.3.7.js" integrity="sha512-iztkobsvnjKfAtTNdHkGVjAYTrrtlC7mGp/54c40wowO7LhURYl3gVzzcEqGl/qKXQltJ2HwMrdLcNUdo+N/RQ==" crossorigin="anonymous"></script>
<?php
if ($SYNTAXHIGHLIGHTING):
?>
		<script type="text/javascript" data-cfasync="false" src="js/prettify.js?<?php echo rawurlencode($VERSION); ?>" integrity="sha512-puO0Ogy++IoA2Pb9IjSxV1n4+kQkKXYAEUtVzfZpQepyDPyXk8hokiYDS7ybMogYlyyEIwMLpZqVhCkARQWLMg==" crossorigin="anonymous"></script>
<?php
endif;
if ($MARKDOWN):
?>
		<script type="text/javascript" data-cfasync="false" src="js/showdown-1.9.1.js" integrity="sha512-VeINC2WUWLyxgeQ56SsYFjQ5+d7RLvLPYOEYDFtEDd3AyVsgIsHYsWZ6WwgALwB6YR6qrVEM3IH4Bck96IfbMw==" crossorigin="anonymous"></script>
<?php
endif;
?>
		<script type="text/javascript" data-cfasync="false" src="js/purify-2.0.7.js" integrity="sha512-XjNEK1xwh7SJ/7FouwV4VZcGW9cMySL3SwNpXgrURLBcXXQYtZdqhGoNdEwx9vwLvFjUGDQVNgpOrTsXlSTiQg==" crossorigin="anonymous"></script>
		<script type="text/javascript" data-cfasync="false" src="js/legacy.js?<?php echo rawurlencode($VERSION); ?>" integrity="sha512-LYos+qXHIRqFf5ZPNphvtTB0cgzHUizu2wwcOwcwz/VIpRv9lpcBgPYz4uq6jx0INwCAj6Fbnl5HoKiLufS2jg==" crossorigin="anonymous"></script>
		<script type="text/javascript" data-cfasync="false" src="js/privatebin.js?<?php echo rawurlencode($VERSION); ?>" integrity="sha512-HxU8g6MFviBCv6fEKEL+bn0fICxg9++ZUxxqySWXkX4iGgLodH/OVZDfr3dgKK5vZAv+0niWLuf+CWZ+/gUwWw==" crossorigin="anonymous"></script>
		<link rel="apple-touch-icon" href="img/apple-touch-icon.png?<?php echo rawurlencode($VERSION); ?>" sizes="180x180" />
		<link rel="icon" type="image/png" href="img/favicon-32x32.png?<?php echo rawurlencode($VERSION); ?>" sizes="32x32" />
		<link rel="icon" type="image/png" href="img/favicon-16x16.png?<?php echo rawurlencode($VERSION); ?>" sizes="16x16" />
		<link rel="manifest" href="manifest.json?<?php echo rawurlencode($VERSION); ?>" />
		<link rel="mask-icon" href="img/safari-pinned-tab.svg?<?php echo rawurlencode($VERSION); ?>" color="#ffcc00" />
		<link rel="shortcut icon" href="img/favicon.ico">
		<meta name="msapplication-config" content="browserconfig.xml">
		<meta name="theme-color" content="#ffe57e" />
	</head>
	<body role="document" data-compression="<?php echo rawurlencode($COMPRESSION); ?>"<?php
$class = array();
if ($isCpct) {
    $class[] = 'navbar-spacing';
}
if ($isDark) {
    $class[] = 'dark-theme';
}
if (count($class)) {
    echo ' class="', implode(' ', $class), '"';
}
?>>
		<div id="passwordmodal" tabindex="-1" class="modal fade" role="dialog" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-body">
						<form id="passwordform" role="form">
							<div class="form-group">
								<label for="passworddecrypt"><span class="glyphicon glyphicon-eye-open"></span> <?php echo I18n::_('Please enter the password for this paste:') ?></label>
								<input id="passworddecrypt" type="password" class="form-control" placeholder="<?php echo I18n::_('Enter password') ?>" required="required">
							</div>
							<button type="submit" class="btn btn-success btn-block"><span class="glyphicon glyphicon-off"></span> <?php echo I18n::_('Decrypt') ?></button>
						</form>
					</div>
				</div>
			</div>
		</div>
<?php
if ($QRCODE):
?>
		<div id="qrcodemodal" tabindex="-1" class="modal fade" aria-labelledby="qrcodemodalTitle" role="dialog" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-body">
						<div class="mx-auto" id="qrcode-display"></div>
					</div>
					<div class="row">
						<div class="btn-group col-xs-12">
							<span class="col-xs-12">
								<button type="button" class="btn btn-primary btn-block" data-dismiss="modal"><?php echo I18n::_('Close') ?></button>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
<?php
endif;
?>
		<div id="emailconfirmmodal" tabindex="-1" class="modal fade" aria-labelledby="emailconfirmmodalTitle" role="dialog" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-body">
						<div id="emailconfirm-display"></div>
					</div>
					<div class="row">
						<div class="btn-group col-xs-12" data-toggle="buttons">
							<span class="col-xs-12 col-md-4">
								<button id="emailconfirm-timezone-current" type="button" class="btn btn-danger btn-block" data-dismiss="modal"><?php echo I18n::_('Use Current Timezone') ?></button>
							</span>
							<span class="col-xs-12 col-md-4">
								<button id="emailconfirm-timezone-utc" type="button" class="btn btn-default btn-block" data-dismiss="modal"><?php echo I18n::_('Convert To UTC') ?></button>
							</span>
							<span class="col-xs-12 col-md-4">
								<button type="button" class="btn btn-primary btn-block" data-dismiss="modal"><?php echo I18n::_('Close') ?></button>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<nav class="navbar navbar-<?php echo $isDark ? 'inverse' : 'default'; ?> navbar-<?php echo $isCpct ? 'fixed' : 'static'; ?>-top"><?php
if ($isCpct):
?><div class="container"><?php
endif;
?>
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
					<span class="sr-only"><?php echo I18n::_('Toggle navigation'); ?></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="reloadlink navbar-brand" href="">
					<img alt="<?php echo I18n::_($NAME); ?>" src="img/icon.svg" width="38" />
				</a>
			</div>
			<div id="navbar" class="navbar-collapse collapse">
				<ul class="nav navbar-nav">
					<li id="loadingindicator" class="navbar-text hidden">
						<span class="glyphicon glyphicon-time" aria-hidden="true"></span>
						<?php echo I18n::_('Loading…'), PHP_EOL; ?>
					</li>
					<li>
						<button id="retrybutton" type="button" class="reloadlink hidden btn btn-<?php echo $isDark ? 'warning' : 'primary'; ?> navbar-btn">
							<span class="glyphicon glyphicon-repeat" aria-hidden="true"></span> <?php echo I18n::_('Retry'), PHP_EOL; ?>
						</button>
					</li>
					<li>
<?php
if ($isPage):
?>
						<button id="sendbutton" type="button" class="hidden btn btn-<?php echo $isDark ? 'warning' : 'primary'; ?> navbar-btn">
							<span class="glyphicon glyphicon-upload" aria-hidden="true"></span> <?php echo I18n::_('Send'), PHP_EOL;
else:
?>
						<button id="newbutton" type="button" class="hidden btn btn-<?php echo $isDark ? 'warning' : 'default'; ?> navbar-btn">
							<span class="glyphicon glyphicon-file" aria-hidden="true"></span> <?php echo I18n::_('New'), PHP_EOL;
endif;
?>
						</button>
						<button id="clonebutton" type="button" class="hidden btn btn-<?php echo $isDark ? 'warning' : 'default'; ?> navbar-btn">
							<span class="glyphicon glyphicon-duplicate" aria-hidden="true"></span> <?php echo I18n::_('Clone'), PHP_EOL; ?>
						</button>
						<button id="rawtextbutton" type="button" class="hidden btn btn-<?php echo $isDark ? 'warning' : 'default'; ?> navbar-btn">
							<span class="glyphicon glyphicon-text-background" aria-hidden="true"></span> <?php echo I18n::_('Raw text'), PHP_EOL; ?>
						</button>
						<button id="emaillink" type="button" class="hidden btn btn-<?php echo $isDark ? 'warning' : 'default'; ?> navbar-btn">
							<span class="glyphicon glyphicon-envelope" aria-hidden="true"></span> <?php echo I18n::_('Email'), PHP_EOL; ?>
						</button>
<?php
if ($QRCODE):
?>
						<button id="qrcodelink" type="button" data-toggle="modal" data-target="#qrcodemodal" class="hidden btn btn-<?php echo $isDark ? 'warning' : 'default'; ?> navbar-btn">
							<span class="glyphicon glyphicon-qrcode" aria-hidden="true"></span> <?php echo I18n::_('QR code'), PHP_EOL; ?>
						</button>
<?php
endif;
?>
					</li>
					<li class="dropdown">
						<select id="pasteExpiration" name="pasteExpiration" class="hidden">
<?php
foreach ($EXPIRE as $key => $value):
?>
							<option value="<?php echo $key; ?>"<?php
    if ($key == $EXPIREDEFAULT):
?> selected="selected"<?php
    endif;
?>><?php echo $value; ?></option>
<?php
endforeach;
?>
						</select>
						<a id="expiration" href="#" class="hidden dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo I18n::_('Expires'); ?>: <span id="pasteExpirationDisplay"><?php echo $EXPIRE[$EXPIREDEFAULT]; ?></span> <span class="caret"></span></a>
						<ul class="dropdown-menu">
<?php
foreach ($EXPIRE as $key => $value):
?>
							<li>
								<a href="#" data-expiration="<?php echo $key; ?>">
									<?php echo $value, PHP_EOL; ?>
								</a>
							</li>
<?php
endforeach;
?>
						</ul>
					</li>
<?php
if ($isCpct):
?>
					<li class="dropdown">
						<a id="formatter" href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo I18n::_('Options'); ?> <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li id="burnafterreadingoption" class="checkbox hidden">
								<label>
									<input type="checkbox" id="burnafterreading" name="burnafterreading"<?php
    if ($BURNAFTERREADINGSELECTED):
?> checked="checked"<?php
    endif;
?> />
									<?php echo I18n::_('Burn after reading'), PHP_EOL; ?>
								</label>
							</li>
<?php
    if ($DISCUSSION):
?>
							<li id="opendiscussionoption" class="checkbox hidden">
								<label>
									<input type="checkbox" id="opendiscussion" name="opendiscussion"<?php
        if ($OPENDISCUSSION):
?> checked="checked"<?php
        endif;
?> />
									<?php echo I18n::_('Open discussion'), PHP_EOL; ?>
								</label>
							</li>
<?php
    endif;
?>
							<li role="separator" class="divider"></li>
							<li>
								<div>
									<?php echo I18n::_('Format'); ?>: <span id="pasteFormatterDisplay"><?php echo $FORMATTER[$FORMATTERDEFAULT]; ?></span> <span class="caret"></span>
								</div>
							</li>
<?php
    foreach ($FORMATTER as $key => $value):
?>
							<li>
								<a href="#" data-format="<?php echo $key; ?>">
									<?php echo $value, PHP_EOL; ?>
								</a>
							</li>
<?php
    endforeach;
?>
						</ul>
						<select id="pasteFormatter" name="pasteFormatter" class="hidden">
<?php
    foreach ($FORMATTER as $key => $value):
?>
							<option value="<?php echo $key; ?>"<?php
        if ($key == $FORMATTERDEFAULT):
?> selected="selected"<?php
        endif;
?>><?php echo $value; ?></option>
<?php
    endforeach;
?>
						</select>
					</li>
<?php
else:
?>
					<li>
						<div id="burnafterreadingoption" class="navbar-text checkbox hidden">
							<label>
								<input type="checkbox" id="burnafterreading" name="burnafterreading"<?php
    if ($BURNAFTERREADINGSELECTED):
?> checked="checked"<?php
    endif;
?> />
								<?php echo I18n::_('Burn after reading'), PHP_EOL; ?>
							</label>
						</div>
					</li>
<?php
    if ($DISCUSSION):
?>
					<li>
						<div id="opendiscussionoption" class="navbar-text checkbox hidden">
							<label>
								<input type="checkbox" id="opendiscussion" name="opendiscussion"<?php
        if ($OPENDISCUSSION):
?> checked="checked"<?php
        endif;
?> />
								<?php echo I18n::_('Open discussion'), PHP_EOL; ?>
							</label>
						</div>
					</li>
<?php
    endif;
endif;
if ($PASSWORD):
?>
					<li>
						<div id="password" class="navbar-form hidden">
							<input type="password" id="passwordinput" placeholder="<?php echo I18n::_('Password (recommended)'); ?>" class="form-control" size="23" />
						</div>
					</li>
<?php
endif;
if ($FILEUPLOAD):
?>
					<li id="attach" class="hidden dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo I18n::_('Attach a file'); ?> <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li id="filewrap">
								<div>
									<input type="file" id="file" name="file" />
								</div>
								<div id="dragAndDropFileName" class="dragAndDropFile"><?php echo I18n::_('alternatively drag & drop a file or paste an image from the clipboard'); ?></div>
							</li>
							<li id="customattachment" class="hidden"></li>
							<li>
								<a id="fileremovebutton"  href="#">
									<?php echo I18n::_('Remove attachment'), PHP_EOL; ?>
								</a>
							</li>
						</ul>
					</li>
<?php
endif;
if (!$isCpct):
?>
					<li class="dropdown">
						<select id="pasteFormatter" name="pasteFormatter" class="hidden">
<?php
    foreach ($FORMATTER as $key => $value):
?>
							<option value="<?php echo $key; ?>"<?php
        if ($key == $FORMATTERDEFAULT):
?> selected="selected"<?php
        endif;
?>><?php echo $value; ?></option>
<?php
    endforeach;
?>
						</select>
						<a id="formatter" href="#" class="hidden dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo I18n::_('Format'); ?>: <span id="pasteFormatterDisplay"><?php echo $FORMATTER[$FORMATTERDEFAULT]; ?></span> <span class="caret"></span></a>
						<ul class="dropdown-menu">
<?php
    foreach ($FORMATTER as $key => $value):
?>
							<li>
								<a href="#" data-format="<?php echo $key; ?>">
									<?php echo $value, PHP_EOL; ?>
								</a>
							</li>
<?php
    endforeach;
?>
						</ul>
					</li>
<?php
endif;
?>
				</ul>
				<ul class="nav navbar-nav pull-right">
<?php
if (strlen($LANGUAGESELECTION)):
?>
					<li id="language" class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-flag" aria-hidden="true"></span> <?php echo $LANGUAGES[$LANGUAGESELECTION][0]; ?> <span class="caret"></span></a>
						<ul class="dropdown-menu">
<?php
    foreach ($LANGUAGES as $key => $value):
?>
							<li>
								<a href="#" data-lang="<?php echo $key; ?>">
									<?php echo $value[0]; ?> (<?php echo $value[1]; ?>)
								</a>
							</li>
<?php
    endforeach;
?>
						</ul>
					</li>
<?php
endif;
?>
				</ul>
			</div>
		<?php
if ($isCpct):
?></div><?php
endif;
?></nav>
		<main>
			<section class="container">
<?php
if (strlen($NOTICE)):
?>
				<div role="alert" class="alert alert-info">
					<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
					<?php echo htmlspecialchars($NOTICE), PHP_EOL; ?>
				</div>
<?php
endif;
?>
				<div id="remainingtime" role="alert" class="hidden alert alert-info">
					<span class="glyphicon glyphicon-fire" aria-hidden="true"></span>
				</div>
<?php
if ($FILEUPLOAD):
?>
				<div id="attachment" role="alert" class="hidden alert alert-info">
					<span class="glyphicon glyphicon-download-alt" aria-hidden="true"></span>
					<a class="alert-link"><?php echo I18n::_('Download attachment'), PHP_EOL; ?></a>
				</div>
<?php
endif;
?>
				<div id="status" role="alert" class="alert alert-info<?php echo empty($STATUS) ? ' hidden' : '' ?>">
					<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
					<?php echo htmlspecialchars($STATUS), PHP_EOL; ?>
				</div>
				<div id="errormessage" role="alert" class="<?php echo empty($ERROR) ? 'hidden' : '' ?> alert alert-danger">
					<span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
					<?php echo htmlspecialchars($ERROR), PHP_EOL; ?>
				</div>
				<noscript>
					<div id="noscript" role="alert" class="alert alert-<?php echo $isDark ? 'error' : 'warning'; ?>">
						<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
						<?php echo I18n::_('JavaScript is required for %s to work.<br />Sorry for the inconvenience.', I18n::_($NAME)), PHP_EOL; ?>
					</div>
				</noscript>
				<div id="oldnotice" role="alert" class="hidden alert alert-danger">
					<span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
					<?php echo I18n::_('%s requires a modern browser to work.', I18n::_($NAME)), PHP_EOL; ?>
					<a href="https://www.mozilla.org/firefox/">Firefox</a>,
					<a href="https://www.opera.com/">Opera</a>,
					<a href="https://www.google.com/chrome">Chrome</a>…
				</div>
<?php
if ($HTTPWARNING):
?>
				<div id="httpnotice" role="alert" class="hidden alert alert-danger">
					<span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
					<?php echo I18n::_('This website is using an insecure connection! Please only use it for testing.'), PHP_EOL; ?><br />
					<span class="small"><?php echo I18n::_('For more information <a href="%s">see this FAQ entry</a>.', 'https://github.com/PrivateBin/PrivateBin/wiki/FAQ#why-does-it-show-me-an-error-about-an-insecure-connection'); ?></span>
				</div>
				<div id="insecurecontextnotice" role="alert" class="hidden alert alert-danger">
					<span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
					<?php echo I18n::_('Your browser may require an HTTPS connection to support the WebCrypto API. Try <a href="%s">switching to HTTPS</a>.', $HTTPSLINK); ?>
				</div>
<?php
endif;
?>
				<div id="pastesuccess" class="hidden">
					<div role="alert" class="alert alert-success">
						<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
						<div id="deletelink"></div>
						<div id="pastelink"></div>
					</div>
<?php
if (strlen($URLSHORTENER)):
?>
					<p>
						<button id="shortenbutton" data-shortener="<?php echo htmlspecialchars($URLSHORTENER); ?>" type="button" class="btn btn-<?php echo $isDark ? 'warning' : 'primary'; ?> btn-block">
						<span class="glyphicon glyphicon-send" aria-hidden="true"></span> <?php echo I18n::_('Shorten URL'), PHP_EOL; ?>
					</button>
					</p>
					<div role="alert" class="alert alert-danger">
						<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
						<?php echo I18n::_('URL shortener may expose your decrypt key in URL.'), PHP_EOL; ?>
					</div>
<?php
endif;
?>
				</div>
				<ul id="editorTabs" class="nav nav-tabs hidden">
					<li role="presentation" class="active"><a id="messageedit" href="#"><?php echo I18n::_('Editor'); ?></a></li>
					<li role="presentation"><a id="messagepreview" href="#"><?php echo I18n::_('Preview'); ?></a></li>
					<li role="presentation" class="pull-right">
<?php
if ($isPage):
?>
						<button id="newbutton" type="button" class="reloadlink hidden btn btn-<?php echo $isDark ? 'warning' : 'default'; ?>">
							<span class="glyphicon glyphicon-file" aria-hidden="true"></span> <?php echo I18n::_('New'), PHP_EOL;
else:
?>
						<button id="sendbutton" type="button" class="hidden btn btn-<?php echo $isDark ? 'warning' : 'primary'; ?>">
							<span class="glyphicon glyphicon-upload" aria-hidden="true"></span> <?php echo I18n::_('Send'), PHP_EOL;
endif;
?>
						</button>
					</li>
				</ul>
			</section>
			<section class="container">
				<article class="row">
					<div id="placeholder" class="col-md-12 hidden"><?php echo I18n::_('+++ no paste text +++'); ?></div>
					<div id="attachmentPreview" class="col-md-12 text-center hidden"></div>
					<div id="prettymessage" class="col-md-12 hidden">
						<pre id="prettyprint" class="col-md-12 prettyprint linenums:1"></pre>
					</div>
					<div id="plaintext" class="col-md-12 hidden"></div>
					<p class="col-md-12"><textarea id="message" name="message" cols="80" rows="25" class="form-control hidden"></textarea></p>
				</article>
			</section>
			<section class="container">
				<div id="discussion" class="hidden">
					<h4><?php echo I18n::_('Discussion'); ?></h4>
					<div id="commentcontainer"></div>
				</div>
			</section>
			<section class="container">
				<div id="noscript" role="alert" class="alert alert-info noscript-hide">
					<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span>
					<?php echo I18n::_('Loading…'); ?><br />
					<span class="small"><?php echo I18n::_('In case this message never disappears please have a look at <a href="%s">this FAQ for information to troubleshoot</a>.', 'https://github.com/PrivateBin/PrivateBin/wiki/FAQ#why-does-not-the-loading-message-go-away'); ?></span>
				</div>
			</section>
			<footer class="container">
				<div class="row">
					<h4 class="col-md-5 col-xs-8"><?php echo I18n::_($NAME); ?> <small>- <?php echo I18n::_('Because ignorance is bliss'); ?></small></h4>
					<p class="col-md-1 col-xs-4 text-center"><?php echo $VERSION; ?></p>
					<p id="aboutbox" class="col-md-6 col-xs-12">
						<?php echo I18n::_('%s is a minimalist, open source online pastebin where the server has zero knowledge of pasted data. Data is encrypted/decrypted <i>in the browser</i> using 256 bits AES. More information on the <a href="https://privatebin.info/">project page</a>.', I18n::_($NAME)), PHP_EOL; ?>
					</p>
				</div>
			</footer>
		</main>
<?php
if ($DISCUSSION):
?>
		<div id="serverdata" class="hidden" aria-hidden="true">
			<div id="templates">
				<article id="commenttemplate" class="comment"><div class="commentmeta"><span class="nickname">name</span><span class="commentdate">0000-00-00</span></div><div class="commentdata">c</div><button class="btn btn-default btn-sm"><?php echo I18n::_('Reply'); ?></button></article>
				<p id="commenttailtemplate" class="comment"><button class="btn btn-default btn-sm"><?php echo I18n::_('Add comment'); ?></button></p>
				<div id="replytemplate" class="reply hidden"><input type="text" id="nickname" class="form-control" title="<?php echo I18n::_('Optional nickname…'); ?>" placeholder="<?php echo I18n::_('Optional nickname…'); ?>" /><textarea id="replymessage" class="replymessage form-control" cols="80" rows="7"></textarea><br /><div id="replystatus" role="alert" class="statusmessage hidden alert"><span class="glyphicon" aria-hidden="true"></span> </div><button id="replybutton" class="btn btn-default btn-sm"><?php echo I18n::_('Post comment'); ?></button></div>
			</div>
		</div>
<?php
endif;
?>
<?php
if ($FILEUPLOAD):
?>
		<div id="dropzone" class="hidden" tabindex="-1" aria-hidden="true"></div>
<?php
endif;
?>
	</body>
</html>