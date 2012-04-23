# ZeroBin version history #

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
  * **Alpha 0.9 (2012-04-11):**
    * Oh bummer... IE 8 is as shitty as IE6/7: Its does not seem to support ''white-space:pre-wrap'' correctly. I had to activate the special handling mode. I still have to test IE 9.
  * **Alpha 0.10 (2012-04-12):**
    * IE9 does not seem to correctly support ''pre-wrap'' either. Special handling mode activated for all version of IE<10. (Note: **ALL other browsers** correctly support this feature.) 
  * **Alpha 0.11 (2012-04-12):**
    * Automatically ignore parameters (such as &utm_source=...) added //after// the anchor by some stupid Web 2.0 services.
    * First public release.
  * **Alpha 0.12 (2012-04-18):**
    * **DISCUSSIONS !** Now you can enable discussions on your pastes. Of course, posted comments and nickname are also encrypted and the server cannot see them.
    * This feature implies a change in storage format. You will have to delete all previous pastes in your ZeroBin. 
    * Added [[php:vizhash_gd|Vizhash]] as avatars, so you can match posters IP addresses without revealing them. (Same image = same IP). Of course the IP address cannot be deduced from the Vizhash.
    * Remaining time before expiration is now displayed.
    * Explicit tags were added to CSS and jQuery selectors (eg. div#aaa instead of #aaa) to speed up browser. 
    * Better cleaning of the URL (to make sure the key is not broken by some stupid redirection service)
  * **Alpha 0.13 (2012-04-18):**
    * FIXED: ''imageantialias()'' call removed because it's not really usefull and can be a problem on most hosts (if GD is not compiled in php).
    * FIXED: $error not properly initialized in index.php
  * **Alpha 0.14 (2012-04-20):**
    * ADDED: GD presence is checked. 
    * CHANGED: Traffic limiter data files moved to data/ (→easier rights management)
    * ADDED: "Burn after reading" implemented. Opening the URL will display the paste and immediately destroy it on server.
  * **Alpha 0.15 (2012-04-20):**
    * FIXED: 2 minor corrections to avoid notices in php log.
    * FIXED: Sources converted to UTF-8.

