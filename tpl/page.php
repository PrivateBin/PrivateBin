<?php declare(strict_types=1);
use PrivateBin\I18n;
?><!DOCTYPE html>
<html lang="<?php echo I18n::getLanguage(); ?>"<?php echo I18n::isRtl() ? ' dir="rtl"' : ''; ?>>
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="Content-Security-Policy" content="<?php echo I18n::encode($CSPHEADER); ?>">
		<meta name="robots" content="noindex" />
		<meta name="google" content="notranslate">
		<title><?php echo I18n::_($NAME); ?></title>
		<link type="text/css" rel="stylesheet" href="css/privatebin.css?<?php echo rawurlencode($VERSION); ?>" />
<?php
if ($SYNTAXHIGHLIGHTING):
?>
		<link type="text/css" rel="stylesheet" href="css/prettify/prettify.css?<?php echo rawurlencode($VERSION); ?>" />
<?php
    if (!empty($SYNTAXHIGHLIGHTINGTHEME)):
?>
		<link type="text/css" rel="stylesheet" href="css/prettify/<?php echo rawurlencode($SYNTAXHIGHLIGHTINGTHEME); ?>.css?<?php echo rawurlencode($VERSION); ?>" />
<?php
    endif;
endif;
?>
		<?php $this->_scriptTag('js/jquery-3.7.1.js', 'defer'); ?>
<?php
if ($QRCODE):
?>
		<?php $this->_scriptTag('js/kjua-0.9.0.js', 'async'); ?>
<?php
endif;
if ($ZEROBINCOMPATIBILITY):
?>
		<?php $this->_scriptTag('js/base64-1.7.js', 'async'); ?>
<?php
endif;
?>
		<?php $this->_scriptTag('js/zlib-1.3.1.js', 'async'); ?>
		<?php $this->_scriptTag('js/base-x-4.0.0.js', 'async'); ?>
		<?php $this->_scriptTag('js/rawinflate-0.3.js', 'async'); ?>
<?php
if ($SYNTAXHIGHLIGHTING):
?>
		<?php $this->_scriptTag('js/prettify.js', 'async'); ?>
<?php
endif;
if ($MARKDOWN):
?>
		<?php $this->_scriptTag('js/showdown-2.1.0.js', 'async'); ?>
<?php
endif;
?>
		<?php $this->_scriptTag('js/purify-3.1.6.js', 'async'); ?>
		<?php $this->_scriptTag('js/legacy.js', 'async'); ?>
		<?php $this->_scriptTag('js/privatebin.js', 'defer'); ?>
		<!-- icon -->
		<link rel="apple-touch-icon" href="img/apple-touch-icon.png?<?php echo rawurlencode($VERSION); ?>" sizes="180x180" />
		<link rel="icon" type="image/png" href="img/favicon-32x32.png?<?php echo rawurlencode($VERSION); ?>" sizes="32x32" />
		<link rel="icon" type="image/png" href="img/favicon-16x16.png?<?php echo rawurlencode($VERSION); ?>" sizes="16x16" />
		<link rel="manifest" href="manifest.json?<?php echo rawurlencode($VERSION); ?>" />
		<link rel="mask-icon" href="img/safari-pinned-tab.svg?<?php echo rawurlencode($VERSION); ?>" color="#ffcc00" />
		<link rel="shortcut icon" href="img/favicon.ico">
		<meta name="msapplication-config" content="browserconfig.xml">
		<meta name="theme-color" content="#ffe57e" />
		<!-- Twitter/social media cards -->
		<meta name="twitter:card" content="summary" />
		<meta name="twitter:title" content="<?php echo I18n::_('Encrypted note on %s', I18n::_($NAME)) ?>" />
		<meta name="twitter:description" content="<?php echo I18n::_('Visit this link to see the note. Giving the URL to anyone allows them to access the note, too.') ?>" />
		<meta name="twitter:image" content="img/apple-touch-icon.png?<?php echo rawurlencode($VERSION); ?>" />
		<meta property="og:title" content="<?php echo I18n::_($NAME); ?>" />
		<meta property="og:site_name" content="<?php echo I18n::_($NAME); ?>" />
		<meta property="og:description" content="<?php echo I18n::_('Visit this link to see the note. Giving the URL to anyone allows them to access the note, too.') ?>" />
		<meta property="og:image" content="img/apple-touch-icon.png?<?php echo rawurlencode($VERSION); ?>" />
		<meta property="og:image:type" content="image/png" />
		<meta property="og:image:width" content="180" />
		<meta property="og:image:height" content="180" />
	</head>
	<body data-compression="<?php echo rawurlencode($COMPRESSION); ?>">
		<header>
			<div id="aboutbox">
				<?php echo sprintf(
                    I18n::_('%s is a minimalist, open source online pastebin where the server has zero knowledge of pasted data. Data is encrypted/decrypted %sin the browser%s using 256 bits AES.',
                        I18n::_($NAME),
                        '%s', '%s'
                    ),
                    '<i>', '</i>'), ' ', $INFO;
                ?>
				<br />
<?php
if (!empty($NOTICE)):
?>
				<span class="blink">▶</span> <?php echo I18n::encode($NOTICE);
endif;
?>
			</div>
			<h1 class="title reloadlink"><?php echo I18n::_($NAME); ?></h1><br />
			<h2 class="title"><?php echo I18n::_('Because ignorance is bliss'); ?></h2><br />
			<h3 class="title"><?php echo $VERSION; ?></h3>
			<noscript><div id="noscript" class="nonworking"><?php echo I18n::_('JavaScript is required for %s to work. Sorry for the inconvenience.', I18n::_($NAME)); ?></div></noscript>
			<div id="oldnotice" class="nonworking hidden">
				<?php echo I18n::_('%s requires a modern browser to work.', I18n::_($NAME)), PHP_EOL; ?>
				<a href="https://www.mozilla.org/firefox/">Firefox</a>,
				<a href="https://www.opera.com/">Opera</a>,
				<a href="https://www.google.com/chrome">Chrome</a>…<br />
				<span class="small"><?php echo I18n::_('For more information <a href="%s">see this FAQ entry</a>.', 'https://github.com/PrivateBin/PrivateBin/wiki/FAQ#why-does-it-show-me-the-error-privatebin-requires-a-modern-browser-to-work'); ?></span>
			</div>
<?php
if ($HTTPWARNING):
?>
			<div id="httpnotice" class="errorMessage hidden">
				<?php echo I18n::_('This website is using an insecure connection! Please only use it for testing.'); ?><br />
				<span class="small"><?php echo I18n::_('For more information <a href="%s">see this FAQ entry</a>.', 'https://github.com/PrivateBin/PrivateBin/wiki/FAQ#why-does-it-show-me-an-error-about-an-insecure-connection'); ?></span>
			</div>
			<div id="insecurecontextnotice" class="errorMessage hidden">
				<?php echo I18n::_('Your browser may require an HTTPS connection to support the WebCrypto API. Try <a href="%s">switching to HTTPS</a>.', $HTTPSLINK); ?>
			</div>
<?php
endif;
?>
		</header>
		<section>
			<article>
				<div id="loadingindicator" class="hidden"><?php echo I18n::_('Loading…'); ?></div>
				<div id="status"><?php echo I18n::encode($STATUS); ?></div>
				<div id="errormessage" class="hidden"><?php echo I18n::encode($ERROR); ?></div>
				<div id="toolbar">
					<button id="newbutton" class="reloadlink hidden"><img src="img/icon_new.png" width="11" height="15" alt="" /><?php echo I18n::_('New'); ?></button>
					<button id="retrybutton" class="reloadlink hidden"><?php echo I18n::_('Retry'), PHP_EOL; ?></button>
					<button id="sendbutton" class="hidden"><img src="img/icon_send.png" width="18" height="15" alt="" /><?php echo I18n::_('Create'); ?></button>
					<button id="clonebutton" class="hidden"><img src="img/icon_clone.png" width="15" height="17" alt="" /><?php echo I18n::_('Clone'); ?></button>
					<button id="rawtextbutton" class="hidden"><img src="img/icon_raw.png" width="15" height="15" alt="" /><?php echo I18n::_('Raw text'); ?></button>
					<button id="downloadtextbutton" class="hidden"><?php echo I18n::_('Save paste'), PHP_EOL; ?></button>
<?php
if ($EMAIL):
?>

					<button id="emaillink" class="hidden"><img src="img/icon_email.png" width="15" height="15" alt="" /><?php echo I18n::_('Email'); ?></button>
<?php
endif;
if ($QRCODE):
?>
					<button id="qrcodelink" class="hidden"><img src="img/icon_qr.png" width="15" height="15" alt="" /><?php echo I18n::_('QR code'); ?></button>
<?php
endif;
?>
					<div id="expiration" class="hidden button"><?php echo I18n::_('Expires'); ?>:
						<select id="pasteExpiration" name="pasteExpiration">
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
					</div>
					<div id="remainingtime" class="hidden"></div>
					<div id="burnafterreadingoption" class="button hidden">
						<input type="checkbox" id="burnafterreading" name="burnafterreading"<?php
if ($BURNAFTERREADINGSELECTED):
?> checked="checked"<?php
endif;
?> />
						<label for="burnafterreading"><?php echo I18n::_('Burn after reading'); ?></label>
					</div>
<?php
if ($DISCUSSION):
?>
					<div id="opendiscussionoption" class="button hidden">
						<input type="checkbox" id="opendiscussion" name="opendiscussion"<?php
    if ($OPENDISCUSSION):
?> checked="checked"<?php
    endif;
?> />
						<label for="opendiscussion"><?php echo I18n::_('Open discussion'); ?></label>
					</div>
<?php
endif;
if ($PASSWORD):
?>
					<div id="password" class="hidden">
						<input type="password" id="passwordinput" placeholder="<?php echo I18n::_('Password (recommended)'); ?>" size="32" />
					</div>
<?php
endif;
?>
					<div id="formatter" class="button hidden"><?php echo I18n::_('Format'); ?>:
						<select id="pasteFormatter" name="pasteFormatter">
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
					</div>
<?php
if (!empty($LANGUAGESELECTION)):
?>
					<div id="language" class="button">
						<select name="lang">
<?php
    foreach ($LANGUAGES as $key => $value):
?>
							<option data-lang="<?php echo $key; ?>" value="<?php echo $key; ?>"<?php
        if ($key == $LANGUAGESELECTION):
?> selected="selected"<?php
        endif;
?>><?php echo $value[0]; ?> (<?php echo $value[1]; ?>)</option>
<?php
    endforeach;
?>
						</select>
					</div>
<?php
endif;
?>
				</div>
<?php
if ($QRCODE):
?>
				<div id="qrcode-display"></div>
<?php
endif;
?>				<div id="pastesuccess" class="hidden">
					<div id="deletelink"></div>
					<div id="pastelink"></div>
<?php
if (!empty($URLSHORTENER)):
?>
					<button id="shortenbutton" data-shortener="<?php echo I18n::encode($URLSHORTENER); ?>"><img src="img/icon_shorten.png" width="13" height="15" /><?php echo I18n::_('Shorten URL'); ?></button>
<?php
endif;
?>
				</div>
<?php
if ($FILEUPLOAD):
?>
				<div id="attachment" class="hidden"><a><?php echo I18n::_('Download attachment'); ?></a></div>
				<div id="attach" class="hidden">
					<span id="clonedfile" class="hidden"><?php echo I18n::_('Cloned file attached.'); ?></span>
					<span id="filewrap"><?php echo I18n::_('Attach a file'); ?>: <input type="file" id="file" name="file" /></span>
					<span id="dragAndDropFileName" class="dragAndDropFile"><?php echo I18n::_('alternatively drag & drop a file or paste an image from the clipboard'); ?></span>
					<button id="fileremovebutton"><?php echo I18n::_('Remove attachment'); ?></button>
				</div>
<?php
endif;
?>
				<div id="preview" class="hidden">
					<button id="messageedit"><?php echo I18n::_('Editor'); ?></button>
					<button id="messagepreview"><?php echo I18n::_('Preview'); ?></button>
				</div>
				<div id="attachmentPreview" class="hidden"></div>
				<div id="prettymessage" class="hidden">
					<pre id="prettyprint" class="prettyprint linenums:1"></pre>
				</div>
				<div id="plaintext" class="hidden"></div>
				<textarea id="message" name="message" cols="80" rows="25" class="hidden"></textarea>
			</article>
		</section>
		<section>
			<div id="discussion" class="hidden">
				<h4 class="title"><?php echo I18n::_('Discussion'); ?></h4>
				<div id="commentcontainer"></div>
			</div>
		</section>
<?php
if ($DISCUSSION):
?>
		<div id="serverdata" class="hidden" aria-hidden="true">
			<div id="templates">
				<article id="commenttemplate" class="comment">
					<div class="commentmeta">
						<span class="nickname">name</span>
						<span class="commentdate">0000-00-00</span>
					</div>
					<div class="commentdata">c</div>
					<button class="btn btn-default btn-sm"><?php echo I18n::_('Reply'); ?></button>
				</article>
				<div id="commenttailtemplate" class="comment">
					<button class="btn btn-default btn-sm"><?php echo I18n::_('Add comment'); ?></button>
				</div>
				<div id="replytemplate" class="reply hidden">
					<input type="text" id="nickname" class="form-control" title="<?php echo I18n::_('Optional nickname…'); ?>" placeholder="<?php echo I18n::_('Optional nickname…'); ?>" />
					<textarea id="replymessage" class="replymessage form-control" cols="80" rows="7"></textarea><br />
					<div id="replystatus" role="alert" class="statusmessage hidden alert">
						<span class="glyphicon" aria-hidden="true"></span>
					</div>
					<button id="replybutton" class="btn btn-default btn-sm"><?php echo I18n::_('Post comment'); ?></button>
				</div>
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
		<section class="container">
			<div id="noscript" role="alert" class="nonworking alert alert-info noscript-hide"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true">
				<span> <?php echo I18n::_('Loading…'); ?></span><br>
				<span class="small"><?php echo I18n::_('In case this message never disappears please have a look at <a href="%s">this FAQ for information to troubleshoot</a>.', 'https://github.com/PrivateBin/PrivateBin/wiki/FAQ#why-does-the-loading-message-not-go-away'); ?></span>
			</div>
		</section>
	</body>
</html>
