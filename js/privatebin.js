/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @see       {@link https://github.com/PrivateBin/PrivateBin}
 * @copyright 2012 Sébastien SAUVAGE ({@link http://sebsauvage.net})
 * @license   {@link https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License}
 * @version   1.1
 * @name      PrivateBin
 * @namespace
 */

/** global: Base64 */
/** global: FileReader */
/** global: RawDeflate */
/** global: history */
/** global: navigator */
/** global: prettyPrint */
/** global: prettyPrintOne */
/** global: showdown */
/** global: sjcl */

// Immediately start random number generator collector.
sjcl.random.startCollectors();

jQuery.PrivateBin = function($, sjcl, Base64, RawDeflate) {
    'use strict';

    /**
     * static helper methods
     *
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var helper = (function (window, document) {
        var me = {};

        /**
         * character to HTML entity lookup table
         *
         * @see    {@link https://github.com/janl/mustache.js/blob/master/mustache.js#L60}
         * @private
         * @enum   {Object}
         * @readonly
         */
        var entityMap = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        };

        /**
         * cache for script location
         *
         * @private
         * @enum   {string|null}
         */
        var scriptLocation = null;

        /**
         * converts a duration (in seconds) into human friendly approximation
         *
         * @name helper.secondsToHuman
         * @function
         * @param  {number} seconds
         * @return {Array}
         */
        me.secondsToHuman = function(seconds)
        {
            var v;
            if (seconds < 60)
            {
                v = Math.floor(seconds);
                return [v, 'second'];
            }
            if (seconds < 60 * 60)
            {
                v = Math.floor(seconds / 60);
                return [v, 'minute'];
            }
            if (seconds < 60 * 60 * 24)
            {
                v = Math.floor(seconds / (60 * 60));
                return [v, 'hour'];
            }
            // If less than 2 months, display in days:
            if (seconds < 60 * 60 * 24 * 60)
            {
                v = Math.floor(seconds / (60 * 60 * 24));
                return [v, 'day'];
            }
            v = Math.floor(seconds / (60 * 60 * 24 * 30));
            return [v, 'month'];
        };

        /**
         * text range selection
         *
         * @see    {@link https://stackoverflow.com/questions/985272/jquery-selecting-text-in-an-element-akin-to-highlighting-with-your-mouse}
         * @name   helper.selectText
         * @function
         * @param  {HTMLElement} element
         */
        me.selectText = function(element)
        {
            var range, selection;

            // MS
            if (document.body.createTextRange)
            {
                range = document.body.createTextRange();
                range.moveToElementText(element);
                range.select();
            }
            // all others
            else if (window.getSelection)
            {
                selection = window.getSelection();
                range = document.createRange();
                range.selectNodeContents(element);
                selection.removeAllRanges();
                selection.addRange(range);
            }
        };

        /**
         * set text of a jQuery element (required for IE),
         *
         * @name   helper.setElementText
         * @function
         * @param  {jQuery} $element - a jQuery element
         * @param  {string} text - the text to enter
         */
        me.setElementText = function($element, text)
        {
            // @TODO: Can we drop IE 10 support? This function looks crazy and checking oldienotice slows everything down…
            // I cannot really say, whether this IE10 method is XSS-safe…
            // For IE<10: Doesn't support white-space:pre-wrap; so we have to do this...
            if ($('#oldienotice').is(':visible')) {
                var html = me.htmlEntities(text).replace(/\n/ig, '\r\n<br>');
                $element.html('<pre>' + html + '</pre>');
            }
            // for other (sane) browsers:
            else
            {
                $element.text(text);
            }
        };

        /**
         * convert URLs to clickable links.
         * URLs to handle:
         * <pre>
         *     magnet:?xt.1=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C&xt.2=urn:sha1:TXGCZQTH26NL6OUQAJJPFALHG2LTGBC7
         *     http://example.com:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
         *     http://user:example.com@localhost:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
         * </pre>
         *
         * @name   helper.urls2links
         * @function
         * @param  {Object} element - a jQuery DOM element
         */
        me.urls2links = function(element)
        {
            var markup = '<a href="$1" rel="nofollow">$1</a>';
            element.html(
                element.html().replace(
                    /((http|https|ftp):\/\/[\w?=&.\/-;#@~%+-]+(?![\w\s?&.\/;#~%"=-]*>))/ig,
                    markup
                )
            );
            element.html(
                element.html().replace(
                    /((magnet):[\w?=&.\/-;#@~%+-]+)/ig,
                    markup
                )
            );
        };

        /**
         * minimal sprintf emulation for %s and %d formats
         *
         * @see    {@link https://stackoverflow.com/questions/610406/javascript-equivalent-to-printf-string-format#4795914}
         * @name   helper.sprintf
         * @function
         * @param  {string} format
         * @param  {...*} args - one or multiple parameters injected into format string
         * @return {string}
         */
        me.sprintf = function()
        {
            var args = arguments;
            if (typeof arguments[0] === 'object')
            {
                args = arguments[0];
            }
            var format = args[0],
                i = 1;
            return format.replace(/%((%)|s|d)/g, function (m) {
                // m is the matched format, e.g. %s, %d
                var val;
                if (m[2]) {
                    val = m[2];
                } else {
                    val = args[i];
                    // A switch statement so that the formatter can be extended.
                    switch (m)
                    {
                        case '%d':
                            val = parseFloat(val);
                            if (isNaN(val)) {
                                val = 0;
                            }
                            break;
                        default:
                            // Default is %s
                    }
                    ++i;
                }
                return val;
            });
        };

        /**
         * replace last child of element with message
         *
         * @name   helper.appendMessage
         * @function
         * @param  {jQuery} $element - a jQuery wrapped DOM element
         * @param  {string} message - the message to append
         * @TODO: make private if possible & move to function
         */
        me.appendMessage = function($element, message)
        {
            var content = $element.contents();
            if (content.length > 0)
            {
                content[content.length - 1].nodeValue = ' ' + message;
            }
            else
            {
                me.setElementText($element, message);
            }
        };

        /**
         * get value of cookie, if it was set, empty string otherwise
         *
         * @see    {@link http://www.w3schools.com/js/js_cookies.asp}
         * @name   helper.getCookie
         * @function
         * @param  {string} cname
         * @return {string}
         */
        me.getCookie = function(cname) {
            var name = cname + '=',
                ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; ++i) {
                var c = ca[i];
                while (c.charAt(0) === ' ')
                {
                    c = c.substring(1);
                }
                if (c.indexOf(name) === 0)
                {
                    return c.substring(name.length, c.length);
                }
            }
            return '';
        };

        /**
         * get the current script location (without search or hash part of the URL),
         * eg. http://example.com/path/?aaaa#bbbb --> http://example.com/path/
         *
         * @name   helper.scriptLocation
         * @function
         * @return {string} current script location
         */
        me.scriptLocation = function()
        {
            // check for cached version
            if (scriptLocation !== null) {
                return scriptLocation;
            }

            scriptLocation = window.location.href.substring(
                    0,
                    window.location.href.length - window.location.search.length - window.location.hash.length
                );

            var hashIndex = scriptLocation.indexOf('?');

            if (hashIndex !== -1)
            {
                scriptLocation = scriptLocation.substring(0, hashIndex);
            }

            return scriptLocation;
        };

        /**
         * get the pastes unique identifier from the URL,
         * eg. http://example.com/path/?c05354954c49a487#c05354954c49a487 returns c05354954c49a487
         *
         * @name   helper.pasteId
         * @function
         * @return {string} unique identifier
         */
        me.pasteId = function()
        {
            return window.location.search.substring(1);
        };

        /**
         * return the deciphering key stored in anchor part of the URL
         *
         * @name   helper.pageKey
         * @function
         * @return {string} key
         */
        me.pageKey = function()
        {
            var key = window.location.hash.substring(1),
                i = key.indexOf('&');

            // Some web 2.0 services and redirectors add data AFTER the anchor
            // (such as &utm_source=...). We will strip any additional data.
            if (i > -1)
            {
                key = key.substring(0, i);
            }

            return key;
        };

        /**
         * convert all applicable characters to HTML entities
         *
         * @see    {@link https://www.owasp.org/index.php/XSS_(Cross_Site_Scripting)_Prevention_Cheat_Sheet#RULE_.231_-_HTML_Escape_Before_Inserting_Untrusted_Data_into_HTML_Element_Content}
         * @name   helper.htmlEntities
         * @function
         * @param  {string} str
         * @return {string} escaped HTML
         */
        me.htmlEntities = function(str) {
            return String(str).replace(
                /[&<>"'`=\/]/g, function(s) {
                    return entityMap[s];
                });
        };

        return me;
    })(window, document);

    /**
     * internationalization methods
     *
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var i18n = (function (window, document) {
        var me = {};

        /**
         * supported languages, minus the built in 'en'
         *
         * @private
         * @prop   {string[]}
         * @readonly
         */
        var supportedLanguages = ['de', 'es', 'fr', 'it', 'no', 'pl', 'oc', 'ru', 'sl', 'zh'];

        /**
         * built in language
         *
         * @private
         * @prop   {string}
         */
        var language = 'en';

        /**
         * translation cache
         *
         * @private
         * @enum   {Object}
         */
        var translations = {};

        /**
         * translate a string, alias for i18n.translate()
         *
         * @name   i18n._
         * @function
         * @param  {string} messageId
         * @param  {...*} args - one or multiple parameters injected into placeholders
         * @return {string}
         */
        me._ = function()
        {
            return me.translate(arguments);
        };

        /**
         * translate a string
         *
         * @name   i18n.translate
         * @function
         * @param  {string} messageId
         * @param  {...*} args - one or multiple parameters injected into placeholders
         * @return {string}
         */
        me.translate = function()
        {
            var args = arguments, messageId;
            if (typeof arguments[0] === 'object')
            {
                args = arguments[0];
            }
            var usesPlurals = $.isArray(args[0]);
            if (usesPlurals)
            {
                // use the first plural form as messageId, otherwise the singular
                messageId = (args[0].length > 1 ? args[0][1] : args[0][0]);
            }
            else
            {
                messageId = args[0];
            }
            if (messageId.length === 0)
            {
                return messageId;
            }
            if (!translations.hasOwnProperty(messageId))
            {
                if (language !== 'en')
                {
                    console.error(
                        'Missing ' + language + ' translation for: ' + messageId
                    );
                }
                translations[messageId] = args[0];
            }
            if (usesPlurals && $.isArray(translations[messageId]))
            {
                var n = parseInt(args[1] || 1, 10),
                    key = me.getPluralForm(n),
                    maxKey = translations[messageId].length - 1;
                if (key > maxKey)
                {
                    key = maxKey;
                }
                args[0] = translations[messageId][key];
                args[1] = n;
            }
            else
            {
                args[0] = translations[messageId];
            }
            return helper.sprintf(args);
        };

        /**
         * per language functions to use to determine the plural form
         *
         * @see    {@link http://localization-guide.readthedocs.org/en/latest/l10n/pluralforms.html}
         * @name   i18n.getPluralForm
         * @function
         * @param  {number} n
         * @return {number} array key
         */
        me.getPluralForm = function(n) {
            switch (language)
            {
                case 'fr':
                case 'oc':
                case 'zh':
                    return (n > 1 ? 1 : 0);
                case 'pl':
                    return (n === 1 ? 0 : (n % 10 >= 2 && n %10 <=4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2));
                case 'ru':
                    return (n % 10 === 1 && n % 100 !== 11 ? 0 : (n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2));
                case 'sl':
                    return (n % 100 === 1 ? 1 : (n % 100 === 2 ? 2 : (n % 100 === 3 || n % 100 === 4 ? 3 : 0)));
                // de, en, es, it, no
                default:
                    return (n !== 1 ? 1 : 0);
            }
        };

        /**
         * load translations into cache, then trigger controller initialization
         *
         * @name   i18n.loadTranslations
         * @function
         */
        me.loadTranslations = function()
        {
            var newLanguage = helper.getCookie('lang');

            // auto-select language based on browser settings
            if (newLanguage.length === 0)
            {
                newLanguage = (navigator.language || navigator.userLanguage).substring(0, 2);
            }

            // if language is already used (e.g, default 'en'), skip update
            if (newLanguage === language) {
                return;
            }

            // if language is not supported, show error
            if (supportedLanguages.indexOf(newLanguage) === -1) {
                console.error('Language \'%s\' is not supported. Translation failed, fallback to English.', newLanguage);
            }

            // load strongs from JSON
            $.getJSON('i18n/' + newLanguage + '.json', function(data) {
                language = newLanguage;
                translations = data;
            }).fail(function (data, textStatus, errorMsg) {
                console.error('Language \'%s\' could not be loaded (%s: %s). Translation failed, fallback to English.', newLanguage, textStatus, errorMsg);
            });
        };

        return me;
    })(window, document);

    /**
     * handles everything related to en/decryption
     *
     * @class
     */
    var cryptTool = (function () {
        var me = {};

        /**
         * compress a message (deflate compression), returns base64 encoded data
         *
         * @name   cryptToolcompress
         * @function
         * @private
         * @param  {string} message
         * @return {string} base64 data
         */
        function compress(message)
        {
            return Base64.toBase64( RawDeflate.deflate( Base64.utob(message) ) );
        }

        /**
         * decompress a message compressed with cryptToolcompress()
         *
         * @name   cryptTooldecompress
         * @function
         * @private
         * @param  {string} data - base64 data
         * @return {string} message
         */
        function decompress(data)
        {
            return Base64.btou( RawDeflate.inflate( Base64.fromBase64(data) ) );
        }

        /**
         * compress, then encrypt message with given key and password
         *
         * @name   cryptTool.cipher
         * @function
         * @param  {string} key
         * @param  {string} password
         * @param  {string} message
         * @return {string} data - JSON with encrypted data
         */
        me.cipher = function(key, password, message)
        {
            // Galois Counter Mode, keysize 256 bit, authentication tag 128 bit
            var options = {mode: 'gcm', ks: 256, ts: 128};
            if ((password || '').trim().length === 0)
            {
                return sjcl.encrypt(key, compress(message), options);
            }
            return sjcl.encrypt(key + sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash(password)), me.compress(message), options);
        };

        /**
         * decrypt message with key, then decompress
         *
         * @name   cryptTool.decipher
         * @function
         * @param  {string} key
         * @param  {string} password
         * @param  {string} data - JSON with encrypted data
         * @return {string} decrypted message
         */
        me.decipher = function(key, password, data)
        {
            if (data !== undefined)
            {
                try
                {
                    return decompress(sjcl.decrypt(key, data));
                }
                catch(err)
                {
                    try
                    {
                        return decompress(sjcl.decrypt(key + sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash(password)), data));
                    }
                    catch(e)
                    {
                        // ignore error, because ????? @TODO
                    }
                }
            }
            return '';
        };

        return me;
    })();

    /**
     * Data source (aka MVC)
     *
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var modal = (function (window, document) {
        var me = {};

        var $cipherData;

        /**
         * check if cipher data was supplied
         *
         * @name   modal.getCipherData
         * @function
         * @return boolean
         */
        me.hasCipherData = function()
        {
            return (me.getCipherData().length > 0);
        };

        /**
         * returns the cipher data
         *
         * @name   modal.getCipherData
         * @function
         * @return string
         */
        me.getCipherData = function()
        {
            return $cipherData.text();
        };

        /**
         * returns the expiration set in the HTML
         *
         * @name   modal.getExpirationDefault
         * @function
         * @return string
         * @TODO the template can be simplified as #pasteExpiration is no longer modified (only default value)
         */
        me.getExpirationDefault = function()
        {
            return $('#pasteExpiration').val();
        };

        /**
         * returns the format set in the HTML
         *
         * @name   modal.getFormatDefault
         * @function
         * @return string
         * @TODO the template can be simplified as #pasteFormatter is no longer modified (only default value)
         */
        me.getFormatDefault = function()
        {
            return $('#pasteFormatter').val();
        };

        /**
         * init navigation manager
         *
         * preloads jQuery elements
         *
         * @name   modal.init
         * @function
         */
        me.init = function()
        {
            $cipherData = $('#cipherdata');
        };

        return me;
    })(window, document);

    /**
     * User interface manager
     *
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var uiMan = (function (window, document) {
        var me = {};

        // jQuery pre-loaded objects
        var $clearText,
            $clonedFile,
            $comments,
            $discussion,
            $image,
            $pasteResult,
            $pasteUrl,
            $prettyMessage,
            $prettyPrint,
            $editorTabs,
            $remainingTime,
            $replyStatus;

        /**
         * handle history (pop) state changes
         *
         * currently this does only handle redirects to the home page.
         *
         * @name   controller.historyChange
         * @function
         * @param  {Event} event
         */
        me.historyChange = function(event)
        {
            var currentLocation = helper.scriptLocation();
            if (event.originalEvent.state === null && // no state object passed
                event.originalEvent.target.location.href === currentLocation && // target location is home page
                window.location.href === currentLocation // and we are not already on the home page
            ) {
                // redirect to home page
                window.location.href = currentLocation;
            }
        };

        /**
         * Forces opening the paste if the link does not do this automatically.
         *
         * This is necessary as browsers will not reload the page when it is
         * already loaded (which is fake as it is set via history.pushState()).
         *
         * @name   controller.pasteLinkClick
         * @function
         * @param  {Event} event
         */
        me.pasteLinkClick = function(event)
        {
            // check if location is (already) shown in URL bar
            if (window.location.href === $pasteUrl.attr('href')) {
                // if so we need to load link by reloading the current site
                window.location.reload(true);
            }
        };

        /**
         * reload the page
         *
         * This takes the user to the PrivateBin home page.
         *
         * @name   controller.reloadPage
         * @function
         * @param  {Event} event
         */
        me.reloadPage = function(event)
        {
            window.location.href = helper.scriptLocation();
            event.preventDefault();
        };

        /**
         * main UI manager
         *
         * @name   controller.init
         * @function
         */
        me.init = function()
        {
            // hide "no javascript" message
            $('#noscript').hide();

            // preload jQuery elements
            $pasteResult = $('#pasteresult');
            // $pasteUrl is saved in sendDataContinue() if/after it is
            // actually created

            // bind events
            $('.reloadlink').click(me.reloadPage);

            $(window).on('popstate', me.historyChange);
        };

        return me;
    })(window, document);

    /**
     * UI state manager
     *
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var state = (function (window, document) {
        var me = {};

        /**
         * put the screen in "New paste" mode
         *
         * @name   controller.stateNewPaste
         * @function
         */
        me.stateNewPaste = function()
        {
            $remainingTime.removeClass('hidden');

            $loadingIndicator.addClass('hidden');
            console.error('stateNewPaste is depreciated');
        };

        /**
         * put the screen in mode after submitting a paste
         *
         * @name   controller.stateSubmittingPaste
         * @function
         */
        me.stateSubmittingPaste = function()
        {
            console.error('stateSubmittingPaste is depreciated');
        };

        /**
         * put the screen in a state where the only option is to submit a
         * new paste
         *
         * @name   controller.stateOnlyNewPaste
         * @function
         */
        me.stateOnlyNewPaste = function()
        {
            console.error('stateOnlyNewPaste is depreciated');
        };

        /**
         * put the screen in "Existing paste" mode
         *
         * @name   controller.stateExistingPaste
         * @function
         * @param  {boolean} [preview=false] - (optional) tell if the preview tabs should be displayed, defaults to false
         */
        me.stateExistingPaste = function(preview)
        {
            preview = preview || false;
            console.error('stateExistingPaste is depreciated');

            if (!preview)
            {



                console.log('show no preview');
            }
        };

        return me;
    })(window, document);

    /**
     * UI status/error manager
     *
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var status = (function (window, document) {
        var me = {};

        var $errorMessage,
            $status,
            $loadingIndicator;

        /**
         * display a status message
         *
         * @name   controller.showStatus
         * @function
         * @param  {string} message - text to display
         * @param  {boolean} [spin=false] - (optional) tell if the "spinning" animation should be displayed, defaults to false
         */
        me.showStatus = function(message, spin)
        {
            // spin is ignored for now
            $status.text(message);
        };

        /**
         * display a status message for replying to comments
         *
         * @name   controller.showStatus
         * @function
         * @param  {string} message - text to display
         * @param  {boolean} [spin=false] - (optional) tell if the "spinning" animation should be displayed, defaults to false
         */
        me.showReplyStatus = function(message, spin)
        {
            if (spin || false) {
                $replyStatus.find('.spinner').removeClass('hidden')
            }
            $replyStatus.text(message);
        };

        /**
         * hides any status messages
         *
         * @name   controller.hideSTatus
         * @function
         */
        me.hideStatus = function()
        {
            $status.html(' ');
        };

        /**
         * display an error message
         *
         * @name   status.showError
         * @function
         * @param  {string} message - text to display
         */
        me.showError = function(message)
        {
            $errorMessage.removeClass('hidden');
            helper.appendMessage($errorMessage, message);
        };

        /**
         * display an error message
         *
         * @name   status.showError
         * @function
         * @param  {string} message - text to display
         */
        me.showReplyError = function(message)
        {
            $replyStatus.addClass('alert-danger');
            $replyStatus.addClass($errorMessage.attr('class')); // @TODO ????

            $replyStatus.text(message);
        };

        /**
         * init status manager
         *
         * preloads jQuery elements
         *
         * @name   status.init
         * @function
         */
        me.init = function()
        {
            // hide "no javascript" message
            $('#noscript').hide();

            $loadingIndicator = $('#loadingindicator'); // TODO: integrate $loadingIndicator into this module or leave it in state and remove it here
            $errorMessage = $('#errormessage');
            $status = $('#status');
            // @TODO $replyStatus …

            // display status returned by php code, if any (eg. paste was properly deleted)
            // @TODO remove this by handling errors in a different way
            if ($status.text().length > 0)
            {
                me.showStatus($status.text());
                return;
            }

            // keep line height even if content empty
            $status.html(' '); // @TODO what? remove?

            // display error message from php code
            if ($errorMessage.text().length > 1) {
                me.showError($errorMessage.text());
            }
        };

        return me;
    })(window, document);

    /**
     * Passwort prompt
     *
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var prompt = (function (window, document) {
        var me = {};

        var $passwordInput,
            $passwordModal,
            $passwordForm,
            $passwordDecrypt;

        /**
         * ask the user for the password and set it
         *
         * @name   controller.requestPassword
         * @function
         */
        me.requestPassword = function()
        {
            if ($passwordModal.length === 0) {
                var password = prompt(i18n._('Please enter the password for this paste:'), '');
                if (password === null)
                {
                    throw 'password prompt canceled';
                }
                if (password.length === 0)
                {
                    // recursive…
                    me.requestPassword();
                } else {
                    $passwordInput.val(password);
                    me.displayMessages();
                }
            } else {
                $passwordModal.modal();
            }
        };

        /**
         * decrypt using the password from the modal dialog
         *
         * @name   controller.decryptPasswordModal
         * @function
         */
        me.decryptPasswordModal = function()
        {
            $passwordInput.val($passwordDecrypt.val());
            me.displayMessages();
        };

        /**
         * submit a password in the modal dialog
         *
         * @name   controller.submitPasswordModal
         * @function
         * @param  {Event} event
         */
        me.submitPasswordModal = function(event)
        {
            event.preventDefault();
            $passwordModal.modal('hide');
        };


        /**
         * init status manager
         *
         * preloads jQuery elements
         *
         * @name   controller.init
         * @function
         */
        me.init = function()
        {
            $passwordInput = $('#passwordinput');
            $passwordModal = $('#passwordmodal');
            $passwordForm = $('#passwordform');
            $passwordDecrypt = $('#passworddecrypt');

            // bind events

            // focus password input when it is shown
            $passwordModal.on('shown.bs.modal', function () {
                $passwordDecrypt.focus();
            });
            // handle modal password request on decryption
            $passwordModal.on('hidden.bs.modal', me.decryptPasswordModal);
            $passwordForm.submit(me.submitPasswordModal);
        };

        return me;
    })(window, document);

    /**
     * Manage paste/message input
     *
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var editor = (function (window, document) {
        var me = {};

        var $message,
            $messageEdit,
            $messagePreview,
            $editorTabs;

        var isPreview = false;

        /**
         * support input of tab character
         *
         * @name   editor.supportTabs
         * @function
         * @param  {Event} event
         * @TODO doc what is @this here?
         * @TODO replace this with $message ??
         */
        function supportTabs(event)
        {
            var keyCode = event.keyCode || event.which;
            // tab was pressed
            if (keyCode === 9)
            {
                // prevent the textarea to lose focus
                event.preventDefault();
                // get caret position & selection
                var val   = this.value,
                    start = this.selectionStart,
                    end   = this.selectionEnd;
                // set textarea value to: text before caret + tab + text after caret
                this.value = val.substring(0, start) + '\t' + val.substring(end);
                // put caret at right position again
                this.selectionStart = this.selectionEnd = start + 1;
            }
        }

        /**
         * view the editor tab
         *
         * @name   editor.viewEditor
         * @function
         * @param  {Event} event - optional
         */
        function viewEditor(event)
        {
            // toggle buttons
            $messageEdit.addClass('active');
            $messagePreview.removeClass('active');

            pasteViewer.hide();

            // reshow input
            $message.removeClass('hidden');

            me.focusInput();
            // me.stateNewPaste();

            // finish
            isPreview = false;
            // if (typeof event === 'undefined') {
            //     event.preventDefault();
            // } // @TODO confirm this is not needed
        }

        /**
         * view the preview tab
         *
         * @name   editor.viewPreview
         * @function
         * @param  {Event} event
         */
        function viewPreview(event)
        {
            // toggle buttons
            $messageEdit.removeClass('active');
            $messagePreview.addClass('active');

            // hide input as now preview is shown
            $message.addClass('hidden');

            // show preview
            pasteViewer.setText($message.val());
            pasteViewer.trigger();

            // finish
            isPreview = true;
            // if (typeof event === 'undefined') {
            //     event.preventDefault();
            // } // @TODO confirm this is not needed
        }

        /**
         * get the state of the preview
         *
         * @name   editor.isPreview
         * @function
         */
        me.isPreview = function()
        {
            return isPreview;
        }

        /**
         * reset the editor view
         *
         * @name   editor.resetInput
         * @function
         */
        me.resetInput = function()
        {
            // go back to input
            if (isPreview) {
                viewEditor();
            }

            // clear content
            $message.val('');
        };

        /**
         * shows the editor
         *
         * @name   editor.show
         * @function
         */
        me.show = function()
        {
            $message.removeClass('hidden');
            $editorTabs.removeClass('hidden');
        };

        /**
         * hides the editor
         *
         * @name   editor.reset
         * @function
         */
        me.hide = function()
        {
            $message.addClass('hidden');
            $editorTabs.addClass('hidden');
        };

        /**
         * focuses the message input
         *
         * @name   editor.focusInput
         * @function
         */
        me.focusInput = function()
        {
            $message.focus();
        };

        /**
         * returns the current text
         *
         * @name   editor.getText
         * @function
         * @return {string}
         */
        me.getText = function()
        {
            return $message.val()
        };

        /**
         * init status manager
         *
         * preloads jQuery elements
         *
         * @name   editor.init
         * @function
         */
        me.init = function()
        {
            $message = $('#message');
            $editorTabs = $('#editorTabs');

            // bind events
            $message.keydown(supportTabs);

            // bind click events to tab switchers (a), but save parent of them
            // (li)
            $messageEdit = $('#messageedit').click(viewEditor).parent();
            $messagePreview = $('#messagepreview').click(viewPreview).parent();
        };

        return me;
    })(window, document);


    /**
     * Parse and show paste.
     *
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var pasteViewer = (function (window, document) {
        var me = {};

        var $clearText,
            $comments,
            $discussion,
            $image,
            $placeholder,
            $prettyMessage,
            $prettyPrint,
            $remainingTime;

        var text,
            format = 'plaintext',
            isDisplayed = false,
            isChanged = true; // by default true as nothing was parsed yet

        /**
         * apply the set format on paste and displays it
         *
         * @name   pasteViewer.parsePaste
         * @private
         * @function
         */
        function parsePaste()
        {
            // skip parsing if no text is given
            if (text === '') {
                return;
            }

            // set text
            helper.setElementText($clearText, text);
            helper.setElementText($prettyPrint, text);

            switch (format) {
                case 'markdown':
                    var converter = new showdown.Converter({
                        strikethrough: true,
                        tables: true,
                        tablesHeaderId: true
                    });
                    $clearText.html(
                        converter.makeHtml(text)
                    );
                    // add table classes from bootstrap css
                    $clearText.find('table').addClass('table-condensed table-bordered');
                    break;
                case 'syntaxhighlighting':
                    // @TODO is this really needed or is "one" enough?
                    if (typeof prettyPrint === 'function')
                    {
                        prettyPrint();
                    }

                    $prettyPrint.html(
                        prettyPrintOne(
                            helper.htmlEntities(text), null, true
                        )
                    );
                    // fall through, as the rest is the same
                default: // = 'plaintext'
                    // convert URLs to clickable links
                    helper.urls2links($clearText);
                    helper.urls2links($prettyPrint);

                    $prettyPrint.css('white-space', 'pre-wrap');
                    $prettyPrint.css('word-break', 'normal');
                    $prettyPrint.removeClass('prettyprint');
            }
        }

        /**
         * displays the paste
         *
         * @name   pasteViewer.show
         * @private
         * @function
         */
        function showPaste()
        {
            // instead of "nothing" better display a placeholder
            if (text === '') {
                $placeholder.removeClass('hidden')
                return;
            }
            // otherwise hide the placeholder
            $placeholder.addClass('hidden')

            switch (format) {
                case 'markdown':
                    $clearText.removeClass('hidden');
                    $prettyMessage.addClass('hidden');
                    break;
                default:
                    $clearText.addClass('hidden');
                    $prettyMessage.removeClass('hidden');
                    break;
            }
        }

        /**
         * show decrypted text in the display area, including discussion (if open)
         *
         * @name   pasteViewer.displayPaste
         * @function
         * @param  {Object} [paste] - (optional) object including comments to display (items = array with keys ('data','meta'))
         */
        me.displayPaste = function(paste)
        {
            paste = paste || $.parseJSON(modal.getCipherData());
            var key = helper.pageKey(),
                password = $passwordInput.val();
            if (!$prettyPrint.hasClass('prettyprinted')) {
                // Try to decrypt the paste.
                try
                {
                    if (paste.attachment)
                    {
                        var attachment = cryptTool.decipher(key, password, paste.attachment);
                        if (attachment.length === 0)
                        {
                            if (password.length === 0)
                            {
                                me.requestPassword();
                                return;
                            }
                            attachment = cryptTool.decipher(key, password, paste.attachment);
                        }
                        if (attachment.length === 0)
                        {
                            throw 'failed to decipher attachment';
                        }

                        if (paste.attachmentname)
                        {
                            var attachmentname = cryptTool.decipher(key, password, paste.attachmentname);
                            if (attachmentname.length > 0)
                            {
                                $attachmentLink.attr('download', attachmentname);
                            }
                        }
                        $attachmentLink.attr('href', attachment);
                        $attachment.removeClass('hidden');

                        // if the attachment is an image, display it
                        var imagePrefix = 'data:image/';
                        if (attachment.substring(0, imagePrefix.length) === imagePrefix)
                        {
                            $image.html(
                                $(document.createElement('img'))
                                    .attr('src', attachment)
                                    .attr('class', 'img-thumbnail')
                            );
                            $image.removeClass('hidden');
                        }
                    }
                    var cleartext = cryptTool.decipher(key, password, paste.data);
                    if (cleartext.length === 0 && password.length === 0 && !paste.attachment)
                    {
                        me.requestPassword();
                        return;
                    }
                    if (cleartext.length === 0 && !paste.attachment)
                    {
                        throw 'failed to decipher message';
                    }

                    $passwordInput.val(password);
                    if (cleartext.length > 0)
                    {
                        pasteViewer.setFormat(paste.meta.formatter);
                        me.formatPaste(paste.meta.formatter, cleartext);
                    }
                }
                catch(err)
                {
                    me.stateOnlyNewPaste();
                    me.showError(i18n._('Could not decrypt data (Wrong key?)'));
                    return;
                }
            }

            // display paste expiration / for your eyes only
            if (paste.meta.expire_date)
            {
                var expiration = helper.secondsToHuman(paste.meta.remaining_time),
                    expirationLabel = [
                        'This document will expire in %d ' + expiration[1] + '.',
                        'This document will expire in %d ' + expiration[1] + 's.'
                    ];
                helper.appendMessage($remainingTime, i18n._(expirationLabel, expiration[0]));
                $remainingTime.removeClass('foryoureyesonly')
                                  .removeClass('hidden');
            }
            if (paste.meta.burnafterreading)
            {
                // unfortunately many web servers don't support DELETE (and PUT) out of the box
                $.ajax({
                    type: 'POST',
                    url: helper.scriptLocation() + '?' + helper.pasteId(),
                    data: {deletetoken: 'burnafterreading'},
                    dataType: 'json',
                    headers: headers
                })
                .fail(function() {
                    controller.showError(i18n._('Could not delete the paste, it was not stored in burn after reading mode.'));
                });
                helper.appendMessage($remainingTime, i18n._(
                    'FOR YOUR EYES ONLY. Don\'t close this window, this message can\'t be displayed again.'
                ));
                $remainingTime.addClass('foryoureyesonly')
                                  .removeClass('hidden');
                // discourage cloning (as it can't really be prevented)
                $cloneButton.addClass('hidden');
            }

            // if the discussion is opened on this paste, display it
            if (paste.meta.opendiscussion)
            {
                $comments.html('');

                var $divComment;

                // iterate over comments
                for (var i = 0; i < paste.comments.length; ++i)
                {
                    var $place = $comments,
                        comment = paste.comments[i],
                        commentText = cryptTool.decipher(key, password, comment.data),
                        $parentComment = $('#comment_' + comment.parentid);

                    $divComment = $('<article><div class="comment" id="comment_' + comment.id
                               + '"><div class="commentmeta"><span class="nickname"></span>'
                               + '<span class="commentdate"></span></div>'
                               + '<div class="commentdata"></div>'
                               + '<button class="btn btn-default btn-sm">'
                               + i18n._('Reply') + '</button></div></article>');
                    var $divCommentData = $divComment.find('div.commentdata');

                    // if parent comment exists
                    if ($parentComment.length)
                    {
                        // shift comment to the right
                        $place = $parentComment;
                    }
                    $divComment.find('button').click({commentid: comment.id}, me.openReply);
                    helper.setElementText($divCommentData, commentText);
                    helper.urls2links($divCommentData);

                    // try to get optional nickname
                    var nick = cryptTool.decipher(key, password, comment.meta.nickname);
                    if (nick.length > 0)
                    {
                        $divComment.find('span.nickname').text(nick);
                    }
                    else
                    {
                        divComment.find('span.nickname').html('<i>' + i18n._('Anonymous') + '</i>');
                    }
                    $divComment.find('span.commentdate')
                              .text(' (' + (new Date(comment.meta.postdate * 1000).toLocaleString()) + ')')
                              .attr('title', 'CommentID: ' + comment.id);

                    // if an avatar is available, display it
                    if (comment.meta.vizhash)
                    {
                        $divComment.find('span.nickname')
                                  .before(
                                    '<img src="' + comment.meta.vizhash + '" class="vizhash" title="' +
                                    i18n._('Anonymous avatar (Vizhash of the IP address)') + '" /> '
                                  );
                    }

                    $place.append($divComment);
                }

                // add 'add new comment' area
                $divComment = $(
                    '<div class="comment"><button class="btn btn-default btn-sm">' +
                    i18n._('Add comment') + '</button></div>'
                );
                $divComment.find('button').click({commentid: helper.pasteId()}, me.openReply);
                $comments.append($divComment);
                $discussion.removeClass('hidden');
            }
        };

        /**
         * open the comment entry when clicking the "Reply" button of a comment
         *
         * @name   pasteViewer.openReply
         * @function
         * @param  {Event} event
         */
        me.openReply = function(event)
        {
            event.preventDefault();

            // remove any other reply area
            $('div.reply').remove();

            var source = $(event.target),
                commentid = event.data.commentid,
                hint = i18n._('Optional nickname...'),
                $reply = $('#replytemplate');
            $reply.find('button').click(
                {parentid: commentid},
                me.sendComment
            );
            source.after($reply);
            $replyStatus = $('#replystatus'); // when ID --> put into HTML
            $('#replymessage').focus();
        };

        /**
         * sets the format in which the text is shown
         *
         * @name   pasteViewer.setFormat
         * @function
         * @param {string}  the the new format
         */
        me.setFormat = function(newFormat)
        {
            if (format !== newFormat) {
                format = newFormat;
                isChanged = true;
            }
        };

        /**
         * returns the current format
         *
         * @name   pasteViewer.setFormat
         * @function
         * @return {string}
         */
        me.getFormat = function()
        {
            return format;
        };

        /**
         * sets the text to show
         *
         * @name   editor.init
         * @function
         * @param {string}  the text to show
         */
        me.setText = function(newText)
        {
            if (text !== newText) {
                text = newText;
                isChanged = true;
            }
        };

        /**
         * show/update the parsed text (preview)
         *
         * @name   pasteViewer.trigger
         * @function
         */
        me.trigger = function()
        {
            if (isChanged) {
                parsePaste();
                isChanged = false;
            }

            if (!isDisplayed) {
                showPaste();
                isDisplayed = true;
            }
        };

        /**
         * hide parsed text (preview)
         *
         * @name   pasteViewer.hide
         * @function
         */
        me.hide = function()
        {
            if (!isDisplayed) {
                console.warn('pasteViewer was called to hide the parsed view, but it is already hidden.');
            }

            $clearText.addClass('hidden');
            $prettyMessage.addClass('hidden');
            $placeholder.addClass('hidden');

            isDisplayed = false;
        };

        /**
         * init status manager
         *
         * preloads jQuery elements
         *
         * @name   editor.init
         * @function
         */
        me.init = function()
        {
            $clearText = $('#cleartext');
            $comments = $('#comments');
            $discussion = $('#discussion');
            $image = $('#image');
            $placeholder = $('#placeholder');
            $prettyMessage = $('#prettymessage');
            $prettyPrint = $('#prettyprint');
            $remainingTime = $('#remainingtime');

            // check requirements
            if (typeof prettyPrintOne !== 'function') {
                status.showError(
                    i18n._('The library %s is not available.', 'pretty print') +
                    i18n._('This may cause display errors.')
                );
            }
            if (typeof showdown !== 'object') {
                status.showError(
                    i18n._('The library %s is not available.', 'showdown') +
                    i18n._('This may cause display errors.')
                );
            }

            // get default option from template/HTML or fall back to set value
            format = modal.getFormatDefault() || format;
        };

        return me;
    })(window, document);

    /**
     * Manage top (navigation) bar
     *
     * @param  {object} window
     * @param  {object} document
     * @name state
     * @class
     */
    var topNav = (function (window, document) {
        var me = {};

        var createButtonsDisplayed = false;
        var viewButtonsDisplayed = false;

        var $attach,
            $attachment,
            $attachmentLink,
            $burnAfterReading,
            $burnAfterReadingOption,
            $cloneButton,
            $clonedFile,
            $expiration,
            $fileRemoveButton,
            $fileWrap,
            $formatter,
            $newButton,
            $openDiscussionOption,
            $openDiscussion,
            $password,
            $rawTextButton,
            $sendButton;

        var pasteExpiration = '1week';

        /**
         * set the expiration on bootstrap templates in dropdown
         *
         * @name   topNav.updateExpiration
         * @function
         * @param  {Event} event
         */
        function updateExpiration(event)
        {
            // get selected option
            var target = $(event.target);

            // update dropdown display and save new expiration time
            $('#pasteExpirationDisplay').text(target.text());
            pasteExpiration = target.data('expiration');

            event.preventDefault();
        }

        /**
         * set the format on bootstrap templates in dropdown
         *
         * @name   topNav.updateFormat
         * @function
         * @param  {Event} event
         */
        function updateFormat(event)
        {
            // get selected option
            var $target = $(event.target);

            // update dropdown display and save new format
            var newFormat = $target.data('format');
            $('#pasteFormatterDisplay').text($target.text());
            pasteViewer.setFormat(newFormat);

            // update preview
            if (editor.isPreview()) {
                pasteViewer.trigger();
            }

            event.preventDefault();
        }

        /**
         * when "burn after reading" is checked, disable discussion
         *
         * @name   topNav.changeBurnAfterReading
         * @function
         */
        function changeBurnAfterReading()
        {
            if ($burnAfterReading.is(':checked')) {
                $openDiscussionOption.addClass('buttondisabled');
                $openDiscussion.prop('checked', false);

                // if button is actually disabled, force-enable it and uncheck other button
                $burnAfterReadingOption.removeClass('buttondisabled');
            } else {
                $openDiscussionOption.removeClass('buttondisabled');
            }
        }

        /**
         * when discussion is checked, disable "burn after reading"
         *
         * @name   topNav.changeOpenDiscussion
         * @function
         */
        function changeOpenDiscussion()
        {
            if ($openDiscussion.is(':checked')) {
                $burnAfterReadingOption.addClass('buttondisabled');
                $burnAfterReading.prop('checked', false);

                // if button is actually disabled, force-enable it and uncheck other button
                $openDiscussionOption.removeClass('buttondisabled');
            } else {
                $burnAfterReadingOption.removeClass('buttondisabled');
            }
        }

        /**
         * return raw text
         *
         * @name   topNav.rawText
         * @function
         * @param  {Event} event
         */
        function rawText(event)
        {
            var paste = pasteViewer.getFormat() === 'markdown' ?
                $prettyPrint.text() : $clearText.text();
            history.pushState(
                null, document.title, helper.scriptLocation() + '?' +
                helper.pasteId() + '#' + helper.pageKey()
            );
            // we use text/html instead of text/plain to avoid a bug when
            // reloading the raw text view (it reverts to type text/html)
            var newDoc = document.open('text/html', 'replace');
            newDoc.write('<pre>' + helper.htmlEntities(paste) + '</pre>');
            newDoc.close();

            event.preventDefault();
        }

        /**
         * set the language in a cookie and reload the page
         *
         * @name   topNav.setLanguage
         * @function
         * @param  {Event} event
         */
        function setLanguage(event)
        {
            document.cookie = 'lang=' + $(event.target).data('lang');
            me.reloadPage(event);
        }

        /**
         * removes an attachment
         *
         * @name   controller.removeAttachment
         * @function
         */
        me.removeAttachment = function()
        {
            $clonedFile.addClass('hidden');
            // removes the saved decrypted file data
            $attachmentLink.attr('href', '');
            // the only way to deselect the file is to recreate the input // @TODO really?
            $fileWrap.html($fileWrap.html());
            $fileWrap.removeClass('hidden');
        };

        /**
         * Shows all elements belonging to viwing an existing pastes
         *
         * @name   topNav.hideAllElem
         * @function
         */
        me.showViewButtons = function()
        {
            if (viewButtonsDisplayed) {
                console.log('showViewButtons: view buttons are already displayed');
                return;
            }

            $cloneButton.removeClass('hidden');
            $rawTextButton.removeClass('hidden');

            viewButtonsDisplayed = true;
        };

        /**
         * Hides all elements belonging to existing pastes
         *
         * @name   topNav.hideAllElem
         * @function
         */
        me.hideViewButtons = function()
        {
            if (!viewButtonsDisplayed) {
                console.log('hideViewButtons: view buttons are already hidden');
                return;
            }

            $cloneButton.addClass('hidden');
            $rawTextButton.addClass('hidden');

            viewButtonsDisplayed = false;
        };

        /**
         * shows all elements needed when creating a new paste
         *
         * @name   topNav.setLanguage
         * @function
         */
        me.showCreateButtons = function()
        {
            if (createButtonsDisplayed) {
                console.log('showCreateButtons: create buttons are already displayed');
                return;
            }

            $attachment.removeClass('hidden');
            $sendButton.removeClass('hidden');
            $expiration.removeClass('hidden');
            $formatter.removeClass('hidden');
            $burnAfterReadingOption.removeClass('hidden');
            $openDiscussionOption.removeClass('hidden');
            $newButton.removeClass('hidden');
            $password.removeClass('hidden');
            $attach.removeClass('hidden');
            // $clonedFile.removeClass('hidden'); // @TODO

            createButtonsDisplayed = true;
        };

        /**
         * shows all elements needed when creating a new paste
         *
         * @name   topNav.setLanguage
         * @function
         */
        me.hideCreateButtons = function()
        {
            if (!createButtonsDisplayed) {
                console.log('hideCreateButtons: create buttons are already hidden');
                return;
            }

            $attachment.addClass('hidden');
            $sendButton.addClass('hidden');
            $expiration.addClass('hidden');
            $formatter.addClass('hidden');
            $burnAfterReadingOption.addClass('hidden');
            $openDiscussionOption.addClass('hidden');
            $newButton.addClass('hidden');
            $password.addClass('hidden');
            $attach.addClass('hidden');
            // $clonedFile.addClass('hidden'); // @TODO

            createButtonsDisplayed = false;
        };

        /**
         * only shows the "new paste" button
         *
         * @name   topNav.setLanguage
         * @function
         */
        me.showNewPasteButton = function()
        {
            $newButton.addClass('hidden');
        };

        /**
         * shows a loading message, optionally with a percentage
         *
         * @name   topNav.showLoading
         * @function
         * @param  {string} message
         * @param  {int}    percentage
         */
        me.showLoading = function(message, percentage)
        {
            // currently parameters are ignored
            $loadingIndicator.removeClass('hidden');
        };

        /**
         * hides the loading message
         *
         * @name   topNav.hideLoading
         * @function
         */
        me.hideLoading = function()
        {
            $loadingIndicator.removeClass('hidden');
        };

        /**
         * returns the currently set expiration time
         *
         * @name   topNav.getExpiration
         * @function
         * @return {int}
         */
        me.getExpiration = function()
        {
            return pasteExpiration;
        };

        /**
         * init navigation manager
         *
         * preloads jQuery elements
         *
         * @name   topNav.init
         * @function
         */
        me.init = function()
        {
            $attach = $('#attach');
            $attachment = $('#attachment');
            $attachmentLink = $('#attachment a');
            $burnAfterReading = $('#burnafterreading');
            $burnAfterReadingOption = $('#burnafterreadingoption');
            $cloneButton = $('#clonebutton');
            $clonedFile = $('#clonedfile');
            $expiration = $('#expiration');
            $fileRemoveButton = $('#fileremovebutton');
            $fileWrap = $('#filewrap');
            $formatter = $('#formatter');
            $newButton = $('#newbutton');
            $openDiscussionOption = $('#opendiscussionoption');
            $openDiscussion = $('#opendiscussion');
            $password = $('#password');
            $rawTextButton = $('#rawtextbutton');
            $sendButton = $('#sendbutton');

            // bootstrap template drop down
            $('#language ul.dropdown-menu li a').click(me.setLanguage);
            // page template drop down
            $('#language select option').click(me.setLanguage);

            // bind events
            $burnAfterReading.change(changeBurnAfterReading);
            $openDiscussionOption.change(changeOpenDiscussion);
            $newButton.click(controller.newPaste);
            $sendButton.click(controller.sendData);
            $cloneButton.click(controller.clonePaste);
            $rawTextButton.click(rawText);
            $fileRemoveButton.click(me.removeAttachment);

            // bootstrap template drop downs
            $('ul.dropdown-menu li a', $('#expiration').parent()).click(updateExpiration);
            $('ul.dropdown-menu li a', $('#formatter').parent()).click(updateFormat);

            // initiate default state of checkboxes
            changeBurnAfterReading();
            changeOpenDiscussion();

            // get default value from template or fall back to set value
            pasteExpiration = modal.getExpirationDefault() || pasteExpiration;
        };

        return me;
    })(window, document);

    /**
     * PrivateBin logic
     *
     * @param  {object} window
     * @param  {object} document
     * @name controller
     * @class
     */
    var controller = (function (window, document) {
        var me = {};

        /**
         * headers to send in AJAX requests
         *
         * @private
         * @enum   {Object}
         */
        var headers = {'X-Requested-With': 'JSONHttpRequest'};

        /**
         * URL shortners create address
         *
         * @private
         * @prop   {string}
         */
        var shortenerUrl = '';

        /**
         * URL of newly created paste
         *
         * @private
         * @prop   {string}
         */
        var createdPasteUrl = '';

        /**
         * send a reply in a discussion
         *
         * @name   controller.sendComment
         * @function
         * @param  {Event} event
         */
        me.sendComment = function(event)
        {
            event.preventDefault();
            $errorMessage.addClass('hidden');
            // do not send if no data
            var replyMessage = $('#replymessage');
            if (replyMessage.val().length === 0)
            {
                return;
            }

            me.showStatus(i18n._('Sending comment...'), true);
            var parentid = event.data.parentid,
                key = helper.pageKey(),
                cipherdata = cryptTool.cipher(key, $passwordInput.val(), replyMessage.val()),
                ciphernickname = '',
                nick = $('#nickname').val();
            if (nick.length > 0)
            {
                ciphernickname = cryptTool.cipher(key, $passwordInput.val(), nick);
            }
            var data_to_send = {
                data:     cipherdata,
                parentid: parentid,
                pasteid:  helper.pasteId(),
                nickname: ciphernickname
            };

            $.ajax({
                type: 'POST',
                url: helper.scriptLocation(),
                data: data_to_send,
                dataType: 'json',
                headers: headers,
                success: function(data)
                {
                    if (data.status === 0)
                    {
                        controller.showStatus(i18n._('Comment posted.'));
                        $.ajax({
                            type: 'GET',
                            url: helper.scriptLocation() + '?' + helper.pasteId(),
                            dataType: 'json',
                            headers: headers,
                            success: function(data)
                            {
                                if (data.status === 0)
                                {
                                    controller.displayMessages(data);
                                }
                                else if (data.status === 1)
                                {
                                    controller.showError(i18n._('Could not refresh display: %s', data.message));
                                }
                                else
                                {
                                    controller.showError(i18n._('Could not refresh display: %s', i18n._('unknown status')));
                                }
                            }
                        })
                        .fail(function() {
                            controller.showError(i18n._('Could not refresh display: %s', i18n._('server error or not responding')));
                        });
                    }
                    else if (data.status === 1)
                    {
                        controller.showError(i18n._('Could not post comment: %s', data.message));
                    }
                    else
                    {
                        controller.showError(i18n._('Could not post comment: %s', i18n._('unknown status')));
                    }
                }
            })
            .fail(function() {
                controller.showError(i18n._('Could not post comment: %s', i18n._('server error or not responding')));
            });
        };

        /**
         * send a new paste to server
         *
         * @name   controller.sendData
         * @function
         * @param  {Event} event
         */
        me.sendData = function(event)
        {
            event.preventDefault();
            var file = document.getElementById('file'),
                files = (file && file.files) ? file.files : null; // FileList object

            // do not send if no data.
            if ($message.val().length === 0 && !(files && files[0]))
            {
                return;
            }

            // if sjcl has not collected enough entropy yet, display a message
            if (!sjcl.random.isReady())
            {
                me.showStatus(i18n._('Sending paste (Please move your mouse for more entropy)...'), true);
                sjcl.random.addEventListener('seeded', function() {
                    me.sendData(event);
                });
                return;
            }

            $('.navbar-toggle').click();
            $password.addClass('hidden');
            me.showStatus(i18n._('Sending paste...'), true);

            me.stateSubmittingPaste();

            var randomkey = sjcl.codec.base64.fromBits(sjcl.random.randomWords(8, 0), 0),
                password = $passwordInput.val();
            if(files && files[0])
            {
                if(typeof FileReader === undefined)
                {
                    // revert loading status…
                    me.stateNewPaste();
                    me.showError(i18n._('Your browser does not support uploading encrypted files. Please use a newer browser.'));
                    return;
                }
                var reader = new FileReader();
                // closure to capture the file information
                reader.onload = (function(theFile)
                {
                    return function(e) {
                        controller.sendDataContinue(
                            randomkey,
                            cryptTool.cipher(randomkey, password, e.target.result),
                            cryptTool.cipher(randomkey, password, theFile.name)
                        );
                    };
                })(files[0]);
                reader.readAsDataURL(files[0]);
            }
            else if($attachmentLink.attr('href'))
            {
                me.sendDataContinue(
                    randomkey,
                    cryptTool.cipher(randomkey, password, $attachmentLink.attr('href')),
                    $attachmentLink.attr('download')
                );
            }
            else
            {
                me.sendDataContinue(randomkey, '', '');
            }
        };

        /**
         * send a new paste to server, step 2
         *
         * @name   controller.sendDataContinue
         * @function
         * @param  {string} randomkey
         * @param  {string} cipherdata_attachment
         * @param  {string} cipherdata_attachment_name
         */
        me.sendDataContinue = function(randomkey, cipherdata_attachment, cipherdata_attachment_name)
        {
            var cipherdata = cryptTool.cipher(randomkey, $passwordInput.val(), editor.getText()),
                data_to_send = {
                    data:             cipherdata,
                    expire:           topNav.getExpiration(),
                    formatter:        pasteViewer.getFormat(),
                    burnafterreading: $burnAfterReading.is(':checked') ? 1 : 0,
                    opendiscussion:   $openDiscussion.is(':checked') ? 1 : 0
                };
            if (cipherdata_attachment.length > 0)
            {
                data_to_send.attachment = cipherdata_attachment;
                if (cipherdata_attachment_name.length > 0)
                {
                    data_to_send.attachmentname = cipherdata_attachment_name;
                }
            }
            $.ajax({
                type: 'POST',
                url: helper.scriptLocation(),
                data: data_to_send,
                dataType: 'json',
                headers: headers,
                success: function(data)
                {
                    if (data.status === 0) {
                        me.stateExistingPaste();
                        var url = helper.scriptLocation() + '?' + data.id + '#' + randomkey,
                            deleteUrl = helper.scriptLocation() + '?pasteid=' + data.id + '&deletetoken=' + data.deletetoken;
                        me.hideStatus();
                        $errorMessage.addClass('hidden');
                        // show new URL in browser bar
                        history.pushState({type: 'newpaste'}, document.title, url);

                        $('#pastelink').html(
                            i18n._(
                                'Your paste is <a id="pasteurl" href="%s">%s</a> <span id="copyhint">(Hit [Ctrl]+[c] to copy)</span>',
                                url, url
                            ) + me.shortenUrl(url)
                        );
                        // save newly created element
                        $pasteUrl = $('#pasteurl');
                        // and add click event
                        $pasteUrl.click(me.pasteLinkClick);

                        var shortenButton = $('#shortenbutton');
                        if (shortenButton) {
                            shortenButton.click(me.sendToShortener);
                        }
                        $('#deletelink').html('<a href="' + deleteUrl + '">' + i18n._('Delete data') + '</a>');
                        $pasteResult.removeClass('hidden');
                        // we pre-select the link so that the user only has to [Ctrl]+[c] the link
                        helper.selectText($pasteUrl[0]);
                        me.hideStatus();
                        me.formatPaste(data_to_send.formatter, $message.val());
                    }
                    else if (data.status === 1)
                    {
                        // revert loading status…
                        controller.stateNewPaste();
                        controller.showError(i18n._('Could not create paste: %s', data.message));
                    }
                    else
                    {
                        // revert loading status…
                        controller.stateNewPaste();
                        controller.showError(i18n._('Could not create paste: %s', i18n._('unknown status')));
                    }
                }
            })
            .fail(function()
            {
                // revert loading status…
                me.stateNewPaste();
                controller.showError(i18n._('Could not create paste: %s', i18n._('server error or not responding')));
            });
        };

        /**
         * check if a URL shortener was defined and create HTML containing a link to it
         *
         * @name   controller.shortenUrl
         * @function
         * @param  {string} url
         * @return {string} html
         */
        me.shortenUrl = function(url)
        {
            var shortenerHtml = $('#shortenbutton');
            if (shortenerHtml) {
                shortenerUrl = shortenerHtml.data('shortener');
                createdPasteUrl = url;
                return ' ' + $('<div />').append(shortenerHtml.clone()).html();
            }
            return '';
        };

        /**
         * forward to URL shortener
         *
         * @name   controller.sendToShortener
         * @function
         * @param  {Event} event
         */
        me.sendToShortener = function(event)
        {
            window.location.href = shortenerUrl + encodeURIComponent(createdPasteUrl);
            event.preventDefault();
        };

        /**
         * creates a new paste
         *
         * @name   controller.newPaste
         * @function
         */
        me.newPaste = function()
        {
            topNav.hideViewButtons();
            topNav.showCreateButtons();
            editor.resetInput();
            editor.show();
            editor.focusInput();
        };

        /**
         * clone the current paste
         *
         * @name   controller.clonePaste
         * @function
         * @param  {Event} event
         */
        me.clonePaste = function(event)
        {
            me.stateNewPaste();

            // erase the id and the key in url
            history.replaceState(null, document.title, helper.scriptLocation());

            status.hideStatus();
            if ($attachmentLink.attr('href'))
            {
                $clonedFile.removeClass('hidden');
                $fileWrap.addClass('hidden');
            }
            $message.val(
                pasteViewer.getFormat() === 'markdown' ?
                    $prettyPrint.val() : $clearText.val()
            );
            $('.navbar-toggle').click();

            event.preventDefault();
        };

        /**
         * application start
         *
         * @name   controller.init
         * @function
         */
        me.init = function()
        {
            // first load translations
            i18n.loadTranslations();

            // initialize other modules/"classes"
            status.init();
            modal.init();
            uiMan.init();
            topNav.init();
            editor.init();
            pasteViewer.init();
            prompt.init();

            // display an existing paste
            if (modal.hasCipherData()) {
                // missing decryption key in URL?
                if (window.location.hash.length === 0)
                {
                    status.showError(i18n._('Cannot decrypt paste: Decryption key missing in URL (Did you use a redirector or an URL shortener which strips part of the URL?)'));
                    return;
                }

                // show proper elements on screen
                // me.hideCreateButtons(); // they should not be visible in the first place
                me.showViewButtons();
                me.displayMessages();
                return;
            }

            // otherwise create a new paste
            me.newPaste();
        };

        return me;
    })(window, document);

    jQuery(document).ready(function() {
        /**
         * main application start, called when DOM is fully loaded and
         * runs controller initalization
         */
        $(controller.init);
    });

    return {
        helper: helper,
        i18n: i18n,
        cryptTool: cryptTool,
        controller: controller
    };
}(jQuery, sjcl, Base64, RawDeflate);
