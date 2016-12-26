<?php
use PrivateBin\I18n;
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="robots" content="noindex" />
		<meta name="referrer" content="no-referrer">
		<title><?php echo I18n::_('PrivateBin'); ?></title>
		<link type="text/css" rel="stylesheet" href="css/bootstrap/bootstrap-3.3.5.css" />
		<link type="text/css" rel="stylesheet" href="css/bootstrap/bootstrap-theme-3.3.5.css" />
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
		<script type="text/javascript" src="js/jquery-3.1.1.js" integrity="sha512-U6K1YLIFUWcvuw5ucmMtT9HH4t0uz3M366qrF5y4vnyH6dgDzndlcGvH/Lz5k8NFh80SN95aJ5rqGZEdaQZ7ZQ==" crossorigin="anonymous"></script>
		<script type="text/javascript" src="js/sjcl-1.0.4.js" integrity="sha512-BqVQ8GgWfMCcdsDuP6Ggm1BV7+mmoWH3PC4UqcYpEKSdEq1rthy6NUsa6gu5sydewbi/ilI3E3ohdCxlPPF9ww==" crossorigin="anonymous"></script>
<?php
if ($ZEROBINCOMPATIBILITY):
?>
		<script type="text/javascript" src="js/base64-1.7.js" integrity="sha512-JdwsSP3GyHR+jaCkns9CL9NTt4JUJqm/BsODGmYhBcj5EAPKcHYh+OiMfyHbcDLECe17TL0hjXADFkusAqiYgA==" crossorigin="anonymous"></script>
<?php
else:
?>
		<script type="text/javascript" src="js/base64-2.1.9.js" integrity="sha512-rbqAby7hObftbEoGQzkhUbEh5YkUn2MtekTLs4btvo2oly4CZ3DxhJzEh0u/rNzS54tcJdqi5Ug1ruugEd2U1g==" crossorigin="anonymous"></script>
<?php
endif;
?>
		<script type="text/javascript" src="js/rawdeflate-0.5.js" integrity="sha512-tTdZ7qMr7tt5VQy4iCHu6/aGB12eRwbUy+AEI5rXntfsjcRfBeeqJloMsBU9FrGk1bIYLiuND/FhU42LO1bi0g==" crossorigin="anonymous"></script>
		<script type="text/javascript" src="js/rawinflate-0.3.js" integrity="sha512-g8uelGgJW9A/Z1tB6Izxab++oj5kdD7B4qC7DHwZkB6DGMXKyzx7v5mvap2HXueI2IIn08YlRYM56jwWdm2ucQ==" crossorigin="anonymous"></script>
		<script type="text/javascript" src="js/bootstrap-3.3.5.js" integrity="sha512-/W33QnLmSAP1fwINS9iXgB6s/VOIG9GVdIuIYaUtbSvKPMv5S08PtT3PqnT2WjwBgB8DFeDN2nqJroqQYF7SwQ==" crossorigin="anonymous"></script>
<?php
if ($SYNTAXHIGHLIGHTING):
?>
		<script type="text/javascript" src="js/prettify.js?<?php echo rawurlencode($VERSION); ?>" integrity="sha512-m8iHxoN+Fe12xxFwWNdY/TS4KoFntHp29qY0xUzBnPd0bkKMOR/dFhEdTWydpt0b/fIXyhB+znGYUvgjfJ2RzQ==" crossorigin="anonymous"></script>
<?php
endif;
if ($MARKDOWN):
?>
		<script type="text/javascript" src="js/showdown-1.4.1.js" integrity="sha512-Kbz1FIlDnqUJu/3yW8H8USzURA3JuUqSKRwz13lM4kWt6C0n6s4tjl81PCfnWtE4gBIzyj5uGePcfUyotk/icw==" crossorigin="anonymous"></script>
<?php
endif;
?>
		<script type="text/javascript" src="js/privatebin.js?<?php echo rawurlencode($VERSION); ?>" integrity="sha512-q/2ZUVaS+RVZEaIzIufFOAbhkq2/hYXix2f/Dt3+MxWouGt7vxB3rIU3jkn3f7VHNBnK/wL3KjKL+xZuaaKPVQ==" crossorigin="anonymous"></script>
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
		<div id="passwordmodal" class="modal fade" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-body">
						<form id="passwordform" role="form">
							<div class="form-group">
								<label for="passworddecrypt"><span class="glyphicon glyphicon-eye-open"></span> <?php echo I18n::_('Please enter the password for this paste:') ?></label>
								<input id="passworddecrypt" type="password" class="form-control" placeholder="<?php echo I18n::_('Enter password') ?>" autofocus>
							</div>
							<button type="submit" class="btn btn-success btn-block"><span class="glyphicon glyphicon-off"></span> <?php echo I18n::_('Decrypt') ?></button>
						</form>
					</div>
				</div>
			</div>
		</div>
		<nav class="navbar navbar-default navbar-static-top">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
					<span class="sr-only"><?php echo I18n::_('Toggle navigation'); ?></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="reloadlink navbar-brand" href="">
					<img alt="<?php echo I18n::_('PrivateBin'); ?>" src="img/icon.svg" width="38" />
				</a>
			</div>
			<div id="navbar" class="navbar-collapse collapse">
				<ul class="nav navbar-nav">
					<li>
						<button id="sendbutton" type="button" class="hidden btn btn-primary navbar-btn">
							<span class="glyphicon glyphicon-upload" aria-hidden="true"></span> <?php echo I18n::_('Send'), PHP_EOL; ?>
						</button>
<?php
if ($EXPIRECLONE):
?>
						<button id="clonebutton" type="button" class="hidden btn btn-default navbar-btn">
							<span class="glyphicon glyphicon-duplicate" aria-hidden="true"></span> <?php echo I18n::_('Clone'), PHP_EOL; ?>
						</button>
<?php
endif;
?>
						<button id="rawtextbutton" type="button" class="hidden btn btn-default navbar-btn">
							<span class="glyphicon glyphicon-text-background" aria-hidden="true"></span> <?php echo I18n::_('Raw text'), PHP_EOL; ?>
						</button>
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
						<div id="opendisc" class="navbar-text checkbox hidden">
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
if ($PASSWORD):
?>
					<li>
						<div id="password" class="navbar-form hidden">
							<input type="password" id="passwordinput" placeholder="<?php echo I18n::_('Password (recommended)'); ?>" class="form-control" size="19" />
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
							</li>
							<li>
								<a id="fileremovebutton"  href="#">
									<?php echo I18n::_('Remove attachment'), PHP_EOL; ?>
								</a>
							</li>
						</ul>
					</li>
<?php
endif;
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
					<li>
						<button id="newbutton" type="button" class="reloadlink hidden btn btn-default navbar-btn">
							<span class="glyphicon glyphicon-file" aria-hidden="true"></span> <?php echo I18n::_('New'), PHP_EOL; ?>
						</button>
					</li>
				</ul>
			</div>
		</nav>
		<header class="container">
<?php
if (strlen($NOTICE)):
?>
			<div role="alert" class="alert alert-info">
				<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> <?php echo htmlspecialchars($NOTICE), PHP_EOL; ?>
			</div>
<?php
endif;
?>
			<div id="remainingtime" role="alert" class="hidden alert alert-info">
				<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span>
			</div>
<?php
if ($FILEUPLOAD):
?>
			<div id="attachment" role="alert" class="hidden alert alert-info">
				<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> <a><?php echo I18n::_('Download attachment'); ?></a> <span id="clonedfile" class="hidden"><?php echo I18n::_('Cloned file attached.'); ?></span>
			</div>
<?php
endif;
if (strlen($STATUS)):
?>
			<div id="status" role="alert" class="alert alert-success">
				<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> <?php echo htmlspecialchars($STATUS), PHP_EOL; ?>
			</div>
<?php
endif;
?>
			<div id="errormessage" role="alert" class="<?php
if (!strlen($ERROR)):
?>hidden <?php
endif;
?>alert alert-danger"><span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <?php echo htmlspecialchars($ERROR); ?></div>
			<noscript><div id="noscript" role="alert" class="nonworking alert alert-warning"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> <?php echo I18n::_('Javascript is required for PrivateBin to work.<br />Sorry for the inconvenience.'); ?></div></noscript>
			<div id="oldienotice" role="alert" class="hidden nonworking alert alert-danger"><span class="glyphicon glyphicon-alert" aria-hidden="true"></span> <?php echo I18n::_('PrivateBin requires a modern browser to work.'); ?></div>
			<div id="ienotice" role="alert" class="hidden alert alert-warning"><span class="glyphicon glyphicon-question-sign" aria-hidden="true"></span> <?php echo I18n::_('Still using Internet Explorer? Do yourself a favor, switch to a modern browser:'), PHP_EOL; ?>
				<a href="https://www.mozilla.org/firefox/">Firefox</a>,
				<a href="https://www.opera.com/">Opera</a>,
				<a href="https://www.google.com/chrome">Chrome</a>,
				<a href="https://www.apple.com/safari">Safari</a>...
			</div>
			<div id="pasteresult" role="alert" class="hidden alert alert-success">
				<span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
				<div id="deletelink"></div>
				<div id="pastelink">
<?php
if (strlen($URLSHORTENER)):
?>
					<button id="shortenbutton" data-shortener="<?php echo htmlspecialchars($URLSHORTENER); ?>" type="button" class="btn btn-primary">
						<span class="glyphicon glyphicon-send" aria-hidden="true"></span> <?php echo I18n::_('Shorten URL'), PHP_EOL; ?>
					</button>
<?php
endif;
?>
				</div>
			</div>
			<ul id="preview" class="nav nav-tabs hidden">
				<li role="presentation" class="active"><a id="messageedit" href="#"><?php echo I18n::_('Editor'); ?></a></li>
				<li role="presentation"><a id="messagepreview" href="#"><?php echo I18n::_('Preview'); ?></a></li>
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
				<h4><?php echo I18n::_('Discussion'); ?></h4>
				<div id="comments"></div>
			</div>
		</section>
        <section class="container">
			<div id="noscript" role="alert" class="nonworking alert alert-info noscript-hide"><span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true">
				<span> <?php echo I18n::_('Loading…'); ?></span><br>
				<span class="small"><?php echo I18n::_('In case this message never disappears please have a look at <a href="https://github.com/PrivateBin/PrivateBin/wiki/FAQ#why-does-not-the-loading-message-go-away">this FAQ for information to troubleshoot</a>.'); ?></span>
			</div>
		</section>
		<footer class="container">
			<div class="row">
				<h4 class="col-md-5 col-xs-8"><?php echo I18n::_('PrivateBin'); ?> <small>- <?php echo I18n::_('Because ignorance is bliss'); ?></small></h4>
				<p class="col-md-1 col-xs-4 text-center"><?php echo $VERSION; ?></p>
				<p id="aboutbox" class="col-md-6 col-xs-12">
					<?php echo I18n::_('PrivateBin is a minimalist, open source online pastebin where the server has zero knowledge of pasted data. Data is encrypted/decrypted <i>in the browser</i> using 256 bits AES. More information on the <a href="https://privatebin.info/">project page</a>.'), PHP_EOL; ?>
				</p>
			</div>
		</footer>
		<div id="cipherdata" class="hidden"><?php echo htmlspecialchars($CIPHERDATA, ENT_NOQUOTES); ?></div>
	</body>
</html>
