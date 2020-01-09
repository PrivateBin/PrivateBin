;<?php http_response_code(403); /*
; config file for PrivateBin
;
; An explanation of each setting can be find online at https://github.com/PrivateBin/PrivateBin/wiki/Configuration.

[main]
; (optional) set a project name to be displayed on the website
; name = "PrivateBin"

; enable or disable the discussion feature, defaults to true
discussion = true

; preselect the discussion feature, defaults to false
opendiscussion = false

; enable or disable the password feature, defaults to true
password = true

; enable or disable the file upload feature, defaults to false
fileupload = false

; preselect the burn-after-reading feature, defaults to false
burnafterreadingselected = false

; which display mode to preselect by default, defaults to "plaintext"
; make sure the value exists in [formatter_options]
defaultformatter = "plaintext"

; (optional) set a syntax highlighting theme, as found in css/prettify/
; syntaxhighlightingtheme = "sons-of-obsidian"

; size limit per paste or comment in bytes, defaults to 10 Mebibytes
sizelimit = 10485760

; template to include, default is "bootstrap" (tpl/bootstrap.php)
template = "bootstrap"

; (optional) notice to display
; notice = "Note: This is a test service: Data may be deleted anytime. Kittens will die if you abuse this service."

; by default PrivateBin will guess the visitors language based on the browsers
; settings. Optionally you can enable the language selection menu, which uses
; a session cookie to store the choice until the browser is closed.
languageselection = false

; set the language your installs defaults to, defaults to English
; if this is set and language selection is disabled, this will be the only language
; languagedefault = "en"

; (optional) URL shortener address to offer after a new paste is created
; it is suggested to only use this with self-hosted shorteners as this will leak
; the pastes encryption key
; urlshortener = "https://shortener.example.com/api?link="

; (optional) Let users create a QR code for sharing the paste URL with one click.
; It works both when a new paste is created and when you view a paste.
; qrcode = true

; (optional) IP based icons are a weak mechanism to detect if a comment was from
; a different user when the same username was used in a comment. It might be
; used to get the IP of a non anonymous comment poster if the server salt is
; leaked and a SHA256 HMAC rainbow table is generated for all (relevant) IPs.
; Can be set to one these values: "none" / "vizhash" / "identicon" (default).
; icon = "none"

; Content Security Policy headers allow a website to restrict what sources are
; allowed to be accessed in its context. You need to change this if you added
; custom scripts from third-party domains to your templates, e.g. tracking
; scripts or run your site behind certain DDoS-protection services.
; Check the documentation at https://content-security-policy.com/
; Notes:
; - If you use a bootstrap theme, you can remove the allow-popups from the
;   sandbox restrictions.
; - By default this disallows to load images from third-party servers, e.g. when
;   they are embedded in pastes. If you wish to allow that, you can adjust the
;   policy here. See https://github.com/PrivateBin/PrivateBin/wiki/FAQ#why-does-not-it-load-embedded-images
;   for details.
; - The 'unsafe-eval' is used in two cases; to check if the browser supports
;   async functions and display an error if not and for Chrome to enable
;   webassembly support (used for zlib compression). You can remove it if Chrome
;   doesn't need to be supported and old browsers don't need to be warned.
; cspheader = "default-src 'none'; manifest-src 'self'; connect-src * blob:; script-src 'self' 'unsafe-eval'; style-src 'self'; font-src 'self'; img-src 'self' data: blob:; media-src blob:; object-src blob:; sandbox allow-same-origin allow-scripts allow-forms allow-popups allow-modals"

; stay compatible with PrivateBin Alpha 0.19, less secure
; if enabled will use base64.js version 1.7 instead of 2.1.9 and sha1 instead of
; sha256 in HMAC for the deletion token
; zerobincompatibility = false

; Enable or disable the warning message when the site is served over an insecure
; connection (insecure HTTP instead of HTTPS), defaults to true.
; Secure transport methods like Tor and I2P domains are automatically whitelisted.
; It is **strongly discouraged** to disable this.
; See https://github.com/PrivateBin/PrivateBin/wiki/FAQ#why-does-it-show-me-an-error-about-an-insecure-connection for more information.
; httpwarning = true

; Pick compression algorithm or disable it. Only applies to pastes/comments
; created after changing the setting.
; Can be set to one these values: "none" / "zlib" (default).
; compression = "zlib"

[expire]
; expire value that is selected per default
; make sure the value exists in [expire_options]
default = "1week"

[expire_options]
; Set each one of these to the number of seconds in the expiration period,
; or 0 if it should never expire
5min = 300
10min = 600
1hour = 3600
1day = 86400
1week = 604800
; Well this is not *exactly* one month, it's 30 days:
1month = 2592000
1year = 31536000
never = 0

[formatter_options]
; Set available formatters, their order and their labels
plaintext = "Plain Text"
syntaxhighlighting = "Source Code"
markdown = "Markdown"

[traffic]
; time limit between calls from the same IP address in seconds
; Set this to 0 to disable rate limiting.
limit = 10

; (optional) if your website runs behind a reverse proxy or load balancer,
; set the HTTP header containing the visitors IP address, i.e. X_FORWARDED_FOR
; header = "X_FORWARDED_FOR"

; directory to store the traffic limits in
dir = PATH "data"

[purge]
; minimum time limit between two purgings of expired pastes, it is only
; triggered when pastes are created
; Set this to 0 to run a purge every time a paste is created.
limit = 300

; maximum amount of expired pastes to delete in one purge
; Set this to 0 to disable purging. Set it higher, if you are running a large
; site
batchsize = 10

; directory to store the purge limit in
dir = PATH "data"

[model]
; name of data model class to load and directory for storage
; the default model "Filesystem" stores everything in the filesystem
class = Filesystem
[model_options]
dir = PATH "data"

;[model]
; example of DB configuration for MySQL
;class = Database
;[model_options]
;dsn = "mysql:host=localhost;dbname=privatebin;charset=UTF8"
;tbl = "privatebin_"	; table prefix
;usr = "privatebin"
;pwd = "Z3r0P4ss"
;opt[12] = true	  ; PDO::ATTR_PERSISTENT

;[model]
; example of DB configuration for SQLite
;class = Database
;[model_options]
;dsn = "sqlite:" PATH "data/db.sq3"
;usr = null
;pwd = null
;opt[12] = true	; PDO::ATTR_PERSISTENT
