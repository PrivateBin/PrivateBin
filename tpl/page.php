<?php
use PrivateBin\I18n;
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="robots" content="noindex" />
		<meta name="referrer" content="no-referrer">
		<meta name="google" content="notranslate">
		<title><?php echo I18n::_($NAME); ?></title>
		<link type="text/css" rel="stylesheet" href="css/privatebin.css?<?php echo rawurlencode($VERSION); ?>" />
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
		<script type="text/javascript" data-cfasync="false" src="js/jquery-3.3.1.js" integrity="sha512-+NqPlbbtM1QqiK8ZAo4Yrj2c4lNQoGv8P79DPtKzj++l5jnN39rHA/xsqn8zE9l0uSoxaCdrOgFs6yjyfbBxSg==" crossorigin="anonymous"></script>
		<script type="text/javascript" data-cfasync="false" src="js/sjcl-1.0.7.js" integrity="sha512-J2eNenPwyfXkMVNMFz9Q54kKfYi5AA3mQWpNgtjSJzsKHtpbhUt/7bvcjGwwmzE8ZUVWMI/ndagIX1lG+SfxGA==" crossorigin="anonymous"></script>
<?php
if ($QRCODE):
?>
		<script async type="text/javascript" data-cfasync="false" src="js/kjua-0.1.2.js" integrity="sha512-hmvfOhcr4J8bjQ2GuNVzfSbuulv72wgQCJpgnXc2+cCHKqvYo8pK2nc0Q4Esem2973zo1radyIMTEkt+xJlhBA==" crossorigin="anonymous"></script>
<?php
endif;
if ($ZEROBINCOMPATIBILITY):
?>
		<script type="text/javascript" data-cfasync="false" src="js/base64-1.7.js" integrity="sha512-JdwsSP3GyHR+jaCkns9CL9NTt4JUJqm/BsODGmYhBcj5EAPKcHYh+OiMfyHbcDLECe17TL0hjXADFkusAqiYgA==" crossorigin="anonymous"></script>
<?php
else:
?>
		<script type="text/javascript" data-cfasync="false" src="js/base64-2.4.5.js" integrity="sha512-YINE6agO8ZrYuzlrZZwQJTu0uqURJDxD4gjsfZ6mV4fP2gW5j8giNJ734iyJVTBrnF2XMiUBM/DSi7ON1V5RMQ==" crossorigin="anonymous"></script>
<?php
endif;
?>
		<script type="text/javascript" data-cfasync="false" src="js/rawdeflate-0.5.js" integrity="sha512-tTdZ7qMr7tt5VQy4iCHu6/aGB12eRwbUy+AEI5rXntfsjcRfBeeqJloMsBU9FrGk1bIYLiuND/FhU42LO1bi0g==" crossorigin="anonymous"></script>
		<script type="text/javascript" data-cfasync="false" src="js/rawinflate-0.3.js" integrity="sha512-g8uelGgJW9A/Z1tB6Izxab++oj5kdD7B4qC7DHwZkB6DGMXKyzx7v5mvap2HXueI2IIn08YlRYM56jwWdm2ucQ==" crossorigin="anonymous"></script>
<?php
if ($SYNTAXHIGHLIGHTING):
?>
		<script type="text/javascript" data-cfasync="false" src="js/prettify.js?<?php echo rawurlencode($VERSION); ?>" integrity="sha512-puO0Ogy++IoA2Pb9IjSxV1n4+kQkKXYAEUtVzfZpQepyDPyXk8hokiYDS7ybMogYlyyEIwMLpZqVhCkARQWLMg==" crossorigin="anonymous"></script>
<?php
endif;
if ($MARKDOWN):
?>
		<script type="text/javascript" data-cfasync="false" src="js/showdown-1.8.6.js" integrity="sha512-YFg2sBCGT00I6X5KzgCLP4VqRlmPMRhkVvJS9oJKk5LxiUzzcjzV5m4fNf6mQMctLrhgS5LFKiFF3vzIuXbjAw==" crossorigin="anonymous"></script>
<?php
endif;
?>
		<script type="text/javascript" data-cfasync="false" src="js/purify-1.0.7.js" integrity="sha512-VnKJHLosO8z2ojNvWk9BEKYqnhZyWK9rM90FgZUUEp/PRnUqR5OLLKE0a3BkVmn7YgB7LXRrjHgFHQYKd6DAIA==" crossorigin="anonymous"></script>
		<script type="text/javascript" data-cfasync="false" src="js/privatebin.js?<?php echo rawurlencode($VERSION); ?>" integrity="sha512-gFuGN7PD3lRe5ZTe/S2NfugOrjqNWYgEwBmb3Grk2EVckz1UbqVPs8+PQp6SmQ0preKNpXC50omnwGh4MPkoRg==" crossorigin="anonymous"></script>
		<!--[if lt IE 10]>
		<style type="text/css">body {padding-left:60px;padding-right:60px;} #ienotice {display:block;} #oldienotice {display:block;}</style>
		<![endif]-->
		<link rel="apple-touch-icon" href="img/apple-touch-icon.png?<?php echo rawurlencode($VERSION); ?>" sizes="180x180" />
		<link rel="icon" type="image/png" href="img/favicon-32x32.png?<?php echo rawurlencode($VERSION); ?>" sizes="32x32" />
		<link rel="icon" type="image/png" href="img/favicon-16x16.png?<?php echo rawurlencode($VERSION); ?>" sizes="16x16" />
		<link rel="manifest" href="manifest.json?<?php echo rawurlencode($VERSION); ?>" />
		<link rel="mask-icon" href="img/safari-pinned-tab.svg?<?php echo rawurlencode($VERSION); ?>" color="#ffcc00" />
		<link rel="shortcut icon" href="img/favicon.ico">
		<meta name="msapplication-config" content="browserconfig.xml">
		<meta name="theme-color" content="#ffe57e" />
	</head>
	<body>
		<header>
			<div id="aboutbox">
				<?php echo I18n::_('%s is a minimalist, open source online pastebin where the server has zero knowledge of pasted data. Data is encrypted/decrypted <i>in the browser</i> using 256 bits AES. More information on the <a href="https://privatebin.info/">project page</a>.', I18n::_($NAME)); ?><br />
<?php
if (strlen($NOTICE)):
?>
				<span class="blink">▶</span> <?php echo htmlspecialchars($NOTICE);
endif;
?>
			</div>
			<h1 class="title reloadlink"><?php echo I18n::_($NAME); ?></h1><br />
			<h2 class="title"><?php echo I18n::_('Because ignorance is bliss'); ?></h2><br />
			<h3 class="title"><?php echo $VERSION; ?></h3>
			<noscript><div id="noscript" class="nonworking"><?php echo I18n::_('JavaScript is required for %s to work.<br />Sorry for the inconvenience.', I18n::_($NAME)); ?></div></noscript>
			<div id="oldienotice" class="nonworking"><?php echo I18n::_('%s requires a modern browser to work.', I18n::_($NAME)); ?></div>
			<div id="ienotice"><?php echo I18n::_('Still using Internet Explorer? Do yourself a favor, switch to a modern browser:'), PHP_EOL; ?>
				<a href="https://www.mozilla.org/firefox/">Firefox</a>,
				<a href="https://www.opera.com/">Opera</a>,
				<a href="https://www.google.com/chrome">Chrome</a>…
			</div>
		</header>
		<section>
			<article>
				<div id="loadingindicator" class="hidden"><?php echo I18n::_('Loading…'); ?></div>
				<div id="status"><?php echo htmlspecialchars($STATUS); ?></div>
				<div id="errormessage" class="hidden"><?php echo htmlspecialchars($ERROR); ?></div>
				<div id="toolbar">
					<button id="newbutton" class="reloadlink hidden"><img src="img/icon_new.png" width="11" height="15" alt="" /><?php echo I18n::_('New'); ?></button>
					<button id="retrybutton" class="reloadlink hidden"><?php echo I18n::_('Retry'), PHP_EOL; ?></button>
					<button id="sendbutton" class="hidden"><img src="img/icon_send.png" width="18" height="15" alt="" /><?php echo I18n::_('Send'); ?></button>
					<button id="clonebutton" class="hidden"><img src="img/icon_clone.png" width="15" height="17" alt="" /><?php echo I18n::_('Clone'); ?></button>
					<button id="rawtextbutton" class="hidden"><img src="img/icon_raw.png" width="15" height="15" alt="" /><?php echo I18n::_('Raw text'); ?></button>
<?php
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
if (strlen($LANGUAGESELECTION)):
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
if (strlen($URLSHORTENER)):
?>
					<button id="shortenbutton" data-shortener="<?php echo htmlspecialchars($URLSHORTENER); ?>"><img src="img/icon_shorten.png" width="13" height="15" /><?php echo I18n::_('Shorten URL'); ?></button>
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
				<article id="commenttemplate" class="comment"><div class="commentmeta"><span class="nickname">name</span><span class="commentdate">0000-00-00</span></div><div class="commentdata">c</div><button class="btn btn-default btn-sm"><?php echo I18n::_('Reply'); ?></button></article>
				<div id="commenttailtemplate" class="comment"><button class="btn btn-default btn-sm"><?php echo I18n::_('Add comment'); ?></button></div>
				<div id="replytemplate" class="reply hidden"><input type="text" id="nickname" class="form-control" title="<?php echo I18n::_('Optional nickname…'); ?>" placeholder="<?php echo I18n::_('Optional nickname…'); ?>" /><textarea id="replymessage" class="replymessage form-control" cols="80" rows="7"></textarea><br /><div id="replystatus" role="alert" class="statusmessage hidden alert"><span class="glyphicon" aria-hidden="true"></span> </div><button id="replybutton" class="btn btn-default btn-sm"><?php echo I18n::_('Post comment'); ?></button></div>
			</div>
		</div>
<?php
endif;
?>
		<section class="container">
			<div id="noscript" role="alert" class="nonworking alert alert-info noscript-hide"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true">
				<span> <?php echo I18n::_('Loading…'); ?></span><br>
				<span class="small"><?php echo I18n::_('In case this message never disappears please have a look at <a href="https://github.com/PrivateBin/PrivateBin/wiki/FAQ#why-does-not-the-loading-message-go-away">this FAQ for information to troubleshoot</a>.'); ?></span>
			</div>
		</section>
	</body>
</html>
