# ZeroBin version history #

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
      * [Upgrading from ZeroBin 0.19 Alpha](https://github.com/elrido/ZeroBin/wiki/Upgrading-from-ZeroBin-0.19-Alpha)
      * [ZeroBin Directory of public servers](https://github.com/elrido/ZeroBin/wiki/ZeroBin-Directory)
      * [Translation](https://github.com/elrido/ZeroBin/wiki/Translation)
      * [Templates](https://github.com/elrido/ZeroBin/wiki/Templates)
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
