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
	</head>
	<body>
<?php
if (empty($ERROR)) :
?>
		<p><?php echo I18n::_('Your paste is <a id="pasteurl" href="%s">%s</a> <span id="copyhint">(Hit [Ctrl]+[c] to copy)</span>', $SHORTURL, $SHORTURL); ?></p>
<?php
else:
?>
		<div id="errormessage">
			<p><?php echo I18n::_('Could not create paste: %s', $ERROR); ?></p>
		</div>
<?php
endif;
?>
	</body>
</html>
