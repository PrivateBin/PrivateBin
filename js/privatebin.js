/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @see       {@link https://github.com/PrivateBin/PrivateBin}
 * @copyright 2012 Sébastien SAUVAGE ({@link http://sebsauvage.net})
 * @license   {@link https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License}
 * @version   1.2.1
 * @name      PrivateBin
 * @namespace
 */

/** global: Base64 */
/** global: DOMPurify */
/** global: FileReader */
/** global: RawDeflate */
/** global: history */
/** global: navigator */
/** global: prettyPrint */
/** global: prettyPrintOne */
/** global: showdown */
/** global: sjcl */
/** global: kjua */

// Immediately start random number generator collector.
sjcl.random.startCollectors();
// Setting this to 10 ensures 1024 bits of entropy get collected before generating the paste key
sjcl.random.setDefaultParanoia(10);

// main application start, called when DOM is fully loaded
jQuery(document).ready(function() {
    'use strict';
    // run main controller
    $.PrivateBin.Controller.init();
});

jQuery.PrivateBin = (function($, sjcl, Base64, RawDeflate) {
    'use strict';

    /**
     * static Helper methods
     *
     * @name Helper
     * @class
     */
    var Helper = (function () {
        var me = {};

        /**
         * blacklist of UserAgents (parts) known to belong to a bot
         *
         * @private
         * @enum   {Object}
         * @readonly
         */
        var BadBotUA = [
            'Bot',
            'bot'
        ];

        /**
         * cache for script location
         *
         * @name Helper.baseUri
         * @private
         * @enum   {string|null}
         */
        var baseUri = null;

        /**
         * converts a duration (in seconds) into human friendly approximation
         *
         * @name Helper.secondsToHuman
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
         * @name   Helper.selectText
         * @function
         * @param  {HTMLElement} element
         */
        me.selectText = function(element)
        {
            var range, selection;

            // MS
            if (document.body.createTextRange) {
                range = document.body.createTextRange();
                range.moveToElementText(element);
                range.select();
            } else if (window.getSelection) {
                selection = window.getSelection();
                range = document.createRange();
                range.selectNodeContents(element);
                selection.removeAllRanges();
                selection.addRange(range);
            }
        };

        /**
         * convert URLs to clickable links.
         * URLs to handle:
         * <pre>
         *     magnet:?xt.1=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C&xt.2=urn:sha1:TXGCZQTH26NL6OUQAJJPFALHG2LTGBC7
         *     https://example.com:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
         *     http://user:example.com@localhost:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
         * </pre>
         *
         * @name   Helper.urls2links
         * @function
         * @param  {string} html
         * @return {string}
         */
        me.urls2links = function(html)
        {
            return html.replace(
                /(((https?|ftp):\/\/[\w?!=&.\/-;#@~%+*-]+(?![\w\s?!&.\/;#~%"=-]*>))|((magnet):[\w?=&.\/-;#@~%+*-]+))/ig,
                '<a href="$1" rel="nofollow">$1</a>'
            );
        };

        /**
         * minimal sprintf emulation for %s and %d formats
         *
         * Note that this function needs the parameters in the same order as the
         * format strings appear in the string, contrary to the original.
         *
         * @see    {@link https://stackoverflow.com/questions/610406/javascript-equivalent-to-printf-string-format#4795914}
         * @name   Helper.sprintf
         * @function
         * @param  {string} format
         * @param  {...*} args - one or multiple parameters injected into format string
         * @return {string}
         */
        me.sprintf = function()
        {
            var args = Array.prototype.slice.call(arguments);
            var format = args[0],
                i = 1;
            return format.replace(/%(s|d)/g, function (m) {
                // m is the matched format, e.g. %s, %d
                var val = args[i];
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
                return val;
            });
        };

        /**
         * get value of cookie, if it was set, empty string otherwise
         *
         * @see    {@link http://www.w3schools.com/js/js_cookies.asp}
         * @name   Helper.getCookie
         * @function
         * @param  {string} cname - may not be empty
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
         * get the current location (without search or hash part of the URL),
         * eg. https://example.com/path/?aaaa#bbbb --> https://example.com/path/
         *
         * @name   Helper.baseUri
         * @function
         * @return {string}
         */
        me.baseUri = function()
        {
            // check for cached version
            if (baseUri !== null) {
                return baseUri;
            }

            // window.location.origin is a newer alternative, but requires FF 21 / Chrome 31 / Safari 7 / IE 11
            baseUri = window.location.protocol + '//' + window.location.host + window.location.pathname;
            return baseUri;
        };

        /**
         * resets state, used for unit testing
         *
         * @name   Helper.reset
         * @function
         */
        me.reset = function()
        {
            baseUri = null;
        };

        /**
         * checks whether this is a bot we dislike
         *
         * @name   Helper.isBadBot
         * @function
         * @return {bool}
         */
        me.isBadBot = function() {
            // check whether a bot user agent part can be found in the current
            // user agent
            var arrayLength = BadBotUA.length;
            for (var i = 0; i < arrayLength; i++) {
                if (navigator.userAgent.indexOf(BadBotUA) >= 0) {
                    return true;
                }
            }

            return false;
        }

        return me;
    })();

    /**
     * internationalization module
     *
     * @name I18n
     * @class
     */
    var I18n = (function () {
        var me = {};

        /**
         * const for string of loaded language
         *
         * @name I18n.languageLoadedEvent
         * @private
         * @prop   {string}
         * @readonly
         */
        var languageLoadedEvent = 'languageLoaded';

        /**
         * supported languages, minus the built in 'en'
         *
         * @name I18n.supportedLanguages
         * @private
         * @prop   {string[]}
         * @readonly
         */
        var supportedLanguages = ['de', 'es', 'fr', 'it', 'hu', 'no', 'nl', 'pl', 'pt', 'oc', 'ru', 'sl', 'zh'];

        /**
         * built in language
         *
         * @name I18n.language
         * @private
         * @prop   {string|null}
         */
        var language = null;

        /**
         * translation cache
         *
         * @name I18n.translations
         * @private
         * @enum   {Object}
         */
        var translations = {};

        /**
         * translate a string, alias for I18n.translate
         *
         * @name   I18n._
         * @function
         * @param  {jQuery} $element - optional
         * @param  {string} messageId
         * @param  {...*} args - one or multiple parameters injected into placeholders
         * @return {string}
         */
        me._ = function()
        {
            return me.translate.apply(this, arguments);
        };

        /**
         * translate a string
         *
         * Optionally pass a jQuery element as the first parameter, to automatically
         * let the text of this element be replaced. In case the (asynchronously
         * loaded) language is not downloadet yet, this will make sure the string
         * is replaced when it is actually loaded.
         * So for easy translations passing the jQuery object to apply it to is
         * more save, especially when they are loaded in the beginning.
         *
         * @name   I18n.translate
         * @function
         * @param  {jQuery} $element - optional
         * @param  {string} messageId
         * @param  {...*} args - one or multiple parameters injected into placeholders
         * @return {string}
         */
        me.translate = function()
        {
            // convert parameters to array
            var args = Array.prototype.slice.call(arguments),
                messageId,
                $element = null;

            // parse arguments
            if (args[0] instanceof jQuery) {
                // optional jQuery element as first parameter
                $element = args[0];
                args.shift();
            }

            // extract messageId from arguments
            var usesPlurals = $.isArray(args[0]);
            if (usesPlurals) {
                // use the first plural form as messageId, otherwise the singular
                messageId = args[0].length > 1 ? args[0][1] : args[0][0];
            } else {
                messageId = args[0];
            }

            if (messageId.length === 0) {
                return messageId;
            }

            // if no translation string cannot be found (in translations object)
            if (!translations.hasOwnProperty(messageId) || language === null) {
                // if language is still loading and we have an elemt assigned
                if (language === null && $element !== null) {
                    // handle the error by attaching the language loaded event
                    var orgArguments = arguments;
                    $(document).on(languageLoadedEvent, function () {
                        // log to show that the previous error could be mitigated
                        console.warn('Fix missing translation of \'' + messageId + '\' with now loaded language ' + language);
                        // re-execute this function
                        me.translate.apply(this, orgArguments);
                    });

                    // and fall back to English for now until the real language
                    // file is loaded
                }

                // for all other languages than English for which this behaviour
                // is expected as it is built-in, log error
                if (language !== null && language !== 'en') {
                    console.error('Missing translation for: \'' + messageId + '\' in language ' + language);
                    // fallback to English
                }

                // save English translation (should be the same on both sides)
                translations[messageId] = args[0];
            }

            // lookup plural translation
            if (usesPlurals && $.isArray(translations[messageId])) {
                var n = parseInt(args[1] || 1, 10),
                    key = me.getPluralForm(n),
                    maxKey = translations[messageId].length - 1;
                if (key > maxKey) {
                    key = maxKey;
                }
                args[0] = translations[messageId][key];
                args[1] = n;
            } else {
                // lookup singular translation
                args[0] = translations[messageId];
            }

            // format string
            var output = Helper.sprintf.apply(this, args);

            // if $element is given, apply text to element
            if ($element !== null) {
                // get last text node of element
                var content = $element.contents();
                if (content.length > 1) {
                    content[content.length - 1].nodeValue = ' ' + output;
                } else {
                    $element.text(output);
                }
            }

            return output;
        };

        /**
         * per language functions to use to determine the plural form
         *
         * @see    {@link http://localization-guide.readthedocs.org/en/latest/l10n/pluralforms.html}
         * @name   I18n.getPluralForm
         * @function
         * @param  {int} n
         * @return {int} array key
         */
        me.getPluralForm = function(n) {
            switch (language)
            {
                case 'fr':
                case 'oc':
                case 'zh':
                    return n > 1 ? 1 : 0;
                case 'pl':
                    return n === 1 ? 0 : (n % 10 >= 2 && n %10 <=4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2);
                case 'ru':
                    return n % 10 === 1 && n % 100 !== 11 ? 0 : (n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2);
                case 'sl':
                    return n % 100 === 1 ? 1 : (n % 100 === 2 ? 2 : (n % 100 === 3 || n % 100 === 4 ? 3 : 0));
                // de, en, es, hu, it, nl, no, pt
                default:
                    return n !== 1 ? 1 : 0;
            }
        };

        /**
         * load translations into cache
         *
         * @name   I18n.loadTranslations
         * @function
         */
        me.loadTranslations = function()
        {
            var newLanguage = Helper.getCookie('lang');

            // auto-select language based on browser settings
            if (newLanguage.length === 0) {
                newLanguage = (navigator.language || navigator.userLanguage || 'en').substring(0, 2);
            }

            // if language is already used skip update
            if (newLanguage === language) {
                return;
            }

            // if language is built-in (English) skip update
            if (newLanguage === 'en') {
                language = 'en';
                return;
            }

            // if language is not supported, show error
            if (supportedLanguages.indexOf(newLanguage) === -1) {
                console.error('Language \'%s\' is not supported. Translation failed, fallback to English.', newLanguage);
                language = 'en';
                return;
            }

            // load strings from JSON
            $.getJSON('i18n/' + newLanguage + '.json', function(data) {
                language = newLanguage;
                translations = data;
                $(document).triggerHandler(languageLoadedEvent);
            }).fail(function (data, textStatus, errorMsg) {
                console.error('Language \'%s\' could not be loaded (%s: %s). Translation failed, fallback to English.', newLanguage, textStatus, errorMsg);
                language = 'en';
            });
        };

        /**
         * resets state, used for unit testing
         *
         * @name   I18n.reset
         * @function
         */
        me.reset = function(mockLanguage, mockTranslations)
        {
            language = mockLanguage || null;
            translations = mockTranslations || {};
        };

        return me;
    })();

    /**
     * handles everything related to en/decryption
     *
     * @name CryptTool
     * @class
     */
    var CryptTool = (function () {
        var me = {};

        /**
         * compress a message (deflate compression), returns base64 encoded data
         *
         * @name   CryptTool.compress
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
         * @name   CryptTool.decompress
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
         * @name   CryptTool.cipher
         * @function
         * @param  {string} key
         * @param  {string} password
         * @param  {string} message
         * @return {string} data - JSON with encrypted data
         */
        me.cipher = function(key, password, message)
        {
            // Galois Counter Mode, keysize 256 bit, authentication tag 128 bit
            var options = {
                mode: 'gcm',
                ks: 256,
                ts: 128
            };

            if ((password || '').trim().length === 0) {
                return sjcl.encrypt(key, compress(message), options);
            }
            return sjcl.encrypt(key + sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash(password)), compress(message), options);
        };

        /**
         * decrypt message with key, then decompress
         *
         * @name   CryptTool.decipher
         * @function
         * @param  {string} key
         * @param  {string} password
         * @param  {string} data - JSON with encrypted data
         * @return {string} decrypted message, empty if decryption failed
         */
        me.decipher = function(key, password, data)
        {
            if (data !== undefined) {
                try {
                    return decompress(sjcl.decrypt(key, data));
                } catch(err) {
                    try {
                        return decompress(sjcl.decrypt(key + sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash(password)), data));
                    } catch(e) {
                        return '';
                    }
                }
            }
        };

        /**
         * checks whether the crypt tool has collected enough entropy
         *
         * @name   CryptTool.isEntropyReady
         * @function
         * @return {bool}
         */
        me.isEntropyReady = function()
        {
            return sjcl.random.isReady();
        };

        /**
         * add a listener function, triggered when enough entropy is available
         *
         * @name   CryptTool.addEntropySeedListener
         * @function
         * @param {function} func
         */
        me.addEntropySeedListener = function(func)
        {
            sjcl.random.addEventListener('seeded', func);
        };

        /**
         * returns a random symmetric key
         *
         * @name   CryptTool.getSymmetricKey
         * @function
         * @return {string} func
         */
        me.getSymmetricKey = function()
        {
            return sjcl.codec.base64.fromBits(sjcl.random.randomWords(8, 10), 0);
        };

        return me;
    })();

    /**
     * (Model) Data source (aka MVC)
     *
     * @name   Model
     * @class
     */
    var Model = (function () {
        var me = {};

        var pasteData = null,
            $templates;

        var id = null, symmetricKey = null;

        /**
         * returns the expiration set in the HTML
         *
         * @name   Model.getExpirationDefault
         * @function
         * @return string
         */
        me.getExpirationDefault = function()
        {
            return $('#pasteExpiration').val();
        };

        /**
         * returns the format set in the HTML
         *
         * @name   Model.getFormatDefault
         * @function
         * @return string
         */
        me.getFormatDefault = function()
        {
            return $('#pasteFormatter').val();
        };

        /**
         * returns the paste data (including the cipher data)
         *
         * @name   Model.getPasteData
         * @function
         * @param {function} callback (optional) Called when data is available
         * @param {function} useCache (optional) Whether to use the cache or
         *                            force a data reload. Default: true
         * @return string
         */
        me.getPasteData = function(callback, useCache)
        {
            // use cache if possible/allowed
            if (useCache !== false && pasteData !== null) {
                //execute callback
                if (typeof callback === 'function') {
                    return callback(pasteData);
                }

                // alternatively just using inline
                return pasteData;
            }

            // reload data
            Uploader.prepare();
            Uploader.setUrl(Helper.baseUri() + '?' + me.getPasteId());

            Uploader.setFailure(function (status, data) {
                // revert loading status…
                Alert.hideLoading();
                TopNav.showViewButtons();

                // show error message
                Alert.showError(Uploader.parseUploadError(status, data, 'get paste data'));
            });
            Uploader.setSuccess(function (status, data) {
                pasteData = data;

                if (typeof callback === 'function') {
                    return callback(data);
                }
            });
            Uploader.run();
        };

        /**
         * get the pastes unique identifier from the URL,
         * eg. https://example.com/path/?c05354954c49a487#dfdsdgdgdfgdf returns c05354954c49a487
         *
         * @name   Model.getPasteId
         * @function
         * @return {string} unique identifier
         * @throws {string}
         */
        me.getPasteId = function()
        {
            if (id === null) {
                // Attention: This also returns the delete token inside of the ID, if it is specified
                id = window.location.search.substring(1);

                if (id === '') {
                    throw 'no paste id given';
                }
            }

            return id;
        }

        /**
         * Returns true, when the URL has a delete token and the current call was used for deleting a paste.
         *
         * @name   Model.hasDeleteToken
         * @function
         * @return {bool}
         */
        me.hasDeleteToken = function()
        {
            return window.location.search.indexOf('deletetoken') !== -1;
        }

        /**
         * return the deciphering key stored in anchor part of the URL
         *
         * @name   Model.getPasteKey
         * @function
         * @return {string|null} key
         * @throws {string}
         */
        me.getPasteKey = function()
        {
            if (symmetricKey === null) {
                symmetricKey = window.location.hash.substring(1);

                if (symmetricKey === '') {
                    throw 'no encryption key given';
                }

                // Some web 2.0 services and redirectors add data AFTER the anchor
                // (such as &utm_source=...). We will strip any additional data.
                var ampersandPos = symmetricKey.indexOf('&');
                if (ampersandPos > -1)
                {
                    symmetricKey = symmetricKey.substring(0, ampersandPos);
                }
            }

            return symmetricKey;
        };

        /**
         * returns a jQuery copy of the HTML template
         *
         * @name Model.getTemplate
         * @function
         * @param  {string} name - the name of the template
         * @return {jQuery}
         */
        me.getTemplate = function(name)
        {
            // find template
            var $element = $templates.find('#' + name + 'template').clone(true);
            // change ID to avoid collisions (one ID should really be unique)
            return $element.prop('id', name);
        };

        /**
         * resets state, used for unit testing
         *
         * @name   Model.reset
         * @function
         */
        me.reset = function()
        {
            pasteData = $templates = id = symmetricKey = null;
        };

        /**
         * init navigation manager
         *
         * preloads jQuery elements
         *
         * @name   Model.init
         * @function
         */
        me.init = function()
        {
            $templates = $('#templates');
        };

        return me;
    })();

    /**
     * Helper functions for user interface
     *
     * everything directly UI-related, which fits nowhere else
     *
     * @name   UiHelper
     * @class
     */
    var UiHelper = (function () {
        var me = {};

        /**
         * handle history (pop) state changes
         *
         * currently this does only handle redirects to the home page.
         *
         * @name   UiHelper.historyChange
         * @private
         * @function
         * @param  {Event} event
         */
        function historyChange(event)
        {
            var currentLocation = Helper.baseUri();
            if (event.originalEvent.state === null && // no state object passed
                event.target.location.href === currentLocation && // target location is home page
                window.location.href === currentLocation // and we are not already on the home page
            ) {
                // redirect to home page
                window.location.href = currentLocation;
            }
        }

        /**
         * reload the page
         *
         * This takes the user to the PrivateBin homepage.
         *
         * @name   UiHelper.reloadHome
         * @function
         */
        me.reloadHome = function()
        {
            window.location.href = Helper.baseUri();
        };

        /**
         * checks whether the element is currently visible in the viewport (so
         * the user can actually see it)
         *
         * @see    {@link https://stackoverflow.com/a/40658647}
         * @name   UiHelper.isVisible
         * @function
         * @param  {jQuery} $element The link hash to move to.
         */
        me.isVisible = function($element)
        {
            var elementTop = $element.offset().top;
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();

            return elementTop > viewportTop && elementTop < viewportBottom;
        };

        /**
         * scrolls to a specific element
         *
         * @see    {@link https://stackoverflow.com/questions/4198041/jquery-smooth-scroll-to-an-anchor#answer-12714767}
         * @name   UiHelper.scrollTo
         * @function
         * @param  {jQuery}           $element        The link hash to move to.
         * @param  {(number|string)}  animationDuration passed to jQuery .animate, when set to 0 the animation is skipped
         * @param  {string}           animationEffect   passed to jQuery .animate
         * @param  {function}         finishedCallback  function to call after animation finished
         */
        me.scrollTo = function($element, animationDuration, animationEffect, finishedCallback)
        {
            var $body = $('html, body'),
                margin = 50,
                callbackCalled = false;

            //calculate destination place
            var dest = 0;
            // if it would scroll out of the screen at the bottom only scroll it as
            // far as the screen can go
            if ($element.offset().top > $(document).height() - $(window).height()) {
                dest = $(document).height() - $(window).height();
            } else {
                dest = $element.offset().top - margin;
            }
            // skip animation if duration is set to 0
            if (animationDuration === 0) {
                window.scrollTo(0, dest);
            } else {
                // stop previous animation
                $body.stop();
                // scroll to destination
                $body.animate({
                    scrollTop: dest
                }, animationDuration, animationEffect);
            }

            // as we have finished we can enable scrolling again
            $body.queue(function (next) {
                if (!callbackCalled) {
                    // call user function if needed
                    if (typeof finishedCallback !== 'undefined') {
                        finishedCallback();
                    }

                    // prevent calling this function twice
                    callbackCalled = true;
                }
                next();
            });
        };

        /**
         * trigger a history (pop) state change
         *
         * used to test the UiHelper.historyChange private function
         *
         * @name   UiHelper.mockHistoryChange
         * @function
         * @param  {string} state   (optional) state to mock
         */
        me.mockHistoryChange = function(state)
        {
            if (typeof state === 'undefined') {
                state = null;
            }
            historyChange($.Event('popstate', {originalEvent: new PopStateEvent('popstate', {state: state}), target: window}));
        };

        /**
         * initialize
         *
         * @name   UiHelper.init
         * @function
         */
        me.init = function()
        {
            // update link to home page
            $('.reloadlink').prop('href', Helper.baseUri());

            $(window).on('popstate', historyChange);
        };

        return me;
    })();

    /**
     * Alert/error manager
     *
     * @name   Alert
     * @class
     */
    var Alert = (function () {
        var me = {};

        var $errorMessage,
            $loadingIndicator,
            $statusMessage,
            $remainingTime;

        var currentIcon;

        var alertType = [
            'loading', // not in bootstrap, but using a good value here
            'info', // status icon
            'warning', // not used yet
            'danger' // error icon
        ];

        var customHandler;

        /**
         * forwards a request to the i18n module and shows the element
         *
         * @name   Alert.handleNotification
         * @private
         * @function
         * @param  {int} id - id of notification
         * @param  {jQuery} $element - jQuery object
         * @param  {string|array} args
         * @param  {string|null} icon - optional, icon
         */
        function handleNotification(id, $element, args, icon)
        {
            // basic parsing/conversion of parameters
            if (typeof icon === 'undefined') {
                icon = null;
            }
            if (typeof args === 'undefined') {
                args = null;
            } else if (typeof args === 'string') {
                // convert string to array if needed
                args = [args];
            }

            // pass to custom handler if defined
            if (typeof customHandler === 'function') {
                var handlerResult = customHandler(alertType[id], $element, args, icon);
                if (handlerResult === true) {
                    // if it returns true, skip own handler
                    return;
                }
                if (handlerResult instanceof jQuery) {
                    // continue processing with new element
                    $element = handlerResult;
                    icon = null; // icons not supported in this case
                }
            }

            // handle icon
            if (icon !== null && // icon was passed
                icon !== currentIcon[id] // and it differs from current icon
            ) {
                var $glyphIcon = $element.find(':first');

                // remove (previous) icon
                $glyphIcon.removeClass(currentIcon[id]);

                // any other thing as a string (e.g. 'null') (only) removes the icon
                if (typeof icon === 'string') {
                    // set new icon
                    currentIcon[id] = 'glyphicon-' + icon;
                    $glyphIcon.addClass(currentIcon[id]);
                }
            }

            // show text
            if (args !== null) {
                // add jQuery object to it as first parameter
                args.unshift($element);
                // pass it to I18n
                I18n._.apply(this, args);
            }

            // show notification
            $element.removeClass('hidden');
        }

        /**
         * display a status message
         *
         * This automatically passes the text to I18n for translation.
         *
         * @name   Alert.showStatus
         * @function
         * @param  {string|array} message     string, use an array for %s/%d options
         * @param  {string|null}  icon        optional, the icon to show,
         *                                    default: leave previous icon
         */
        me.showStatus = function(message, icon)
        {
            console.info('status shown: ', message);
            handleNotification(1, $statusMessage, message, icon);
        };

        /**
         * display an error message
         *
         * This automatically passes the text to I18n for translation.
         *
         * @name   Alert.showError
         * @function
         * @param  {string|array} message     string, use an array for %s/%d options
         * @param  {string|null}  icon        optional, the icon to show, default:
         *                                    leave previous icon
         */
        me.showError = function(message, icon)
        {
            console.error('error message shown: ', message);
            handleNotification(3, $errorMessage, message, icon);
        };

        /**
         * display remaining message
         *
         * This automatically passes the text to I18n for translation.
         *
         * @name   Alert.showRemaining
         * @function
         * @param  {string|array} message     string, use an array for %s/%d options
         */
        me.showRemaining = function(message)
        {
            console.info('remaining message shown: ', message);
            handleNotification(1, $remainingTime, message);
        };

        /**
         * shows a loading message, optionally with a percentage
         *
         * This automatically passes all texts to the i10s module.
         *
         * @name   Alert.showLoading
         * @function
         * @param  {string|array|null} message      optional, use an array for %s/%d options, default: 'Loading…'
         * @param  {string|null}       icon         optional, the icon to show, default: leave previous icon
         */
        me.showLoading = function(message, icon)
        {
            if (typeof message !== 'undefined' && message !== null) {
                console.info('status changed: ', message);
            }

            // default message text
            if (typeof message === 'undefined') {
                message = 'Loading…';
            }

            handleNotification(0, $loadingIndicator, message, icon);

            // show loading status (cursor)
            $('body').addClass('loading');
        };

        /**
         * hides the loading message
         *
         * @name   Alert.hideLoading
         * @function
         */
        me.hideLoading = function()
        {
            $loadingIndicator.addClass('hidden');

            // hide loading cursor
            $('body').removeClass('loading');
        };

        /**
         * hides any status/error messages
         *
         * This does not include the loading message.
         *
         * @name   Alert.hideMessages
         * @function
         */
        me.hideMessages = function()
        {
            // also possible: $('.statusmessage').addClass('hidden');
            $statusMessage.addClass('hidden');
            $errorMessage.addClass('hidden');
        };

        /**
         * set a custom handler, which gets all notifications.
         *
         * This handler gets the following arguments:
         * alertType (see array), $element, args, icon
         * If it returns true, the own processing will be stopped so the message
         * will not be displayed. Otherwise it will continue.
         * As an aditional feature it can return q jQuery element, which will
         * then be used to add the message there. Icons are not supported in
         * that case and will be ignored.
         * Pass 'null' to reset/delete the custom handler.
         * Note that there is no notification when a message is supposed to get
         * hidden.
         *
         * @name   Alert.setCustomHandler
         * @function
         * @param {function|null} newHandler
         */
        me.setCustomHandler = function(newHandler)
        {
            customHandler = newHandler;
        };

        /**
         * init status manager
         *
         * preloads jQuery elements
         *
         * @name   Alert.init
         * @function
         */
        me.init = function()
        {
            // hide "no javascript" error message
            $('#noscript').hide();

            // not a reset, but first set of the elements
            $errorMessage = $('#errormessage');
            $loadingIndicator = $('#loadingindicator');
            $statusMessage = $('#status');
            $remainingTime = $('#remainingtime');

            currentIcon = [
                'glyphicon-time', // loading icon
                'glyphicon-info-sign', // status icon
                '', // reserved for warning, not used yet
                'glyphicon-alert' // error icon
            ];
        };

        return me;
    })();

    /**
     * handles paste status/result
     *
     * @name   PasteStatus
     * @class
     */
    var PasteStatus = (function () {
        var me = {};

        var $pasteSuccess,
            $pasteUrl,
            $remainingTime,
            $shortenButton;

        /**
         * forward to URL shortener
         *
         * @name   PasteStatus.sendToShortener
         * @private
         * @function
         */
        function sendToShortener()
        {
            window.location.href = $shortenButton.data('shortener') +
                                   encodeURIComponent($pasteUrl.attr('href'));
        }

        /**
         * Forces opening the paste if the link does not do this automatically.
         *
         * This is necessary as browsers will not reload the page when it is
         * already loaded (which is fake as it is set via history.pushState()).
         *
         * @name   PasteStatus.pasteLinkClick
         * @function
         */
        function pasteLinkClick()
        {
            // check if location is (already) shown in URL bar
            if (window.location.href === $pasteUrl.attr('href')) {
                // if so we need to load link by reloading the current site
                window.location.reload(true);
            }
        }

        /**
         * creates a notification after a successfull paste upload
         *
         * @name   PasteStatus.createPasteNotification
         * @function
         * @param  {string} url
         * @param  {string} deleteUrl
         */
        me.createPasteNotification = function(url, deleteUrl)
        {
            $('#pastelink').html(
                I18n._(
                    'Your paste is <a id="pasteurl" href="%s">%s</a> <span id="copyhint">(Hit [Ctrl]+[c] to copy)</span>',
                    url, url
                )
            );
            // save newly created element
            $pasteUrl = $('#pasteurl');
            // and add click event
            $pasteUrl.click(pasteLinkClick);

            // shorten button
            $('#deletelink').html('<a href="' + deleteUrl + '">' + I18n._('Delete data') + '</a>');

            // show result
            $pasteSuccess.removeClass('hidden');
            // we pre-select the link so that the user only has to [Ctrl]+[c] the link
            Helper.selectText($pasteUrl[0]);
        };

        /**
         * shows the remaining time
         *
         * @name PasteStatus.showRemainingTime
         * @function
         * @param {object} pasteMetaData
         */
        me.showRemainingTime = function(pasteMetaData)
        {
            if (pasteMetaData.burnafterreading) {
                // display paste "for your eyes only" if it is deleted

                // the paste has been deleted when the JSON with the ciphertext
                // has been downloaded

                Alert.showRemaining("FOR YOUR EYES ONLY. Don't close this window, this message can't be displayed again.");
                $remainingTime.addClass('foryoureyesonly');

                // discourage cloning (it cannot really be prevented)
                TopNav.hideCloneButton();

            } else if (pasteMetaData.expire_date) {
                // display paste expiration
                var expiration = Helper.secondsToHuman(pasteMetaData.remaining_time),
                    expirationLabel = [
                        'This document will expire in %d ' + expiration[1] + '.',
                        'This document will expire in %d ' + expiration[1] + 's.'
                    ];

                Alert.showRemaining([expirationLabel, expiration[0]]);
                $remainingTime.removeClass('foryoureyesonly');
            } else {
                // never expires
                return;
            }

            // in the end, display notification
            $remainingTime.removeClass('hidden');
        };

        /**
         * hides the remaining time and successful upload notification
         *
         * @name PasteStatus.hideMessages
         * @function
         */
        me.hideMessages = function()
        {
            $remainingTime.addClass('hidden');
            $pasteSuccess.addClass('hidden');
        };

        /**
         * init status manager
         *
         * preloads jQuery elements
         *
         * @name   PasteStatus.init
         * @function
         */
        me.init = function()
        {
            $pasteSuccess = $('#pastesuccess');
            // $pasteUrl is saved in me.createPasteNotification() after creation
            $remainingTime = $('#remainingtime');
            $shortenButton = $('#shortenbutton');

            // bind elements
            $shortenButton.click(sendToShortener);
        };

        return me;
    })();

    /**
     * password prompt
     *
     * @name Prompt
     * @class
     */
    var Prompt = (function () {
        var me = {};

        var $passwordDecrypt,
            $passwordForm,
            $passwordModal;

        var password = '';

        /**
         * submit a password in the modal dialog
         *
         * @name Prompt.submitPasswordModal
         * @private
         * @function
         * @param  {Event} event
         */
        function submitPasswordModal(event)
        {
            event.preventDefault();

            // get input
            password = $passwordDecrypt.val();

            // hide modal
            $passwordModal.modal('hide');

            PasteDecrypter.run();
        }

        /**
         * ask the user for the password and set it
         *
         * @name Prompt.requestPassword
         * @function
         */
        me.requestPassword = function()
        {
            // show new bootstrap method (if available)
            if ($passwordModal.length !== 0) {
                $passwordModal.modal({
                    backdrop: 'static',
                    keyboard: false
                });
                return;
            }

            // fallback to old method for page template
            password = prompt(I18n._('Please enter the password for this paste:'), '');
            if (password === null) {
                throw 'password prompt canceled';
            }
            if (password.length === 0) {
                // recurse…
                return me.requestPassword();
            }
            PasteDecrypter.run();
        };

        /**
         * get the cached password
         *
         * If you do not get a password with this function
         * (returns an empty string), use requestPassword.
         *
         * @name   Prompt.getPassword
         * @function
         * @return {string}
         */
        me.getPassword = function()
        {
            return password;
        };

        /**
         * resets the password to an empty string
         *
         * @name   Prompt.reset
         * @function
         */
        me.reset = function()
        {
            // reset internal
            password = '';

            // and also reset UI
            $passwordDecrypt.val('');
        }

        /**
         * init status manager
         *
         * preloads jQuery elements
         *
         * @name   Prompt.init
         * @function
         */
        me.init = function()
        {
            $passwordDecrypt = $('#passworddecrypt');
            $passwordForm = $('#passwordform');
            $passwordModal = $('#passwordmodal');

            // bind events

            // focus password input when it is shown
            $passwordModal.on('shown.bs.Model', function () {
                $passwordDecrypt.focus();
            });
            // handle Model password submission
            $passwordForm.submit(submitPasswordModal);
        };

        return me;
    })();

    /**
     * Manage paste/message input, and preview tab
     *
     * Note that the actual preview is handled by PasteViewer.
     *
     * @name   Editor
     * @class
     */
    var Editor = (function () {
        var me = {};

        var $editorTabs,
            $messageEdit,
            $messagePreview,
            $message;

        var isPreview = false;

        /**
         * support input of tab character
         *
         * @name   Editor.supportTabs
         * @function
         * @param  {Event} event
         * @this $message (but not used, so it is jQuery-free, possibly faster)
         */
        function supportTabs(event)
        {
            var keyCode = event.keyCode || event.which;
            // tab was pressed
            if (keyCode === 9) {
                // get caret position & selection
                var val   = this.value,
                    start = this.selectionStart,
                    end   = this.selectionEnd;
                // set textarea value to: text before caret + tab + text after caret
                this.value = val.substring(0, start) + '\t' + val.substring(end);
                // put caret at right position again
                this.selectionStart = this.selectionEnd = start + 1;
                // prevent the textarea to lose focus
                event.preventDefault();
            }
        }

        /**
         * view the Editor tab
         *
         * @name   Editor.viewEditor
         * @function
         * @param  {Event} event - optional
         */
        function viewEditor(event)
        {
            // toggle buttons
            $messageEdit.addClass('active');
            $messagePreview.removeClass('active');

            PasteViewer.hide();

            // reshow input
            $message.removeClass('hidden');

            me.focusInput();

            // finish
            isPreview = false;

            // prevent jumping of page to top
            if (typeof event !== 'undefined') {
                event.preventDefault();
            }
        }

        /**
         * view the preview tab
         *
         * @name   Editor.viewPreview
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
            PasteViewer.setText($message.val());
            if (AttachmentViewer.hasAttachmentData()) {
                var attachmentData = AttachmentViewer.getAttachmentData() || AttachmentViewer.getAttachmentLink().attr('href');
                AttachmentViewer.handleAttachmentPreview(AttachmentViewer.getAttachmentPreview(), attachmentData);
            }
            PasteViewer.run();

            // finish
            isPreview = true;

            // prevent jumping of page to top
            if (typeof event !== 'undefined') {
                event.preventDefault();
            }
        }

        /**
         * get the state of the preview
         *
         * @name   Editor.isPreview
         * @function
         */
        me.isPreview = function()
        {
            return isPreview;
        };

        /**
         * reset the Editor view
         *
         * @name   Editor.resetInput
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
         * shows the Editor
         *
         * @name   Editor.show
         * @function
         */
        me.show = function()
        {
            $message.removeClass('hidden');
            $editorTabs.removeClass('hidden');
        };

        /**
         * hides the Editor
         *
         * @name   Editor.reset
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
         * @name   Editor.focusInput
         * @function
         */
        me.focusInput = function()
        {
            $message.focus();
        };

        /**
         * sets a new text
         *
         * @name   Editor.setText
         * @function
         * @param {string} newText
         */
        me.setText = function(newText)
        {
            $message.val(newText);
        };

        /**
         * returns the current text
         *
         * @name   Editor.getText
         * @function
         * @return {string}
         */
        me.getText = function()
        {
            return $message.val();
        };

        /**
         * init status manager
         *
         * preloads jQuery elements
         *
         * @name   Editor.init
         * @function
         */
        me.init = function()
        {
            $editorTabs = $('#editorTabs');
            $message = $('#message');

            // bind events
            $message.keydown(supportTabs);

            // bind click events to tab switchers (a), but save parent of them
            // (li)
            $messageEdit = $('#messageedit').click(viewEditor).parent();
            $messagePreview = $('#messagepreview').click(viewPreview).parent();
        };

        return me;
    })();

    /**
     * (view) Parse and show paste.
     *
     * @name   PasteViewer
     * @class
     */
    var PasteViewer = (function () {
        var me = {};

        var $placeholder,
            $prettyMessage,
            $prettyPrint,
            $plainText;

        var text,
            format = 'plaintext',
            isDisplayed = false,
            isChanged = true; // by default true as nothing was parsed yet

        /**
         * apply the set format on paste and displays it
         *
         * @name   PasteViewer.parsePaste
         * @private
         * @function
         */
        function parsePaste()
        {
            // skip parsing if no text is given
            if (text === '') {
                return;
            }

            // escape HTML entities, link URLs, sanitize
            var escapedLinkedText = Helper.urls2links(
                    $('<div />').text(text).html()
                ),
                sanitizedLinkedText = DOMPurify.sanitize(escapedLinkedText);
            $plainText.html(sanitizedLinkedText);
            $prettyPrint.html(sanitizedLinkedText);

            switch (format) {
                case 'markdown':
                    var converter = new showdown.Converter({
                        strikethrough: true,
                        tables: true,
                        tablesHeaderId: true,
                        simplifiedAutoLink: true,
                        excludeTrailingPunctuationFromURLs: true
                    });
                    // let showdown convert the HTML and sanitize HTML *afterwards*!
                    $plainText.html(
                        DOMPurify.sanitize(converter.makeHtml(text))
                    );
                    // add table classes from bootstrap css
                    $plainText.find('table').addClass('table-condensed table-bordered');
                    break;
                case 'syntaxhighlighting':
                    // yes, this is really needed to initialize the environment
                    if (typeof prettyPrint === 'function')
                    {
                        prettyPrint();
                    }

                    $prettyPrint.html(
                        DOMPurify.sanitize(
                            prettyPrintOne(escapedLinkedText, null, true)
                        )
                    );
                    // fall through, as the rest is the same
                default: // = 'plaintext'
                    $prettyPrint.css('white-space', 'pre-wrap');
                    $prettyPrint.css('word-break', 'normal');
                    $prettyPrint.removeClass('prettyprint');
            }
        }

        /**
         * displays the paste
         *
         * @name   PasteViewer.showPaste
         * @private
         * @function
         */
        function showPaste()
        {
            // instead of "nothing" better display a placeholder
            if (text === '') {
                $placeholder.removeClass('hidden');
                return;
            }
            // otherwise hide the placeholder
            $placeholder.addClass('hidden');

            switch (format) {
                case 'markdown':
                    $plainText.removeClass('hidden');
                    $prettyMessage.addClass('hidden');
                    break;
                default:
                    $plainText.addClass('hidden');
                    $prettyMessage.removeClass('hidden');
                    break;
            }
        }

        /**
         * sets the format in which the text is shown
         *
         * @name   PasteViewer.setFormat
         * @function
         * @param {string} newFormat the new format
         */
        me.setFormat = function(newFormat)
        {
            // skip if there is no update
            if (format === newFormat) {
                return;
            }

            // needs to update display too, if we switch from or to Markdown
            if (format === 'markdown' || newFormat === 'markdown') {
                isDisplayed = false;
            }

            format = newFormat;
            isChanged = true;
        };

        /**
         * returns the current format
         *
         * @name   PasteViewer.getFormat
         * @function
         * @return {string}
         */
        me.getFormat = function()
        {
            return format;
        };

        /**
         * returns whether the current view is pretty printed
         *
         * @name   PasteViewer.isPrettyPrinted
         * @function
         * @return {bool}
         */
        me.isPrettyPrinted = function()
        {
            return $prettyPrint.hasClass('prettyprinted');
        };

        /**
         * sets the text to show
         *
         * @name   PasteViewer.setText
         * @function
         * @param {string} newText the text to show
         */
        me.setText = function(newText)
        {
            if (text !== newText) {
                text = newText;
                isChanged = true;
            }
        };

        /**
         * gets the current cached text
         *
         * @name   PasteViewer.getText
         * @function
         * @return {string}
         */
        me.getText = function()
        {
            return text;
        };

        /**
         * show/update the parsed text (preview)
         *
         * @name   PasteViewer.run
         * @function
         */
        me.run = function()
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
         * @name   PasteViewer.hide
         * @function
         */
        me.hide = function()
        {
            if (!isDisplayed) {
                console.warn('PasteViewer was called to hide the parsed view, but it is already hidden.');
            }

            $plainText.addClass('hidden');
            $prettyMessage.addClass('hidden');
            $placeholder.addClass('hidden');
            AttachmentViewer.hideAttachmentPreview();

            isDisplayed = false;
        };

        /**
         * init status manager
         *
         * preloads jQuery elements
         *
         * @name   PasteViewer.init
         * @function
         */
        me.init = function()
        {
            $placeholder = $('#placeholder');
            $plainText = $('#plaintext');
            $prettyMessage = $('#prettymessage');
            $prettyPrint = $('#prettyprint');

            // check requirements
            if (typeof prettyPrintOne !== 'function') {
                Alert.showError([
                    'The library %s is not available. This may cause display errors.',
                    'pretty print'
                ]);
            }
            if (typeof showdown !== 'object') {
                Alert.showError([
                    'The library %s is not available. This may cause display errors.',
                    'showdown'
                ]);
            }

            // get default option from template/HTML or fall back to set value
            format = Model.getFormatDefault() || format;
            text = '';
            isDisplayed = false;
            isChanged = true;
        };

        return me;
    })();

    /**
     * (view) Show attachment and preview if possible
     *
     * @name   AttachmentViewer
     * @class
     */
    var AttachmentViewer = (function () {
        var me = {};

        var $attachmentLink;
        var $attachmentPreview;
        var $attachment;
        var attachmentData;
        var file;
        var $fileInput;
        var $dragAndDropFileName;
        var attachmentHasPreview = false;

        /**
         * sets the attachment but does not yet show it
         *
         * @name   AttachmentViewer.setAttachment
         * @function
         * @param {string} attachmentData - base64-encoded data of file
         * @param {string} fileName - optional, file name
         */
        me.setAttachment = function(attachmentData, fileName)
        {
            // IE does not support setting a data URI on an a element
            // Convert dataURI to a Blob and use msSaveBlob to download
            if (window.Blob && navigator.msSaveBlob) {
                $attachmentLink.off('click').on('click', function () {
                    // data URI format: data:[<mediaType>][;base64],<data>

                    // position in data URI string of where data begins
                    var base64Start = attachmentData.indexOf(',') + 1;
                    // position in data URI string of where mediaType ends
                    var mediaTypeEnd = attachmentData.indexOf(';');

                    // extract mediaType
                    var mediaType = attachmentData.substring(5, mediaTypeEnd);
                    // extract data and convert to binary
                    var decodedData = Base64.atob(attachmentData.substring(base64Start));

                    // Transform into a Blob
                    var decodedDataLength = decodedData.length;
                    var buf = new Uint8Array(decodedDataLength);

                    for (var i = 0; i < decodedDataLength; i++) {
                        buf[i] = decodedData.charCodeAt(i);
                    }

                    var blob = new window.Blob([ buf ], { type: mediaType });
                    navigator.msSaveBlob(blob, fileName);
                });
            } else {
                $attachmentLink.attr('href', attachmentData);
            }

            if (typeof fileName !== 'undefined') {
                $attachmentLink.attr('download', fileName);
            }

            me.handleAttachmentPreview($attachmentPreview, attachmentData);
        };

        /**
         * displays the attachment
         *
         * @name AttachmentViewer.showAttachment
         * @function
         */
        me.showAttachment = function()
        {
            $attachment.removeClass('hidden');

            if (attachmentHasPreview) {
                $attachmentPreview.removeClass('hidden');
            }
        };

        /**
         * removes the attachment
         *
         * This automatically hides the attachment containers too, to
         * prevent an inconsistent display.
         *
         * @name AttachmentViewer.removeAttachment
         * @function
         */
        me.removeAttachment = function()
        {
            if (!$attachment.length) {
                return;
            }
            me.hideAttachment();
            me.hideAttachmentPreview();
            $attachmentLink.removeAttr('href');
            $attachmentLink.removeAttr('download');
            $attachmentLink.off('click');
            $attachmentPreview.html('');

            AttachmentViewer.removeAttachmentData();
        };

        /**
         * removes the attachment data
         *
         * This removes the data, which would be uploaded otherwise.
         *
         * @name AttachmentViewer.removeAttachmentData
         * @function
         */
        me.removeAttachmentData = function()
        {
            file = undefined;
            attachmentData = undefined;
        };

        /**
         * Cleares the drag & drop data.
         *
         * @name AttachmentViewer.clearDragAndDrop
         * @function
         */
        me.clearDragAndDrop = function()
        {
            $dragAndDropFileName.text('');
        };

        /**
         * hides the attachment
         *
         * This will not hide the preview (see AttachmentViewer.hideAttachmentPreview
         * for that) nor will it hide the attachment link if it was moved somewhere
         * else (see AttachmentViewer.moveAttachmentTo).
         *
         * @name AttachmentViewer.hideAttachment
         * @function
         */
        me.hideAttachment = function()
        {
            $attachment.addClass('hidden');
        };

        /**
         * hides the attachment preview
         *
         * @name AttachmentViewer.hideAttachmentPreview
         * @function
         */
        me.hideAttachmentPreview = function()
        {
            if ($attachmentPreview) {
                $attachmentPreview.addClass('hidden');
            }
        };

        /**
         * checks if there is an attachment displayed
         *
         * @name   AttachmentViewer.hasAttachment
         * @function
         */
        me.hasAttachment = function()
        {
            if (!$attachment.length) {
                return false;
            }
            var link = $attachmentLink.prop('href');
            return (typeof link !== 'undefined' && link !== '');
        };

        /**
         * checks if there is attachment data (for preview!) available
         *
         * It returns true, when there is data that needs to be encrypted.
         *
         * @name   AttachmentViewer.hasAttachmentData
         * @function
         */
        me.hasAttachmentData = function()
        {
            if ($attachment.length) {
                return true;
            }
            return false;
        };

        /**
         * return the attachment
         *
         * @name   AttachmentViewer.getAttachment
         * @function
         * @returns {array}
         */
        me.getAttachment = function()
        {
            return [
                $attachmentLink.prop('href'),
                $attachmentLink.prop('download')
            ];
        };

        /**
         * moves the attachment link to another element
         *
         * It is advisable to hide the attachment afterwards (AttachmentViewer.hideAttachment)
         *
         * @name   AttachmentViewer.moveAttachmentTo
         * @function
         * @param {jQuery} $element - the wrapper/container element where this should be moved to
         * @param {string} label - the text to show (%s will be replaced with the file name), will automatically be translated
         */
        me.moveAttachmentTo = function($element, label)
        {
            // move elemement to new place
            $attachmentLink.appendTo($element);

            // update text
            I18n._($attachmentLink, label, $attachmentLink.attr('download'));
        };

        /**
         * read file data as data URL using the FileReader API
         *
         * @name   AttachmentViewer.readFileData
         * @private
         * @function
         * @param {object} loadedFile (optional) loaded file object
         * @see {@link https://developer.mozilla.org/en-US/docs/Web/API/FileReader#readAsDataURL()}
         */
        function readFileData(loadedFile) {
            if (typeof FileReader === 'undefined') {
                // revert loading status…
                me.hideAttachment();
                me.hideAttachmentPreview();
                Alert.showError('Your browser does not support uploading encrypted files. Please use a newer browser.');
                return;
            }

            var fileReader = new FileReader();
            if (loadedFile === undefined) {
                loadedFile = $fileInput[0].files[0];
                $dragAndDropFileName.text('');
            } else {
                $dragAndDropFileName.text(loadedFile.name);
            }

            file = loadedFile;

            fileReader.onload = function (event) {
                var dataURL = event.target.result;
                attachmentData = dataURL;

                if (Editor.isPreview()) {
                    me.handleAttachmentPreview($attachmentPreview, dataURL);
                    $attachmentPreview.removeClass('hidden');
                }
            };
            fileReader.readAsDataURL(loadedFile);
        }

        /**
         * handle the preview of files that can either be an image, video, audio or pdf element
         *
         * @name   AttachmentViewer.handleAttachmentPreview
         * @function
         * @argument {jQuery} $targetElement element where the preview should be appended
         * @argument {string} file as a data URL
         */
        me.handleAttachmentPreview = function ($targetElement, data) {
            if (data) {
                // source: https://developer.mozilla.org/en-US/docs/Web/API/FileReader#readAsDataURL()
                var mimeType = data.slice(
                    data.indexOf('data:') + 5,
                    data.indexOf(';base64,')
                );

                attachmentHasPreview = true;
                if (mimeType.match(/image\//i)) {
                    $targetElement.html(
                        $(document.createElement('img'))
                            .attr('src', data)
                            .attr('class', 'img-thumbnail')
                    );
                } else if (mimeType.match(/video\//i)) {
                    $targetElement.html(
                        $(document.createElement('video'))
                            .attr('controls', 'true')
                            .attr('autoplay', 'true')
                            .attr('class', 'img-thumbnail')

                            .append($(document.createElement('source'))
                            .attr('type', mimeType)
                            .attr('src', data))
                    );
                } else if (mimeType.match(/audio\//i)) {
                    $targetElement.html(
                        $(document.createElement('audio'))
                            .attr('controls', 'true')
                            .attr('autoplay', 'true')

                            .append($(document.createElement('source'))
                            .attr('type', mimeType)
                            .attr('src', data))
                    );
                } else if (mimeType.match(/\/pdf/i)) {
                    // PDFs are only displayed if the filesize is smaller than about 1MB (after base64 encoding).
                    // Bigger filesizes currently cause crashes in various browsers.
                    // See also: https://code.google.com/p/chromium/issues/detail?id=69227

                    // Firefox crashes with files that are about 1.5MB
                    // The performance with 1MB files is bearable
                    if (data.length > 1398488) {
                        Alert.showError('File too large, to display a preview. Please download the attachment.'); //TODO: is this error really neccessary?
                        return;
                    }

                    // Fallback for browsers, that don't support the vh unit
                    var clientHeight = $(window).height();

                    $targetElement.html(
                        $(document.createElement('embed'))
                            .attr('src', data)
                            .attr('type', 'application/pdf')
                            .attr('class', 'pdfPreview')
                            .css('height', clientHeight)
                    );
                } else {
                    attachmentHasPreview = false;
                }
            }
        };

        /**
         * attaches the file attachment drag & drop handler to the page
         *
         * @name   AttachmentViewer.addDragDropHandler
         * @private
         * @function
         */
        function addDragDropHandler() {
            if (typeof $fileInput === 'undefined' || $fileInput.length === 0) {
                return;
            }

            var ignoreDragDrop = function(event) {
                event.stopPropagation();
                event.preventDefault();
            };

            var drop = function(event) {
                var evt = event.originalEvent;
                evt.stopPropagation();
                evt.preventDefault();

                if ($fileInput) {
                    var file = evt.dataTransfer.files[0];
                    //Clear the file input:
                    $fileInput.wrap('<form>').closest('form').get(0).reset();
                    $fileInput.unwrap();
                    //Only works in Chrome:
                    //fileInput[0].files = e.dataTransfer.files;

                    readFileData(file);
                }
            };

            $(document).on('drop', drop);
            $(document).on('dragenter', ignoreDragDrop);
            $(document).on('dragover', ignoreDragDrop);
            $fileInput.on('change', function () {
                readFileData();
            });
        }

        /**
         * attaches the clipboard attachment handler to the page
         *
         * @name   AttachmentViewer.addClipboardEventHandler
         * @private
         * @function
         */
        function addClipboardEventHandler() {
            $(document).on('paste', function (event) {
                var items = (event.clipboardData || event.originalEvent.clipboardData).items;
                for (var i in items) {
                    if (items.hasOwnProperty(i)) {
                        var item = items[i];
                        if (item.kind === 'file') {
                            //Clear the file input:
                            $fileInput.wrap('<form>').closest('form').get(0).reset();
                            $fileInput.unwrap();

                            readFileData(item.getAsFile());
                        }
                    }
                }
            });
        }


        /**
         * getter for attachment data
         *
         * @name   AttachmentViewer.getAttachmentData
         * @function
         * @return {jQuery}
         */
        me.getAttachmentData = function () {
            return attachmentData;
        };

        /**
         * getter for attachment link
         *
         * @name   AttachmentViewer.getAttachmentLink
         * @function
         * @return {jQuery}
         */
        me.getAttachmentLink = function () {
            return $attachmentLink;
        };

        /**
         * getter for attachment preview
         *
         * @name   AttachmentViewer.getAttachmentPreview
         * @function
         * @return {jQuery}
         */
        me.getAttachmentPreview = function () {
            return $attachmentPreview;
        };

        /**
         * getter for file data, returns the file contents
         *
         * @name   AttachmentViewer.getFile
         * @function
         * @return {string}
         */
        me.getFile = function () {
            return file;
        };

        /**
         * initiate
         *
         * preloads jQuery elements
         *
         * @name   AttachmentViewer.init
         * @function
         */
        me.init = function()
        {
            $attachment = $('#attachment');
            if($attachment.length){
                $attachmentLink = $('#attachment a');
                $attachmentPreview = $('#attachmentPreview');
                $dragAndDropFileName = $('#dragAndDropFileName');

                $fileInput = $('#file');
                addDragDropHandler();
                addClipboardEventHandler();
            }
        }

        return me;
    })();

    /**
     * (view) Shows discussion thread and handles replies
     *
     * @name   DiscussionViewer
     * @class
     */
    var DiscussionViewer = (function () {
        var me = {};

        var $commentTail,
            $discussion,
            $reply,
            $replyMessage,
            $replyNickname,
            $replyStatus,
            $commentContainer;

        var replyCommentId;

        /**
         * initializes the templates
         *
         * @name   DiscussionViewer.initTemplates
         * @private
         * @function
         */
        function initTemplates()
        {
            $reply = Model.getTemplate('reply');
            $replyMessage = $reply.find('#replymessage');
            $replyNickname = $reply.find('#nickname');
            $replyStatus = $reply.find('#replystatus');

            // cache jQuery elements
            $commentTail = Model.getTemplate('commenttail');
        }

        /**
         * open the comment entry when clicking the "Reply" button of a comment
         *
         * @name   DiscussionViewer.openReply
         * @private
         * @function
         * @param  {Event} event
         */
        function openReply(event)
        {
            var $source = $(event.target);

            // clear input
            $replyMessage.val('');
            $replyNickname.val('');

            // get comment id from source element
            replyCommentId = $source.parent().prop('id').split('_')[1];

            // move to correct position
            $source.after($reply);

            // show
            $reply.removeClass('hidden');
            $replyMessage.focus();

            event.preventDefault();
        }

        /**
         * custom handler for displaying notifications in own status message area
         *
         * @name   DiscussionViewer.handleNotification
         * @function
         * @param  {string} alertType
         * @return {bool|jQuery}
         */
        me.handleNotification = function(alertType)
        {
            // ignore loading messages
            if (alertType === 'loading') {
                return false;
            }

            if (alertType === 'danger') {
                $replyStatus.removeClass('alert-info');
                $replyStatus.addClass('alert-danger');
                $replyStatus.find(':first').removeClass('glyphicon-alert');
                $replyStatus.find(':first').addClass('glyphicon-info-sign');
            } else {
                $replyStatus.removeClass('alert-danger');
                $replyStatus.addClass('alert-info');
                $replyStatus.find(':first').removeClass('glyphicon-info-sign');
                $replyStatus.find(':first').addClass('glyphicon-alert');
            }

            return $replyStatus;
        };

        /**
         * adds another comment
         *
         * @name   DiscussionViewer.addComment
         * @function
         * @param {object} comment
         * @param {string} commentText
         * @param {string} nickname
         */
        me.addComment = function(comment, commentText, nickname)
        {
            if (commentText === '') {
                commentText = 'comment decryption failed';
            }

            // create new comment based on template
            var $commentEntry = Model.getTemplate('comment');
            $commentEntry.prop('id', 'comment_' + comment.id);
            var $commentEntryData = $commentEntry.find('div.commentdata');

            // set & parse text
            $commentEntryData.html(
                DOMPurify.sanitize(
                    Helper.urls2links(commentText)
                )
            );

            // set nickname
            if (nickname.length > 0) {
                $commentEntry.find('span.nickname').text(nickname);
            } else {
                $commentEntry.find('span.nickname').html('<i></i>');
                I18n._($commentEntry.find('span.nickname i'), 'Anonymous');
            }

            // set date
            $commentEntry.find('span.commentdate')
                      .text(' (' + (new Date(comment.meta.postdate * 1000).toLocaleString()) + ')')
                      .attr('title', 'CommentID: ' + comment.id);

            // if an avatar is available, display it
            if (comment.meta.vizhash) {
                $commentEntry.find('span.nickname')
                             .before(
                                '<img src="' + comment.meta.vizhash + '" class="vizhash" /> '
                             );
                $(document).on('languageLoaded', function () {
                    $commentEntry.find('img.vizhash')
                                 .prop('title', I18n._('Avatar generated from IP address'));
                });
            }

            // starting point (default value/fallback)
            var $place = $commentContainer;

            // if parent comment exists
            var $parentComment = $('#comment_' + comment.parentid);
            if ($parentComment.length) {
                // use parent as position for new comment, so it is shifted
                // to the right
                $place = $parentComment;
            }

            // finally append comment
            $place.append($commentEntry);
        };

        /**
         * finishes the discussion area after last comment
         *
         * @name   DiscussionViewer.finishDiscussion
         * @function
         */
        me.finishDiscussion = function()
        {
            // add 'add new comment' area
            $commentContainer.append($commentTail);

            // show discussions
            $discussion.removeClass('hidden');
        };

        /**
         * removes the old discussion and prepares everything for creating a new
         * one.
         *
         * @name   DiscussionViewer.prepareNewDiscussion
         * @function
         */
        me.prepareNewDiscussion = function()
        {
            $commentContainer.html('');
            $discussion.addClass('hidden');

            // (re-)init templates
            initTemplates();
        };

        /**
         * returns the users message from the reply form
         *
         * @name   DiscussionViewer.getReplyMessage
         * @function
         * @return {String}
         */
        me.getReplyMessage = function()
        {
            return $replyMessage.val();
        };

        /**
         * returns the users nickname (if any) from the reply form
         *
         * @name   DiscussionViewer.getReplyNickname
         * @function
         * @return {String}
         */
        me.getReplyNickname = function()
        {
            return $replyNickname.val();
        };

        /**
         * returns the id of the parent comment the user is replying to
         *
         * @name   DiscussionViewer.getReplyCommentId
         * @function
         * @return {int|undefined}
         */
        me.getReplyCommentId = function()
        {
            return replyCommentId;
        };

        /**
         * highlights a specific comment and scrolls to it if necessary
         *
         * @name   DiscussionViewer.highlightComment
         * @function
         * @param {string} commentId
         * @param {bool} fadeOut - whether to fade out the comment
         */
        me.highlightComment = function(commentId, fadeOut)
        {
            var $comment = $('#comment_' + commentId);
            // in case comment does not exist, cancel
            if ($comment.length === 0) {
                return;
            }

            $comment.addClass('highlight');
            var highlightComment = function () {
                if (fadeOut === true) {
                    setTimeout(function () {
                        $comment.removeClass('highlight');
                    }, 300);
                }
            };

            if (UiHelper.isVisible($comment)) {
                return highlightComment();
            }

            UiHelper.scrollTo($comment, 100, 'swing', highlightComment);
        };

        /**
         * initiate
         *
         * preloads jQuery elements
         *
         * @name   DiscussionViewer.init
         * @function
         */
        me.init = function()
        {
            // bind events to templates (so they are later cloned)
            $('#commenttailtemplate, #commenttemplate').find('button').on('click', openReply);
            $('#replytemplate').find('button').on('click', PasteEncrypter.sendComment);

            $commentContainer = $('#commentcontainer');
            $discussion = $('#discussion');
        };

        return me;
    })();

    /**
     * Manage top (navigation) bar
     *
     * @name   TopNav
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var TopNav = (function (window, document) {
        var me = {};

        var createButtonsDisplayed = false;
        var viewButtonsDisplayed = false;

        var $attach,
            $burnAfterReading,
            $burnAfterReadingOption,
            $cloneButton,
            $customAttachment,
            $expiration,
            $fileRemoveButton,
            $fileWrap,
            $formatter,
            $newButton,
            $openDiscussion,
            $openDiscussionOption,
            $password,
            $passwordInput,
            $rawTextButton,
            $qrCodeLink,
            $sendButton,
            $retryButton;

        var pasteExpiration = '1week',
            retryButtonCallback;

        /**
         * set the expiration on bootstrap templates in dropdown
         *
         * @name   TopNav.updateExpiration
         * @private
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
         * @name   TopNav.updateFormat
         * @private
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
            PasteViewer.setFormat(newFormat);

            // update preview
            if (Editor.isPreview()) {
                PasteViewer.run();
            }

            event.preventDefault();
        }

        /**
         * when "burn after reading" is checked, disable discussion
         *
         * @name   TopNav.changeBurnAfterReading
         * @private
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
         * @name   TopNav.changeOpenDiscussion
         * @private
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
         * @name   TopNav.rawText
         * @private
         * @function
         */
        function rawText()
        {
            TopNav.hideAllButtons();
            Alert.showLoading('Showing raw text…', 'time');
            var paste = PasteViewer.getText();

            // push a new state to allow back navigation with browser back button
            history.pushState(
                {type: 'raw'},
                document.title,
                // recreate paste URL
                Helper.baseUri() + '?' + Model.getPasteId() + '#' +
                Model.getPasteKey()
            );

            // we use text/html instead of text/plain to avoid a bug when
            // reloading the raw text view (it reverts to type text/html)
            var $head = $('head').children().not('noscript, script, link[type="text/css"]');
            var newDoc = document.open('text/html', 'replace');
            newDoc.write('<!DOCTYPE html><html><head>');
            for (var i = 0; i < $head.length; i++) {
                newDoc.write($head[i].outerHTML);
            }
            newDoc.write('</head><body><pre>' + DOMPurify.sanitize($('<div />').text(paste).html()) + '</pre></body></html>');
            newDoc.close();
        }

        /**
         * saves the language in a cookie and reloads the page
         *
         * @name   TopNav.setLanguage
         * @private
         * @function
         * @param  {Event} event
         */
        function setLanguage(event)
        {
            document.cookie = 'lang=' + $(event.target).data('lang');
            UiHelper.reloadHome();
        }

        /**
         * hides all messages and creates a new paste
         *
         * @name   TopNav.clickNewPaste
         * @private
         * @function
         */
        function clickNewPaste()
        {
            Controller.hideStatusMessages();
            Controller.newPaste();
        }

        /**
         * retrys some callback registered before
         *
         * @name   TopNav.clickRetryButton
         * @private
         * @function
         * @param  {Event} event
         */
        function clickRetryButton(event)
        {
            retryButtonCallback(event);
        }

        /**
         * removes the existing attachment
         *
         * @name   TopNav.removeAttachment
         * @private
         * @function
         * @param  {Event} event
         */
        function removeAttachment(event)
        {
            // if custom attachment is used, remove it first
            if (!$customAttachment.hasClass('hidden')) {
                AttachmentViewer.removeAttachment();
                $customAttachment.addClass('hidden');
                $fileWrap.removeClass('hidden');
            }

            // in any case, remove saved attachment data
            AttachmentViewer.removeAttachmentData();

            // hide UI for selected files
            // our up-to-date jQuery can handle it :)
            $fileWrap.find('input').val('');
            AttachmentViewer.clearDragAndDrop();

            // pevent '#' from appearing in the URL
            event.preventDefault();
        }

        /**
         * Shows the QR code of the current paste (URL).
         *
         * @name   TopNav.displayQrCode
         * @private
         * @function
         */
        function displayQrCode()
        {
            var qrCanvas = kjua({
                render: 'canvas',
                text: window.location.href
            });
            $('#qrcode-display').html(qrCanvas);
        }

        /**
         * Shows all navigation elements for viewing an existing paste
         *
         * @name   TopNav.showViewButtons
         * @function
         */
        me.showViewButtons = function()
        {
            if (viewButtonsDisplayed) {
                console.warn('showViewButtons: view buttons are already displayed');
                return;
            }

            $newButton.removeClass('hidden');
            $cloneButton.removeClass('hidden');
            $rawTextButton.removeClass('hidden');
            $qrCodeLink.removeClass('hidden');

            viewButtonsDisplayed = true;
        };

        /**
         * Hides all navigation elements for viewing an existing paste
         *
         * @name   TopNav.hideViewButtons
         * @function
         */
        me.hideViewButtons = function()
        {
            if (!viewButtonsDisplayed) {
                console.warn('hideViewButtons: view buttons are already hidden');
                return;
            }

            $cloneButton.addClass('hidden');
            $newButton.addClass('hidden');
            $rawTextButton.addClass('hidden');
            $qrCodeLink.addClass('hidden');

            viewButtonsDisplayed = false;
        };

        /**
         * Hides all elements belonging to existing pastes
         *
         * @name   TopNav.hideAllButtons
         * @function
         */
        me.hideAllButtons = function()
        {
            me.hideViewButtons();
            me.hideCreateButtons();
        };

        /**
         * shows all elements needed when creating a new paste
         *
         * @name   TopNav.showCreateButtons
         * @function
         */
        me.showCreateButtons = function()
        {
            if (createButtonsDisplayed) {
                console.warn('showCreateButtons: create buttons are already displayed');
                return;
            }

            $attach.removeClass('hidden');
            $burnAfterReadingOption.removeClass('hidden');
            $expiration.removeClass('hidden');
            $formatter.removeClass('hidden');
            $newButton.removeClass('hidden');
            $openDiscussionOption.removeClass('hidden');
            $password.removeClass('hidden');
            $sendButton.removeClass('hidden');

            createButtonsDisplayed = true;
        };

        /**
         * shows all elements needed when creating a new paste
         *
         * @name   TopNav.hideCreateButtons
         * @function
         */
        me.hideCreateButtons = function()
        {
            if (!createButtonsDisplayed) {
                console.warn('hideCreateButtons: create buttons are already hidden');
                return;
            }

            $newButton.addClass('hidden');
            $sendButton.addClass('hidden');
            $expiration.addClass('hidden');
            $formatter.addClass('hidden');
            $burnAfterReadingOption.addClass('hidden');
            $openDiscussionOption.addClass('hidden');
            $password.addClass('hidden');
            $attach.addClass('hidden');

            createButtonsDisplayed = false;
        };

        /**
         * only shows the "new paste" button
         *
         * @name   TopNav.showNewPasteButton
         * @function
         */
        me.showNewPasteButton = function()
        {
            $newButton.removeClass('hidden');
        };

        /**
         * only shows the "retry" button
         *
         * @name   TopNav.showRetryButton
         * @function
         */
        me.showRetryButton = function()
        {
            $retryButton.removeClass('hidden');
        }

        /**
         * hides the "retry" button
         *
         * @name   TopNav.hideRetryButton
         * @function
         */
        me.hideRetryButton = function()
        {
            $retryButton.addClass('hidden');
        }

        /**
         * only hides the clone button
         *
         * @name   TopNav.hideCloneButton
         * @function
         */
        me.hideCloneButton = function()
        {
            $cloneButton.addClass('hidden');
        };

        /**
         * only hides the raw text button
         *
         * @name   TopNav.hideRawButton
         * @function
         */
        me.hideRawButton = function()
        {
            $rawTextButton.addClass('hidden');
        };

        /**
         * hides the file selector in attachment
         *
         * @name   TopNav.hideFileSelector
         * @function
         */
        me.hideFileSelector = function()
        {
            $fileWrap.addClass('hidden');
        };


        /**
         * shows the custom attachment
         *
         * @name   TopNav.showCustomAttachment
         * @function
         */
        me.showCustomAttachment = function()
        {
            $customAttachment.removeClass('hidden');
        };

        /**
         * collapses the navigation bar, only if expanded
         *
         * @name   TopNav.collapseBar
         * @function
         */
        me.collapseBar = function()
        {
            if ($('#navbar').attr('aria-expanded') === 'true') {
                $('.navbar-toggle').click();
            }
        };

        /**
         * returns the currently set expiration time
         *
         * @name   TopNav.getExpiration
         * @function
         * @return {int}
         */
        me.getExpiration = function()
        {
            return pasteExpiration;
        };

        /**
         * returns the currently selected file(s)
         *
         * @name   TopNav.getFileList
         * @function
         * @return {FileList|null}
         */
        me.getFileList = function()
        {
            var $file = $('#file');

            // if no file given, return null
            if (!$file.length || !$file[0].files.length) {
                return null;
            }

            // ensure the selected file is still accessible
            if (!($file[0].files && $file[0].files[0])) {
                return null;
            }

            return $file[0].files;
        };

        /**
         * returns the state of the burn after reading checkbox
         *
         * @name   TopNav.getExpiration
         * @function
         * @return {bool}
         */
        me.getBurnAfterReading = function()
        {
            return $burnAfterReading.is(':checked');
        };

        /**
         * returns the state of the discussion checkbox
         *
         * @name   TopNav.getOpenDiscussion
         * @function
         * @return {bool}
         */
        me.getOpenDiscussion = function()
        {
            return $openDiscussion.is(':checked');
        };

        /**
         * returns the entered password
         *
         * @name   TopNav.getPassword
         * @function
         * @return {string}
         */
        me.getPassword = function()
        {
            return $passwordInput.val();
        };

        /**
         * returns the element where custom attachments can be placed
         *
         * Used by AttachmentViewer when an attachment is cloned here.
         *
         * @name   TopNav.getCustomAttachment
         * @function
         * @return {jQuery}
         */
        me.getCustomAttachment = function()
        {
            return $customAttachment;
        };

        /**
         * Set a function to call when the retry button is clicked.
         *
         * @name   TopNav.setRetryCallback
         * @function
         * @param {function} callback
         */
        me.setRetryCallback = function(callback)
        {
            retryButtonCallback = callback;
        }

        /**
         * init navigation manager
         *
         * preloads jQuery elements
         *
         * @name   TopNav.init
         * @function
         */
        me.init = function()
        {
            $attach = $('#attach');
            $burnAfterReading = $('#burnafterreading');
            $burnAfterReadingOption = $('#burnafterreadingoption');
            $cloneButton = $('#clonebutton');
            $customAttachment = $('#customattachment');
            $expiration = $('#expiration');
            $fileRemoveButton = $('#fileremovebutton');
            $fileWrap = $('#filewrap');
            $formatter = $('#formatter');
            $newButton = $('#newbutton');
            $openDiscussion = $('#opendiscussion');
            $openDiscussionOption = $('#opendiscussionoption');
            $password = $('#password');
            $passwordInput = $('#passwordinput');
            $rawTextButton = $('#rawtextbutton');
            $retryButton = $('#retrybutton');
            $sendButton = $('#sendbutton');
            $qrCodeLink = $('#qrcodelink');

            // bootstrap template drop down
            $('#language ul.dropdown-menu li a').click(setLanguage);
            // page template drop down
            $('#language select option').click(setLanguage);

            // bind events
            $burnAfterReading.change(changeBurnAfterReading);
            $openDiscussionOption.change(changeOpenDiscussion);
            $newButton.click(clickNewPaste);
            $sendButton.click(PasteEncrypter.sendPaste);
            $cloneButton.click(Controller.clonePaste);
            $rawTextButton.click(rawText);
            $retryButton.click(clickRetryButton);
            $fileRemoveButton.click(removeAttachment);
            $qrCodeLink.click(displayQrCode);

            // bootstrap template drop downs
            $('ul.dropdown-menu li a', $('#expiration').parent()).click(updateExpiration);
            $('ul.dropdown-menu li a', $('#formatter').parent()).click(updateFormat);

            // initiate default state of checkboxes
            changeBurnAfterReading();
            changeOpenDiscussion();

            // get default value from template or fall back to set value
            pasteExpiration = Model.getExpirationDefault() || pasteExpiration;

            createButtonsDisplayed = false;
            viewButtonsDisplayed = false;
        };

        return me;
    })(window, document);

    /**
     * Responsible for AJAX requests, transparently handles encryption…
     *
     * @name   Uploader
     * @class
     */
    var Uploader = (function () {
        var me = {};

        var successFunc = null,
            failureFunc = null,
            url,
            data,
            symmetricKey,
            password;

        /**
         * public variable ('constant') for errors to prevent magic numbers
         *
         * @name   Uploader.error
         * @readonly
         * @enum   {Object}
         */
        me.error = {
            okay: 0,
            custom: 1,
            unknown: 2,
            serverError: 3
        };

        /**
         * ajaxHeaders to send in AJAX requests
         *
         * @name   Uploader.ajaxHeaders
         * @private
         * @readonly
         * @enum   {Object}
         */
        var ajaxHeaders = {'X-Requested-With': 'JSONHttpRequest'};

        /**
         * called after successful upload
         *
         * @name   Uploader.checkCryptParameters
         * @private
         * @function
         * @throws {string}
         */
        function checkCryptParameters()
        {
            // workaround for this nasty 'bug' in ECMAScript
            // see https://stackoverflow.com/questions/18808226/why-is-typeof-null-object
            var typeOfKey = typeof symmetricKey;
            if (symmetricKey === null) {
                typeOfKey = 'null';
            }

            // in case of missing preparation, throw error
            switch (typeOfKey) {
                case 'string':
                    // already set, all right
                    return;
                case 'null':
                    // needs to be generated auto-generate
                    symmetricKey = CryptTool.getSymmetricKey();
                    break;
                default:
                    console.error('current invalid symmetricKey:', symmetricKey);
                    throw 'symmetricKey is invalid, probably the module was not prepared';
            }
            // password is optional
        }

        /**
         * called after successful upload
         *
         * @name   Uploader.success
         * @private
         * @function
         * @param {int} status
         * @param {int} result - optional
         */
        function success(status, result)
        {
            // add useful data to result
            result.encryptionKey = symmetricKey;
            result.requestData = data;

            if (successFunc !== null) {
                successFunc(status, result);
            }
        }

        /**
         * called after a upload failure
         *
         * @name   Uploader.fail
         * @private
         * @function
         * @param {int} status - internal code
         * @param {int} result - original error code
         */
        function fail(status, result)
        {
            if (failureFunc !== null) {
                failureFunc(status, result);
            }
        }

        /**
         * actually uploads the data
         *
         * @name   Uploader.run
         * @function
         */
        me.run = function()
        {
            $.ajax({
                type: 'POST',
                url: url,
                data: data,
                dataType: 'json',
                headers: ajaxHeaders,
                success: function(result) {
                    if (result.status === 0) {
                        success(0, result);
                    } else if (result.status === 1) {
                        fail(1, result);
                    } else {
                        fail(2, result);
                    }
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error(textStatus, errorThrown);
                fail(3, jqXHR);
            });
        };

        /**
         * set success function
         *
         * @name   Uploader.setUrl
         * @function
         * @param {function} newUrl
         */
        me.setUrl = function(newUrl)
        {
            url = newUrl;
        };

        /**
         * sets the password to use (first value) and optionally also the
         * encryption key (not recommend, it is automatically generated).
         *
         * Note: Call this after prepare() as prepare() resets these values.
         *
         * @name   Uploader.setCryptValues
         * @function
         * @param {string} newPassword
         * @param {string} newKey       - optional
         */
        me.setCryptParameters = function(newPassword, newKey)
        {
            password = newPassword;

            if (typeof newKey !== 'undefined') {
                symmetricKey = newKey;
            }
        };

        /**
         * set success function
         *
         * @name   Uploader.setSuccess
         * @function
         * @param {function} func
         */
        me.setSuccess = function(func)
        {
            successFunc = func;
        };

        /**
         * set failure function
         *
         * @name   Uploader.setFailure
         * @function
         * @param {function} func
         */
        me.setFailure = function(func)
        {
            failureFunc = func;
        };

        /**
         * prepares a new upload
         *
         * Call this when doing a new upload to reset any data from potential
         * previous uploads. Must be called before any other method of this
         * module.
         *
         * @name   Uploader.prepare
         * @function
         * @return {object}
         */
        me.prepare = function()
        {
            // entropy should already be checked!

            // reset password
            password = '';

            // reset key, so it a new one is generated when it is used
            symmetricKey = null;

            // reset data
            successFunc = null;
            failureFunc = null;
            url = Helper.baseUri();
            data = {};
        };

        /**
         * encrypts and sets the data
         *
         * @name   Uploader.setData
         * @function
         * @param {string} index
         * @param {mixed} element
         */
        me.setData = function(index, element)
        {
            checkCryptParameters();
            data[index] = CryptTool.cipher(symmetricKey, password, element);
        };

        /**
         * set the additional metadata to send unencrypted
         *
         * @name   Uploader.setUnencryptedData
         * @function
         * @param {string} index
         * @param {mixed} element
         */
        me.setUnencryptedData = function(index, element)
        {
            data[index] = element;
        };

        /**
         * set the additional metadata to send unencrypted passed at once
         *
         * @name   Uploader.setUnencryptedData
         * @function
         * @param {object} newData
         */
        me.setUnencryptedBulkData = function(newData)
        {
            $.extend(data, newData);
        };

        /**
         * Helper, which parses shows a general error message based on the result of the Uploader
         *
         * @name    Uploader.parseUploadError
         * @function
         * @param {int} status
         * @param {object} data
         * @param {string} doThisThing - a human description of the action, which was tried
         * @return {array}
         */
        me.parseUploadError = function(status, data, doThisThing) {
            var errorArray;

            switch (status) {
                case me.error.custom:
                    errorArray = ['Could not ' + doThisThing + ': %s', data.message];
                    break;
                case me.error.unknown:
                    errorArray = ['Could not ' + doThisThing + ': %s', I18n._('unknown status')];
                    break;
                case me.error.serverError:
                    errorArray = ['Could not ' + doThisThing + ': %s', I18n._('server error or not responding')];
                    break;
                default:
                    errorArray = ['Could not ' + doThisThing + ': %s', I18n._('unknown error')];
                    break;
            }

            return errorArray;
        };

        /**
         * init Uploader
         *
         * @name   Uploader.init
         * @function
         */
        me.init = function()
        {
            // nothing yet
        };

        return me;
    })();

    /**
     * (controller) Responsible for encrypting paste and sending it to server.
     *
     * Does upload, encryption is done transparently by Uploader.
     *
     * @name PasteEncrypter
     * @class
     */
    var PasteEncrypter = (function () {
        var me = {};

        var requirementsChecked = false;

        /**
         * checks whether there is a suitable amount of entrophy
         *
         * @name PasteEncrypter.checkRequirements
         * @private
         * @function
         * @param {function} retryCallback - the callback to execute to retry the upload
         * @return {bool}
         */
        function checkRequirements(retryCallback) {
            // skip double requirement checks
            if (requirementsChecked === true) {
                return true;
            }

            if (!CryptTool.isEntropyReady()) {
                // display a message and wait
                Alert.showStatus('Please move your mouse for more entropy…');

                CryptTool.addEntropySeedListener(retryCallback);
                return false;
            }

            requirementsChecked = true;

            return true;
        }

        /**
         * called after successful paste upload
         *
         * @name PasteEncrypter.showCreatedPaste
         * @private
         * @function
         * @param {int} status
         * @param {object} data
         */
        function showCreatedPaste(status, data) {
            Alert.hideLoading();

            var url = Helper.baseUri() + '?' + data.id + '#' + data.encryptionKey,
                deleteUrl = Helper.baseUri() + '?pasteid=' + data.id + '&deletetoken=' + data.deletetoken;

            Alert.hideMessages();

            // show notification
            PasteStatus.createPasteNotification(url, deleteUrl);

            // show new URL in browser bar
            history.pushState({type: 'newpaste'}, document.title, url);

            TopNav.showViewButtons();
            TopNav.hideRawButton();
            Editor.hide();

            // parse and show text
            // (preparation already done in me.sendPaste())
            PasteViewer.run();
        }

        /**
         * called after successful comment upload
         *
         * @name PasteEncrypter.showUploadedComment
         * @private
         * @function
         * @param {int} status
         * @param {object} data
         */
        function showUploadedComment(status, data) {
            // show success message
            Alert.showStatus('Comment posted.');

            // reload paste
            Controller.refreshPaste(function () {
                // highlight sent comment
                DiscussionViewer.highlightComment(data.id, true);
                // reset error handler
                Alert.setCustomHandler(null);
            });
        }

        /**
         * adds attachments to the Uploader
         *
         * @name PasteEncrypter.encryptAttachments
         * @private
         * @function
         * @param {function} callback - excuted when action is successful
         */
        function encryptAttachments(callback) {
            var file = AttachmentViewer.getAttachmentData();

            if (typeof file !== 'undefined' && file !== null) {
                var fileName = AttachmentViewer.getFile().name;

                Uploader.setData('attachment', file);
                Uploader.setData('attachmentname', fileName);

                // run callback
                return callback();
            } else if (AttachmentViewer.hasAttachment()) {
                // fall back to cloned part
                var attachment = AttachmentViewer.getAttachment();

                Uploader.setData('attachment', attachment[0]);
                Uploader.setData('attachmentname', attachment[1]);
                return callback();
            } else {
                // if there are no attachments, this is of course still successful
                return callback();
            }
        }

        /**
         * send a reply in a discussion
         *
         * @name   PasteEncrypter.sendComment
         * @function
         */
        me.sendComment = function()
        {
            Alert.hideMessages();
            Alert.setCustomHandler(DiscussionViewer.handleNotification);

            // UI loading state
            TopNav.hideAllButtons();
            Alert.showLoading('Sending comment…', 'cloud-upload');

            // get data
            var plainText = DiscussionViewer.getReplyMessage(),
                nickname = DiscussionViewer.getReplyNickname(),
                parentid = DiscussionViewer.getReplyCommentId();

            // do not send if there is no data
            if (plainText.length === 0) {
                // revert loading status…
                Alert.hideLoading();
                Alert.setCustomHandler(null);
                TopNav.showViewButtons();
                return;
            }

            // check entropy
            if (!checkRequirements(function () {
                me.sendComment();
            })) {
                return; // to prevent multiple executions
            }

            // prepare Uploader
            Uploader.prepare();
            Uploader.setCryptParameters(Prompt.getPassword(), Model.getPasteKey());

            // set success/fail functions
            Uploader.setSuccess(showUploadedComment);
            Uploader.setFailure(function (status, data) {
                // revert loading status…
                Alert.hideLoading();
                TopNav.showViewButtons();

                // show error message
                Alert.showError(
                    Uploader.parseUploadError(status, data, 'post comment')
                );

                // reset error handler
                Alert.setCustomHandler(null);
            });

            // fill it with unencrypted params
            Uploader.setUnencryptedData('pasteid', Model.getPasteId());
            if (typeof parentid === 'undefined') {
                // if parent id is not set, this is the top-most comment, so use
                // paste id as parent, as the root element of the discussion tree
                Uploader.setUnencryptedData('parentid', Model.getPasteId());
            } else {
                Uploader.setUnencryptedData('parentid', parentid);
            }

            // encrypt data
            Uploader.setData('data', plainText);

            if (nickname.length > 0) {
                Uploader.setData('nickname', nickname);
            }

            Uploader.run();
        };

        /**
         * sends a new paste to server
         *
         * @name   PasteEncrypter.sendPaste
         * @function
         */
        me.sendPaste = function()
        {
            // hide previous (error) messages
            Controller.hideStatusMessages();

            // UI loading state
            TopNav.hideAllButtons();
            Alert.showLoading('Sending paste…', 'cloud-upload');
            TopNav.collapseBar();

            // get data
            var plainText = Editor.getText(),
                format = PasteViewer.getFormat(),
                // the methods may return different values if no files are attached (null, undefined or false)
                files = TopNav.getFileList() || AttachmentViewer.getFile() || AttachmentViewer.hasAttachment();

            // do not send if there is no data
            if (plainText.length === 0 && !files) {
                // revert loading status…
                Alert.hideLoading();
                TopNav.showCreateButtons();
                return;
            }

            // check entropy
            if (!checkRequirements(function () {
                me.sendPaste();
            })) {
                return; // to prevent multiple executions
            }

            // prepare Uploader
            Uploader.prepare();
            Uploader.setCryptParameters(TopNav.getPassword());

            // set success/fail functions
            Uploader.setSuccess(showCreatedPaste);
            Uploader.setFailure(function (status, data) {
                // revert loading status…
                Alert.hideLoading();
                TopNav.showCreateButtons();

                // show error message
                Alert.showError(
                    Uploader.parseUploadError(status, data, 'create paste')
                );
            });

            // fill it with unencrypted submitted options
            Uploader.setUnencryptedBulkData({
                expire:           TopNav.getExpiration(),
                formatter:        format,
                burnafterreading: TopNav.getBurnAfterReading() ? 1 : 0,
                opendiscussion:   TopNav.getOpenDiscussion() ? 1 : 0
            });

            // prepare PasteViewer for later preview
            PasteViewer.setText(plainText);
            PasteViewer.setFormat(format);

            // encrypt cipher data
            Uploader.setData('data', plainText);

            // encrypt attachments
            encryptAttachments(
                function () {
                    // send data
                    Uploader.run();
                }
            );
        };

        /**
         * initialize
         *
         * @name   PasteEncrypter.init
         * @function
         */
        me.init = function()
        {
            // nothing yet
        };

        return me;
    })();

    /**
     * (controller) Responsible for decrypting cipherdata and passing data to view.
     *
     * Only decryption, no download.
     *
     * @name PasteDecrypter
     * @class
     */
    var PasteDecrypter = (function () {
        var me = {};

        /**
         * decrypt data or prompts for password in case of failure
         *
         * @name   PasteDecrypter.decryptOrPromptPassword
         * @private
         * @function
         * @param  {string} key
         * @param  {string} password - optional, may be an empty string
         * @param  {string} cipherdata
         * @throws {string}
         * @return {false|string} false, when unsuccessful or string (decrypted data)
         */
        function decryptOrPromptPassword(key, password, cipherdata)
        {
            // try decryption without password
            var plaindata = CryptTool.decipher(key, password, cipherdata);

            // if it fails, request password
            if (plaindata.length === 0 && password.length === 0) {
                // show prompt
                Prompt.requestPassword();

                // Thus, we cannot do anything yet, we need to wait for the user
                // input.
                return false;
            }

            // if all tries failed, we can only return an error
            if (plaindata.length === 0) {
                throw 'failed to decipher data';
            }

            return plaindata;
        }

        /**
         * decrypt the actual paste text
         *
         * @name   PasteDecrypter.decryptPaste
         * @private
         * @function
         * @param  {object} paste - paste data in object form
         * @param  {string} key
         * @param  {string} password
         * @param  {bool} ignoreError - ignore decryption errors iof set to true
         * @return {bool} whether action was successful
         * @throws {string}
         */
        function decryptPaste(paste, key, password, ignoreError)
        {
            var plaintext;
            if (ignoreError === true) {
                plaintext = CryptTool.decipher(key, password, paste.data);
            } else {
                try {
                    plaintext = decryptOrPromptPassword(key, password, paste.data);
                } catch (err) {
                    throw 'failed to decipher paste text: ' + err;
                }
                if (plaintext === false) {
                    return false;
                }
            }

            // on success show paste
            PasteViewer.setFormat(paste.meta.formatter);
            PasteViewer.setText(plaintext);
            // trigger to show the text (attachment loaded afterwards)
            PasteViewer.run();

            return true;
        }

        /**
         * decrypts any attachment
         *
         * @name   PasteDecrypter.decryptAttachment
         * @private
         * @function
         * @param  {object} paste - paste data in object form
         * @param  {string} key
         * @param  {string} password
         * @return {bool} whether action was successful
         * @throws {string}
         */
        function decryptAttachment(paste, key, password)
        {
            var attachment, attachmentName;

            // decrypt attachment
            try {
                attachment = decryptOrPromptPassword(key, password, paste.attachment);
            } catch (err) {
                throw 'failed to decipher attachment: ' + err;
            }
            if (attachment === false) {
                return false;
            }

            // decrypt attachment name
            if (paste.attachmentname) {
                try {
                    attachmentName = decryptOrPromptPassword(key, password, paste.attachmentname);
                } catch (err) {
                    throw 'failed to decipher attachment name: ' + err;
                }
                if (attachmentName === false) {
                    return false;
                }
            }

            AttachmentViewer.setAttachment(attachment, attachmentName);
            AttachmentViewer.showAttachment();

            return true;
        }

        /**
         * decrypts all comments and shows them
         *
         * @name   PasteDecrypter.decryptComments
         * @private
         * @function
         * @param  {object} paste - paste data in object form
         * @param  {string} key
         * @param  {string} password
         * @return {bool} whether action was successful
         */
        function decryptComments(paste, key, password)
        {
            // remove potentially previous discussion
            DiscussionViewer.prepareNewDiscussion();

            // iterate over comments
            for (var i = 0; i < paste.comments.length; ++i) {
                var comment = paste.comments[i];

                DiscussionViewer.addComment(
                    comment,
                    CryptTool.decipher(key, password, comment.data),
                    comment.meta.nickname ? CryptTool.decipher(key, password, comment.meta.nickname) : ''
                );
            }

            DiscussionViewer.finishDiscussion();
            return true;
        }

        /**
         * show decrypted text in the display area, including discussion (if open)
         *
         * @name   PasteDecrypter.run
         * @function
         * @param  {Object} [paste] - (optional) object including comments to display (items = array with keys ('data','meta'))
         */
        me.run = function(paste)
        {
            Alert.hideMessages();
            Alert.showLoading('Decrypting paste…', 'cloud-download');

            if (typeof paste === 'undefined') {
                // get cipher data and wait until it is available
                Model.getPasteData(me.run);
                return;
            }

            var key = Model.getPasteKey(),
                password = Prompt.getPassword();

            // try to decrypt the paste
            try {
                // decrypt attachments
                if (paste.attachment) {
                    if (AttachmentViewer.hasAttachmentData()) {
                        // try to decrypt paste and if it fails (because the password is
                        // missing) return to let JS continue and wait for user
                        if (!decryptAttachment(paste, key, password)) {
                            return;
                        }
                    }
                    // ignore empty paste, as this is allowed when pasting attachments
                    decryptPaste(paste, key, password, true);
                } else {
                    if (decryptPaste(paste, key, password) === false) {
                        return false;
                    }
                }

                // shows the remaining time (until) deletion
                PasteStatus.showRemainingTime(paste.meta);

                // if the discussion is opened on this paste, display it
                if (paste.meta.opendiscussion) {
                    decryptComments(paste, key, password);
                }

                Alert.hideLoading();
                TopNav.showViewButtons();
            } catch(err) {
                Alert.hideLoading();

                // log and show error
                console.error(err);
                Alert.showError('Could not decrypt data. Did you enter a wrong password? Retry with the button at the top.');
                // reset password, so it can be re-entered and sow retry button
                Prompt.reset();
                TopNav.setRetryCallback(function () {
                    TopNav.hideRetryButton();

                    me.run(paste);
                });
                TopNav.showRetryButton();
            }
        };

        /**
         * initialize
         *
         * @name   PasteDecrypter.init
         * @function
         */
        me.init = function()
        {
            // nothing yet
        };

        return me;
    })();

    /**
     * (controller) main PrivateBin logic
     *
     * @name   Controller
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var Controller = (function (window, document) {
        var me = {};

        /**
         * hides all status messages no matter which module showed them
         *
         * @name   Controller.hideStatusMessages
         * @function
         */
        me.hideStatusMessages = function()
        {
            PasteStatus.hideMessages();
            Alert.hideMessages();
        };

        /**
         * creates a new paste
         *
         * @name   Controller.newPaste
         * @function
         */
        me.newPaste = function()
        {
            // Important: This *must not* run Alert.hideMessages() as previous
            // errors from viewing a paste should be shown.
            TopNav.hideAllButtons();
            Alert.showLoading('Preparing new paste…', 'time');

            PasteStatus.hideMessages();
            PasteViewer.hide();
            Editor.resetInput();
            Editor.show();
            Editor.focusInput();
            AttachmentViewer.removeAttachment();

            TopNav.showCreateButtons();
            Alert.hideLoading();
        };

        /**
         * shows how we much we love bots that execute JS ;)
         *
         * @name   Controller.showBadBotMessage
         * @function
         */
        me.showBadBotMessage = function()
        {
            TopNav.hideAllButtons();
            Alert.showError('I love you too, bot…');
        }

        /**
         * shows the loaded paste
         *
         * @name   Controller.showPaste
         * @function
         */
        me.showPaste = function()
        {
            try {
                Model.getPasteKey();
            } catch (err) {
                console.error(err);

                // missing decryption key (or paste ID) in URL?
                if (window.location.hash.length === 0) {
                    Alert.showError('Cannot decrypt paste: Decryption key missing in URL (Did you use a redirector or an URL shortener which strips part of the URL?)');
                    return;
                }
            }

            // show proper elements on screen
            PasteDecrypter.run();
        };

        /**
         * refreshes the loaded paste to show potential new data
         *
         * @name   Controller.refreshPaste
         * @function
         * @param  {function} callback
         */
        me.refreshPaste = function(callback)
        {
            // save window position to restore it later
            var orgPosition = $(window).scrollTop();

            Model.getPasteData(function (data) {
                Uploader.prepare();
                Uploader.setUrl(Helper.baseUri() + '?' + Model.getPasteId());

                Uploader.setFailure(function (status, data) {
                    // revert loading status…
                    Alert.hideLoading();
                    TopNav.showViewButtons();

                    // show error message
                    Alert.showError(
                        Uploader.parseUploadError(status, data, 'refresh display')
                    );
                });
                Uploader.setSuccess(function (status, data) {
                    PasteDecrypter.run(data);

                    // restore position
                    window.scrollTo(0, orgPosition);

                    PasteDecrypter.run(data);

                    // NOTE: could create problems as callback may be called
                    // asyncronously if PasteDecrypter e.g. needs to wait for a
                    // password being entered
                    callback();
                });
                Uploader.run();
            }, false); // this false is important as it circumvents the cache
        }

        /**
         * clone the current paste
         *
         * @name   Controller.clonePaste
         * @function
         */
        me.clonePaste = function()
        {
            TopNav.collapseBar();
            TopNav.hideAllButtons();

            // hide messages from previous paste
            me.hideStatusMessages();

            // erase the id and the key in url
            history.pushState({type: 'clone'}, document.title, Helper.baseUri());

            if (AttachmentViewer.hasAttachment()) {
                AttachmentViewer.moveAttachmentTo(
                    TopNav.getCustomAttachment(),
                    'Cloned: \'%s\''
                );
                TopNav.hideFileSelector();
                AttachmentViewer.hideAttachment();
                // NOTE: it also looks nice without removing the attachment
                // but for a consistent display we remove it…
                AttachmentViewer.hideAttachmentPreview();
                TopNav.showCustomAttachment();

                // show another status message to make the user aware that the
                // file was cloned too!
                Alert.showStatus(
                    [
                        'The cloned file \'%s\' was attached to this paste.',
                        AttachmentViewer.getAttachment()[1]
                    ],
                    'copy'
                );
            }

            Editor.setText(PasteViewer.getText());
            PasteViewer.hide();
            Editor.show();

            TopNav.showCreateButtons();
        };

        /**
         * removes a saved paste
         *
         * @name   Controller.removePaste
         * @function
         * @param  {string} pasteId
         * @param  {string} deleteToken
         * @deprecated not used anymore, de we still need it?
         */
        me.removePaste = function(pasteId, deleteToken) {
            // unfortunately many web servers don't support DELETE (and PUT) out of the box
            // so we use a POST request
            Uploader.prepare();
            Uploader.setUrl(Helper.baseUri() + '?' + pasteId);
            Uploader.setUnencryptedData('deletetoken', deleteToken);

            Uploader.setFailure(function () {
                Alert.showError(
                    I18n._('Could not delete the paste, it was not stored in burn after reading mode.')
                );
            });
            Uploader.run();
        };

        /**
         * application start
         *
         * @name   Controller.init
         * @function
         */
        me.init = function()
        {
            // first load translations
            I18n.loadTranslations();

            DOMPurify.setConfig({SAFE_FOR_JQUERY: true});

            // initialize other modules/"classes"
            Alert.init();
            Model.init();
            AttachmentViewer.init();
            DiscussionViewer.init();
            Editor.init();
            PasteDecrypter.init();
            PasteEncrypter.init();
            PasteStatus.init();
            PasteViewer.init();
            Prompt.init();
            TopNav.init();
            UiHelper.init();
            Uploader.init();

            // check whether existing paste needs to be shown
            try {
                Model.getPasteId();
            } catch (e) {
                // otherwise create a new paste
                return me.newPaste();
            }

            // if delete token is passed (i.e. paste has been deleted by this access)
            // there is no more stuf we need to do
            if (Model.hasDeleteToken()) {
                return;
            }

            // prevent bots from viewing a paste and potentially deleting data
            // when burn-after-reading is set
            // see https://github.com/elrido/ZeroBin/issues/11
            if (Helper.isBadBot()) {
                return me.showBadBotMessage();
            }

            // display an existing paste
            return me.showPaste();
        }

        return me;
    })(window, document);

    return {
        Helper: Helper,
        I18n: I18n,
        CryptTool: CryptTool,
        Model: Model,
        UiHelper: UiHelper,
        Alert: Alert,
        PasteStatus: PasteStatus,
        Prompt: Prompt,
        Editor: Editor,
        PasteViewer: PasteViewer,
        AttachmentViewer: AttachmentViewer,
        DiscussionViewer: DiscussionViewer,
        TopNav: TopNav,
        Uploader: Uploader,
        PasteEncrypter: PasteEncrypter,
        PasteDecrypter: PasteDecrypter,
        Controller: Controller
    };
})(jQuery, sjcl, Base64, RawDeflate);
