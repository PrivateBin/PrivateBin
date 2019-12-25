# PrivateBin version history

  * **1.4 (not yet released)**
    * ADDED: Translation for Ukrainian (#533)
    * ADDED: Option to send a mail with the link, when creating a paste (#398)
    * ADDED: Add support for CONFIG_PATH environment variable (#552)
    * FIXED: Password disabling option (#527)
  * **1.3.1 (2019-09-22)**
    * ADDED: Translation for Bulgarian (#455)
    * CHANGED: Improved mobile UI - obscured send button and hard to click shortener button (#477)
    * CHANGED: Enhanced URL shortener integration (#479)
    * CHANGED: Improved file upload drag & drop UI (#317)
    * CHANGED: Increased default size limit from 2 to 10 MiB, switch data from BLOB to MEDIUMBLOB in MySQL (#458)
    * CHANGED: Upgrading libraries to: DOMpurify 2.0.1
    * FIXED: Enabling browsers without WASM to create pastes and read uncompressed ones (#454)
    * FIXED: Cloning related issues (#489, #491, #493, #494)
    * FIXED: Enable file operation only when editing (#497) 
    * FIXED: Clicking 'New' on a previously submitted paste does not blank address bar (#354)
    * FIXED: Clear address bar when create new paste from existing paste (#479)
    * FIXED: Discussion section not hiding when new/clone paste is clicked on (#484)
    * FIXED: Showdown.js error when posting svg qrcode (#485)
    * FIXED: Failed to handle the case where user cancelled attachment selection properly (#487)
    * FIXED: Displaying the appropriate errors in older browsers (#508)
  * **1.3 (2019-07-09)**
    * ADDED: Translation for Czech (#424)
    * ADDED: Threat modeled the application (#177)
    * ADDED: Made compression configurable (#38)
    * CHANGED: Minimum required PHP version is 5.5, due to a change in the identicon library
    * CHANGED: Minimum required browser versions are Firefox 54, Chrome 57, Opera 44, Safari 11, Edge 16, due to use of WebCrypto API, async/await, ES6 & WebAssembly features - all Internet Explorer versions are incompatible
    * CHANGED: JSON and encryption formats were changed to replace SJCL library by browser integrated WebCrypto API (#28, #74)
    * CHANGED: Replaced rawdeflate.js with zlib.wasm to resolve decompression failures and gain compatibility with standard deflate implementations (#193, #260, #328, #434, #440)
    * CHANGED: Increase PBKDF2 iterations to 100k (#350)
    * CHANGED: Replaced last use of MD5 with Fowler–Noll–Vo checksum which produces the exact length we need for the paste ID (#49)
    * CHANGED: Simplified some PHP code & renamed PrivateBin class into Controller, to make MVC pattern use more obvious (#342)
    * CHANGED: Upgrading libraries to: identicon 1.2.0, random_compat 2.0.18, jQuery 3.4.1, Showdown 1.9.0, DOMpurify 1.0.11 & kjua 0.6.0
    * FIXED: Prevent Chrome from sending content of paste to Google for translation (#378)
    * FIXED: To support attachments larger then 2 MiB in newer Chrome versions, we switched to blob instead of data URIs (#432)
    * FIXED: Since Outlook strips trailing equal signs in links, the key in URL hash is now base58 encoded, instead of base64 (#377)
    * FIXED: Facebooks started injecting parameters into shared URLs for tracking that lead to inaccessible pastes (#396)
    * FIXED: Properly escaped HTML in raw text mode (#358)
    * FIXED: Made download links better readable in the dark bootstrap theme (#364)
    * FIXED: Allow Letsencrypt bot to access on apache servers (#413)
  * **1.2.1 (2018-08-11)**
    * ADDED: Add support for mega.nz links in pastes and comments (#331)
    * CHANGED: Added some missing Russian translations (#348)
    * CHANGED: Minor PHP refactoring: Rename PrivateBin class to Controller, improved logic of some persistence classes (#342)
    * CHANGED: Upgrading DOMpurify library to 1.0.7
    * FIXED: Ensure legacy browsers without webcrypto support can't create paste keys with insufficient entropy (#346)
    * FIXED: Re-add support for old browsers (Firefox&lt;21, Chrome&lt;31, Safari&lt;7, IE&lt;11), broken in 1.2, will be removed again in 1.3
  * **1.2 (2018-07-22)**
    * ADDED: Translations for Spanish, Occitan, Norwegian, Portuguese, Dutch and Hungarian
    * ADDED: Option in configuration to change the default "PrivateBin" title of the site
    * ADDED: Added display of video, audio & PDF, drag & drop, preview of attachments (#182)
    * ADDED: QR code generation (#169)
    * ADDED: Introduced DOMpurify library to sanitize generated HTML before display (#183)
    * CHANGED: Force JSON request for getting paste data & password retry (#216)
    * CHANGED: Minimum required PHP version is 5.4 (#186)
    * CHANGED: Shipped .htaccess files were updated for Apache 2.4 (#192)
    * CHANGED: Cleanup of bootstrap template variants and moved icons to `img` directory
    * CHANGED: Removed option to hide clone button on expiring pastes, since this requires reading the paste for rendering the template, which leaks information on the pastes state
    * CHANGED: Upgrading libraries to: SJCL 1.0.7, jQuery 3.3.1, Base64 2.4.5, Showdown 1.8.6, DOMpurify 1.0.5 & Prettify 453bd5f
    * CHANGED: Refactored JavaScript code, making it modular with private and public functions, making it much easier to maintain (#178)
    * FIXED: To counteract regressions introduced by the refactoring, we finally introduced property based unit testing for the JavaScript code, this caught several regressions, but also some very old bugs not found so far (#32)
  * **1.1.1 (2017-10-06)**
    * CHANGED: Switched to `.php` file extension for configuration file, to avoid leaking configuration data in unprotected installation.
  * **1.1 (2016-12-26)**
    * ADDED: Translations for Italian and Russian
    * ADDED: Loading message displayed until decryption succeeded for slower (in terms of CPU or network) systems
    * ADDED: Dockerfile for docker container creation
    * CHANGED: Using modal dialog to request password input instead of native JS input window (#69)
    * CHANGED: Suppressed referrer HTTP header sending when following links in a paste or comment (#96) and added additional HTTP headers for XSS mitigation (#91)
    * CHANGED: Updated random_compat and jQuery libraries
    * FIXED: XSS using JavaScript stored as markdown formatted paste, after clicking on Raw paste button (#137)
    * FIXED: Automatic purging deleting non-expiring pastes, when using database store (#149)
  * **1.0 (2016-08-25)**
    * ADDED: Translations for Slowene and Chinese
    * ADDED: re-introduced (optional) URL shortener support, which was removed back in version 0.16 for privacy concerns
    * ADDED: Preview tab, helpful for writing markdown code or check the source code rendering
    * ADDED: Automatic purging of expired pastes, done on paste creation
    * ADDED: Option to disable icons in discussions (will only affect newly created pastes)
    * ADDED: Composer support
    * CHANGED: Renamed the ZeroBin fork to PrivateBin
    * CHANGED: Removed unmaintained RainTPL template engine, replacing the templates with straight forward PHP files
    * CHANGED: New logo and favicons
    * CHANGED: Upgrading SJCL library to 1.0.4
    * CHANGED: Switched to GCM instead of CCM mode for AES encryption for newly created pastes
    * CHANGED: Use backported random bytes function from PHP7 for older PHP versions instead of mcrypt
    * CHANGED: Switched to a SHA256 HMAC of the IP in traffic limiter instead of storing it in plain text on the server
    * CHANGED: Introduced content security policy header to reduce cross site scripting (XSS) risks
    * CHANGED: Added SHA512 subresource integrity hashes for all javascript includes to reduce the risk of manipulated scripts and easier detection of such
    * CHANGED: Refactored PHP code to conform to PSR-4 and PSR-2 standards
    * CHANGED: Switched to Identicons as the default for comments with nicknames
    * CHANGED: Vizhash is now optional and based on (128 byte) SHA512 HMAC instead of (144 byte) combination of MD5, SHA1 and a reversal of that string
    * FIXED: Content-type negociation for HTML in certain uncommon browser configurations
    * FIXED: JavaScript error displayed before page is loaded or during attachment load
    * FIXED: Don't strip space characters at beginning or end of optional password
    * FIXED: Various UI glitches in mobile version or on smaller desktops with language menu, button spacing and long URLs
    * FIXED: Back button now works as expected after switching to raw text view of a paste
    * FIXED: Reactivated second error message above send comment button to ensure its visibility when the main error message is outside the viewport
    * FIXED: Raw text now displays original markdown instead of rendered HTML
    * FIXED: Removed unused code detected with the help of various code review tools
    * FIXED: Table format for PostgreSQL, making it possible to use PostgreSQL as backend in addition to MySQL, SQLite and flat files
  * **0.22 (2015-11-09)**:
    * ADDED: Tab character input support
    * ADDED: Dark bootstrap theme
    * ADDED: Option to hide clone button on expiring pastes
    * ADDED: Option to set a different default language then English and/or enforce it as the only language
    * ADDED: Database now contains version to allow automatic update of structure, only if necessary; removing database structure check on each request
    * ADDED: Favicons
    * FIXING: Regressions in database layer, prohibiting pastes from being stored
    * FIXING: Fixing "missing" comments when they were posted during the same second to the same paste
    * FIXING: JS failing when password input disabled
    * CHANGED: Switching positions of "New" and "Send" button, highlighting the latter to improve workflow
    * CHANGED: Renamed config file to make updates easier
    * CHANGED: Switching to JSON-based REST-API
    * CHANGED: Database structure to store attachments, allowing larger attachments to be stored (depending on maximum BLOB size of database backend)
    * CHANGED: Refactored data model, traffic limiting & request handling
  * **0.21.1 (2015-09-21)**:
    * FIXING: lost meta data when using DB model instead of flat files
    * FIXING: mobile navbar getting triggered on load
    * CHANGED: database table "paste" gets automatically extended with a "meta" column
    * CHANGED: navbar of "bootstrap" template now spans full width of view port on large screens
  * **0.21 (2015-09-19)**:
    * ADDED: Translations for German, French and Polish, language selection menu (optional)
    * ADDED: File upload and image display support (optional)
    * ADDED: Markdown format support
    * ADDED: "bootstrap-compact" template that hides some of the options in a drop down menu to ensure the nav bar fitting on one line on smaller screen sizes
    * FIXING: Various usability issues with different screen sizes / device types in the "bootstrap" template
    * CHANGED: Instead of having different options to enable and preselect certain formats there is now a generic `[formatter_options]` section where formats can be added to the displayed format drop down menu. A `defaultformatter` can be set, it defaults to "plaintext". The `syntaxhighlighting` configuration got deprecated.
    * `zerobin.js` got a major refactoring:
      * moved from global namespace into anonymous function
      * events are no longer set via "onclick" attributes in the templates, but bound by from JS side
      * for simpler maintenance the functions were grouped into objects: zerobin (display logic, event handling), filter (compression,
encryption), i18n (translation, counterpart of i18n.php) and helper (stateless utilities)
    * Wiki pages were added to address common topics:
      * [Upgrading from ZeroBin 0.19 Alpha](https://github.com/PrivateBin/PrivateBin/wiki/Upgrading-from-ZeroBin-0.19-Alpha)
      * [Directory of public PrivateBin servers](https://github.com/PrivateBin/PrivateBin/wiki/PrivateBin-Directory)
      * [Translation](https://github.com/PrivateBin/PrivateBin/wiki/Translation)
      * [Templates](https://github.com/PrivateBin/PrivateBin/wiki/Templates)
  * **0.20 (2015-09-03)**:
    * ADDED: Password protected pastes (optional)
    * ADDED: configuration options for highlighting, password, discussions, expiration times, rate limiting
    * ADDED: JSON-only retrieval of paste incl. discussion, used to be able to refresh paste when posting a comment
    * ADDED: bootstrap CSS based template
    * CHANGED: "Burn after reading" pastes are now deleted only after the paste was successfully decrypted via callback. This prevents accidental deletion by chatbots following URLs and the like. Usage of a password is suggested to ensure only the desired recipient is able to encrypt it.
    * CHANGED: the "opendiscussion" option now only controls if the discussion checkbox is preselected. Use "discussion = false" to disable the discussion feature completely (which also removes the checkbox from the template).
    * FIXING: Behaviour of several conflicting configuration options. As a general measure unit tests for 9 of the options and all their possible configurations were added via a unit test generator.
    * updated JS libraries: jquery to 1.11.3, sjcl to 1.0.2, base64.js to 2.1.9, deflate to 0.5, inflate to 0.3 and prettify to latest
    * generally improved documentation, both inline phpdoc / JSdoc source code documentation, as well as Wiki pages on installation, configuration, development and JSON-API
  * **Alpha 0.19 (2013-07-05)**:
    * Corrected XSS security flaw which affected IE<10. Other browsers were not affected.
    * Corrected spacing display in IE<10.
  * **Alpha 0.18 (2013-02-24)**:
    * ADDED: The resulting URL is automatically selected after pressing "Send". You just have to press CTRL+C.
    * ADDED: Automatic syntax highlighting for 53 languages using highlight.js
    * ADDED: "5 minutes" and "1 week" expirations.
    * ADDED: "Raw text" button.
    * jQuery upgraded to 1.9.1
    * sjcl upgraded to GitHub master 2013-02-23
    * base64.js upgraded to 1.7
    * FIXED: Dates in discussion are now proper local dates.
    * ADDED: Robot meta tags in HTML to prevent search engines indexing.
    * ADDED: Better json checking (including entropy).
    * ADDED: Added version to js/css assets URLs in order to prevent some abusive caches to serve an obsolete version of these files when ZeroBin is upgraded.
    * "Burn after reading" option has been moved out of Expiration combo to a separate checkbox. Reason is: You can prevent a read-once paste to be available ad vitam eternam on the net.
  * **Alpha 0.17 (2013-02-23)**:
    * ADDED: Deletion URL.
    * small refactoring.
    * improved regex checks.
    * larger server alt on installation.
  * **Alpha 0.16**:
    * FIXED minor php warnings.
    * FIXED: zerobin.js reformated and properly commented.
    * FIXED: Directory structure re-organized.
    * CHANGED: URL shortening button was removed. (It was bad for privacy.)
  * **Alpha 0.15 (2012-04-20):**
    * FIXED: 2 minor corrections to avoid notices in php log.
    * FIXED: Sources converted to UTF-8.
  * **Alpha 0.14 (2012-04-20):**
    * ADDED: GD presence is checked. 
    * CHANGED: Traffic limiter data files moved to data/ (→easier rights management)
    * ADDED: "Burn after reading" implemented. Opening the URL will display the paste and immediately destroy it on server.
  * **Alpha 0.13 (2012-04-18):**
    * FIXED: ''imageantialias()'' call removed because it's not really usefull and can be a problem on most hosts (if GD is not compiled in php).
    * FIXED: $error not properly initialized in index.php
  * **Alpha 0.12 (2012-04-18):**
    * **DISCUSSIONS !** Now you can enable discussions on your pastes. Of course, posted comments and nickname are also encrypted and the server cannot see them.
    * This feature implies a change in storage format. You will have to delete all previous pastes in your ZeroBin. 
    * Added [[php:vizhash_gd|Vizhash]] as avatars, so you can match posters IP addresses without revealing them. (Same image = same IP). Of course the IP address cannot be deduced from the Vizhash.
    * Remaining time before expiration is now displayed.
    * Explicit tags were added to CSS and jQuery selectors (eg. div#aaa instead of #aaa) to speed up browser. 
    * Better cleaning of the URL (to make sure the key is not broken by some stupid redirection service)
  * **Alpha 0.11 (2012-04-12):**
    * Automatically ignore parameters (such as &utm_source=...) added //after// the anchor by some stupid Web 2.0 services.
    * First public release.
  * **Alpha 0.10 (2012-04-12):**
    * IE9 does not seem to correctly support ''pre-wrap'' either. Special handling mode activated for all version of IE<10. (Note: **ALL other browsers** correctly support this feature.) 
  * **Alpha 0.9 (2012-04-11):**
    * Oh bummer... IE 8 is as shitty as IE6/7: Its does not seem to support ''white-space:pre-wrap'' correctly. I had to activate the special handling mode. I still have to test IE 9.
  * **Alpha 0.8 (2012-04-11):**
    * Source code not published yet.
    * Interface completely redesigned. Icons added.
    * Now properly supports IE6/7 (ugly display, but it works. "Clone" button is disabled though.)
    * Added one level of depth for storage directories (This is better for higher load servers).
    * php version is now checked (min: 5.2.6)
    * Better checks on posted json data on server.
    * Added "1 year" expiration.
    * URLs are now converted to clickable links. This include http, https, ftp and magnet links.
    * Clickable links include ''rel="nofollow"'' to discourage SEO.
    * On my public service (http://sebsauvage.net/paste/)
      * All data will be deleted (you were warned - this is a test service)
      * Default paste expiration is now 1 month to prevent clogging-up my host.
