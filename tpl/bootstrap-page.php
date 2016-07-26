<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex" />
		<title><?php echo PrivateBin\i18n::_('PrivateBin'); ?></title>
		<link type="text/css" rel="stylesheet" href="css/bootstrap/bootstrap-3.3.5.css" />
		<link type="text/css" rel="stylesheet" href="css/bootstrap/bootstrap-theme-3.3.5.css" />
		<link type="text/css" rel="stylesheet" href="css/bootstrap/privatebin.css?<?php echo rawurlencode($VERSION); ?>" /><?php
if ($SYNTAXHIGHLIGHTING): ?>
		<link type="text/css" rel="stylesheet" href="css/prettify/prettify.css?<?php echo rawurlencode($VERSION); ?>" /><?php
    if (strlen($SYNTAXHIGHLIGHTINGTHEME)): ?>
		<link type="text/css" rel="stylesheet" href="css/prettify/<?php echo rawurlencode($SYNTAXHIGHLIGHTINGTHEME); ?>.css?<?php echo rawurlencode($VERSION); ?>" /><?php
    endif;
endif; ?>
		<script type="text/javascript" src="js/jquery-1.11.3.js"></script>
		<script type="text/javascript" src="js/sjcl-1.0.4.js"></script>
		<script type="text/javascript" src="js/base64-<?php echo rawurlencode($BASE64JSVERSION); ?>.js"></script>
		<script type="text/javascript" src="js/rawdeflate-0.5.js"></script>
		<script type="text/javascript" src="js/rawinflate-0.3.js"></script>
		<script type="text/javascript" src="js/bootstrap-3.3.5.js"></script><?php
if ($SYNTAXHIGHLIGHTING): ?>
		<script type="text/javascript" src="js/prettify.js?<?php echo rawurlencode($VERSION); ?>"></script><?php
endif;
if ($MARKDOWN): ?>
		<script type="text/javascript" src="js/showdown-1.4.1.js"></script><?php
endif; ?>
		<script type="text/javascript" src="js/privatebin.js?<?php echo rawurlencode($VERSION); ?>"></script>
		<!--[if lt IE 10]>
		<style type="text/css">body {padding-left:60px;padding-right:60px;} #ienotice {display:block;} #oldienotice {display:block;}</style>
		<![endif]-->
		<link rel="apple-touch-icon" href="apple-touch-icon.png?<?php echo rawurlencode($VERSION); ?>" sizes="180x180" />
		<link rel="icon" type="image/png" href="favicon-32x32.png?<?php echo rawurlencode($VERSION); ?>" sizes="32x32" />
		<link rel="icon" type="image/png" href="favicon-16x16.png?<?php echo rawurlencode($VERSION); ?>" sizes="16x16" />
		<link rel="manifest" href="manifest.json?<?php echo rawurlencode($VERSION); ?>" />
		<link rel="mask-icon" href="safari-pinned-tab.svg?<?php echo rawurlencode($VERSION); ?>" color="#ffcc00" />
		<link rel="shortcut icon" href="favicon.ico">
		<meta name="msapplication-config" content="browserconfig.xml">
		<meta name="theme-color" content="#ffe57e" />
	</head>
	<body role="document">
		<nav class="navbar navbar-default navbar-static-top">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
					<span class="sr-only"><?php echo PrivateBin\i18n::_('Toggle navigation'); ?></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="reloadlink navbar-brand" href="/">
					<img alt="<?php echo PrivateBin\i18n::_('PrivateBin'); ?>" src="img/icon.svg" width="20" />
				</a>
			</div>
			<div id="navbar" class="navbar-collapse collapse">
				<ul class="nav navbar-nav">
					<li>
						<button id="sendbutton" type="button" class="hidden btn btn-primary navbar-btn">
							<span class="glyphicon glyphicon-upload" aria-hidden="true"></span> <?php echo PrivateBin\i18n::_('Send'); ?>
						</button><?php
if ($EXPIRECLONE): ?>
						<button id="clonebutton" type="button" class="hidden btn btn-default navbar-btn">
							<span class="glyphicon glyphicon-duplicate" aria-hidden="true"></span> <?php echo PrivateBin\i18n::_('Clone'); ?>
						</button><?php
endif; ?>
						<button id="rawtextbutton" type="button" class="hidden btn btn-default navbar-btn">
							<span class="glyphicon glyphicon-text-background" aria-hidden="true"></span> <?php echo PrivateBin\i18n::_('Raw text'); ?>
						</button>
					</li>
					<li class="dropdown">
						<select id="pasteExpiration" name="pasteExpiration" class="hidden"><?php
foreach ($EXPIRE as $key => $value): ?>
							<option value="<?php echo $key; ?>"<?php
    if ($key == $EXPIREDEFAULT): ?> selected="selected"<?php
    endif; ?>><?php echo $value; ?></option><?php
endforeach; ?>
						</select>
						<a id="expiration" href="#" class="hidden dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo PrivateBin\i18n::_('Expires'); ?>: <span id="pasteExpirationDisplay"><?php echo $EXPIRE[$EXPIREDEFAULT]; ?></span> <span class="caret"></span></a>
						<ul class="dropdown-menu"><?php
foreach ($EXPIRE as $key => $value): ?>
							<li>
								<a href="#" onclick="$('#pasteExpiration').val('<?php echo $key; ?>');$('#pasteExpirationDisplay').text('<?php echo $value; ?>');return false;">
									<?php echo $value; ?>
								</a>
							</li><?php
endforeach; ?>
						</ul>
					</li>
					<li>
						<div id="burnafterreadingoption" class="navbar-text checkbox hidden">
							<label>
								<input type="checkbox" id="burnafterreading" name="burnafterreading" <?php
if ($BURNAFTERREADINGSELECTED): ?> checked="checked"<?php
endif; ?> />
								<?php echo PrivateBin\i18n::_('Burn after reading'); ?>
							</label>
						</div>
					</li><?php
if ($DISCUSSION): ?>
					<li>
						<div id="opendisc" class="navbar-text checkbox hidden">
							<label>
								<input type="checkbox" id="opendiscussion" name="opendiscussion" <?php
    if ($OPENDISCUSSION): ?> checked="checked"<?php
    endif; ?> />
								<?php echo PrivateBin\i18n::_('Open discussion'); ?>
						 	</label>
						</div>
					</li><?php
endif;
if ($PASSWORD): ?>
					<li>
						<div id="password" class="navbar-form hidden">
							<input type="password" id="passwordinput" placeholder="<?php echo PrivateBin\i18n::_('Password (recommended)'); ?>" class="form-control" size="19" />
						</div>
					</li><?php
endif;
if ($FILEUPLOAD): ?>
					<li id="attach" class="hidden dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo PrivateBin\i18n::_('Attach a file'); ?> <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li id="filewrap">
								<div>
									<input type="file" id="file" name="file" />
								</div>
							</li>
							<li>
								<a id="fileremovebutton"  href="#">
									<?php echo PrivateBin\i18n::_('Remove attachment'); ?>
								</a>
							</li>
						</ul>
					</li><?php
endif; ?>
					<li class="dropdown">
						<select id="pasteFormatter" name="pasteFormatter" class="hidden"><?php
foreach ($FORMATTER as $key => $value): ?>
							<option value="<?php echo $key; ?>"<?php
    if ($key == $FORMATTERDEFAULT): ?> selected="selected"<?php
    endif; ?>><?php echo $value; ?></option><?php
endforeach; ?>
						</select>
						<a id="formatter" href="#" class="hidden dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo PrivateBin\i18n::_('Format'); ?>: <span id="pasteFormatterDisplay"><?php echo $FORMATTER[$FORMATTERDEFAULT]; ?></span> <span class="caret"></span></a>
						<ul class="dropdown-menu"><?php
foreach ($FORMATTER as $key => $value): ?>
							<li>
								<a href="#" onclick="$('#pasteFormatter').val('<?php echo $key; ?>');$('#pasteFormatterDisplay').text('<?php echo $value; ?>');return false;">
									<?php echo $value; ?>
								</a>
							</li><?php
endforeach; ?>
						</ul>
					</li>
				</ul>
				<ul class="nav navbar-nav pull-right"><?php
if (strlen($LANGUAGESELECTION)): ?>
					<li id="language" class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="glyphicon glyphicon-flag" aria-hidden="true"></span> <?php echo $LANGUAGES[$LANGUAGESELECTION][0]; ?> <span class="caret"></span></a>
						<ul class="dropdown-menu"><?php
    foreach ($LANGUAGES as $key => $value): ?>
							<li>
								<a href="#" class="reloadlink" onclick="document.cookie='lang=<?php echo $key; ?>';">
									<?php echo $value[0]; ?> (<?php echo $value[1]; ?>)
								</a>
							</li><?php
    endforeach; ?>
						</ul>
					</li><?php
endif; ?>
					<li>
						<button id="newbutton" type="button" class="reloadlink hidden btn btn-default navbar-btn">
							<span class="glyphicon glyphicon-file" aria-hidden="true"></span> <?php echo PrivateBin\i18n::_('New'); ?>
						</button>
					</li>
				</ul>
			</div>
		</nav>
		<header class="container"><?php
if (strlen($NOTICE)): ?>
			<div role="alert" class="alert alert-info">
				<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> <?php echo htmlspecialchars($NOTICE); ?>
			</div><?php
endif; ?>
			<div id="remainingtime" role="alert" class="hidden alert alert-info">
				<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
			</div><?php
if ($FILEUPLOAD): ?>
			<div id="attachment" role="alert" class="hidden alert alert-info">
				<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> <a><?php echo PrivateBin\i18n::_('Download attachment'); ?></a> <span id="clonedfile" class="hidden"><?php echo PrivateBin\i18n::_('Cloned file attached.'); ?></span>
			</div><?php
endif;
if (strlen($STATUS)): ?>
			<div id="status" role="alert" class="alert alert-success">
				<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> <?php echo htmlspecialchars($STATUS); ?>
			</div><?php
endif; ?>
			<div id="errormessage" role="alert" class="<?php
if (!strlen($ERROR)): ?>hidden <?php
endif; ?>alert alert-danger"><span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <?php echo htmlspecialchars($ERROR); ?></div>
			<noscript><div id="noscript" role="alert" class="nonworking alert alert-warning"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> <?php echo PrivateBin\i18n::_('Javascript is required for PrivateBin to work.<br />Sorry for the inconvenience.'); ?></div></noscript>
			<div id="oldienotice" role="alert" class="hidden nonworking alert alert-danger"><span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <?php echo PrivateBin\i18n::_('PrivateBin requires a modern browser to work.'); ?></div>
			<div id="ienotice" role="alert" class="hidden alert alert-warning"><span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span> <?php echo PrivateBin\i18n::_('Still using Internet Explorer? Do yourself a favor, switch to a modern browser:'); ?>
				<a href="https://www.mozilla.org/firefox/">Firefox</a>,
				<a href="https://www.opera.com/">Opera</a>,
				<a href="https://www.google.com/chrome">Chrome</a>,
				<a href="https://www.apple.com/safari">Safari</a>...
			</div>
			<div id="pasteresult" role="alert" class="hidden alert alert-success">
				<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
				<div id="deletelink"></div>
				<div id="pastelink"><?php
if (strlen($URLSHORTENER)): ?>
					<button id="shortenbutton" data-shortener="<?php echo htmlspecialchars($URLSHORTENER); ?>" type="button" class="btn btn-primary">
						<span class="glyphicon glyphicon-send" aria-hidden="true"></span> <?php echo PrivateBin\i18n::_('Shorten URL'); ?>
					</button><?php
endif; ?>
				</div>
			</div>
			<ul id="preview" class="nav nav-tabs hidden">
				<li role="presentation" class="active"><a id="messageedit" href="#"><?php echo PrivateBin\i18n::_('Editor'); ?></a></li>
				<li role="presentation"><a id="messagepreview" href="#"><?php echo PrivateBin\i18n::_('Preview'); ?></a></li>
			</ul>
		</header>
		<section class="container">
			<article class="row">
				<div id="image" class="col-md-12 text-center hidden"></div>
				<div id="prettymessage" class="col-md-12 hidden">
					<pre id="prettyprint" class="col-md-12 prettyprint linenums:1"></pre>
				</div>
				<div id="cleartext" class="col-md-12 hidden"></div>
				<p class="col-md-12"><textarea id="message" name="message" cols="80" rows="25" class="form-control hidden"></textarea></p>
			</article>
		</section>
		<section class="container">
			<div id="discussion" class="hidden">
				<h4><?php echo PrivateBin\i18n::_('Discussion'); ?></h4>
				<div id="comments"></div>
			</div>
		</section>
		<footer class="container">
			<div class="row">
				<h4 class="col-md-5 col-xs-8"><?php echo PrivateBin\i18n::_('PrivateBin'); ?> <small>- <?php echo PrivateBin\i18n::_('Because ignorance is bliss'); ?></small></h4>
				<p class="col-md-1 col-xs-4 text-center"><?php echo $VERSION; ?></p>
				<p id="aboutbox" class="col-md-6 col-xs-12">
					<?php echo PrivateBin\i18n::_('PrivateBin is a minimalist, open source online pastebin where the server has zero knowledge of pasted data. Data is encrypted/decrypted <i>in the browser</i> using 256 bits AES. More information on the <a href="https://github.com/PrivateBin/PrivateBin/wiki">project page</a>.'); ?>
				</p>
			</div>
		</footer>
		<div id="cipherdata" class="hidden"><?php echo htmlspecialchars($CIPHERDATA, ENT_NOQUOTES); ?></div>
	</body>
</html>
