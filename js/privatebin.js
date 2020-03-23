/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @see       {@link https://github.com/PrivateBin/PrivateBin}
 * @copyright 2012 Sébastien SAUVAGE ({@link http://sebsauvage.net})
 * @license   {@link https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License}
 * @version   1.3.4
 * @name      PrivateBin
 * @namespace
 */

// global Base64, DOMPurify, FileReader, RawDeflate, history, navigator, prettyPrint, prettyPrintOne, showdown, kjua

jQuery.fn.draghover = function() {
    'use strict';
    return this.each(function() {
        let collection = $(),
            self = $(this);
  
        self.on('dragenter', function(e) {
            if (collection.length === 0) {
                self.trigger('draghoverstart');
            }
            collection = collection.add(e.target);
        });
  
        self.on('dragleave drop', function(e) {
            collection = collection.not(e.target);
            if (collection.length === 0) {
                self.trigger('draghoverend');
            }
        });
    });
};

// main application start, called when DOM is fully loaded
jQuery(document).ready(function() {
    'use strict';
    // run main controller
    $.PrivateBin.Controller.init();
});

jQuery.PrivateBin = (function($, RawDeflate) {
    'use strict';

    /**
     * zlib library interface
     *
     * @private
     */
    let z;

    /**
     * CryptoData class
     *
     * bundles helper fuctions used in both paste and comment formats
     *
     * @name CryptoData
     * @class
     */
    function CryptoData(data) {
        this.v = 1;
        // store all keys in the default locations for drop-in replacement
        for (let key in data) {
            this[key] = data[key];
        }

        /**
         * gets the cipher data (cipher text + adata)
         *
         * @name Paste.getCipherData
         * @function
         * @return {Array}|{string}
         */
        this.getCipherData = function()
        {
            return this.v === 1 ? this.data : [this.ct, this.adata];
        }
    }

    /**
     * Paste class
     *
     * bundles helper fuctions around the paste formats
     *
     * @name Paste
     * @class
     */
    function Paste(data) {
        // inherit constructor and methods of CryptoData
        CryptoData.call(this, data);

        /**
         * gets the used formatter
         *
         * @name Paste.getFormat
         * @function
         * @return {string}
         */
        this.getFormat = function()
        {
            return this.v === 1 ? this.meta.formatter : this.adata[1];
        }

        /**
         * gets the remaining seconds before the paste expires
         *
         * returns 0 if there is no expiration
         *
         * @name Paste.getTimeToLive
         * @function
         * @return {string}
         */
        this.getTimeToLive = function()
        {
            return (this.v === 1 ? this.meta.remaining_time : this.meta.time_to_live) || 0;
        }

        /**
         * is burn-after-reading enabled
         *
         * @name Paste.isBurnAfterReadingEnabled
         * @function
         * @return {bool}
         */
        this.isBurnAfterReadingEnabled = function()
        {
            return (this.v === 1 ? this.meta.burnafterreading : this.adata[3]);
        }

        /**
         * are discussions enabled
         *
         * @name Paste.isDiscussionEnabled
         * @function
         * @return {bool}
         */
        this.isDiscussionEnabled = function()
        {
            return (this.v === 1 ? this.meta.opendiscussion : this.adata[2]);
        }
    }

    /**
     * Comment class
     *
     * bundles helper fuctions around the comment formats
     *
     * @name Comment
     * @class
     */
    function Comment(data) {
        // inherit constructor and methods of CryptoData
        CryptoData.call(this, data);

        /**
         * gets the UNIX timestamp of the comment creation
         *
         * @name Paste.getCreated
         * @function
         * @return {int}
         */
        this.getCreated = function()
        {
            return this.meta[this.v === 1 ? 'postdate' : 'created'];
        }

        /**
         * gets the icon of the comment submitter
         *
         * @name Paste.getIcon
         * @function
         * @return {string}
         */
        this.getIcon = function()
        {
            return this.meta[this.v === 1 ? 'vizhash' : 'icon'] || '';
        }
    }

    /**
     * static Helper methods
     *
     * @name Helper
     * @class
     */
    const Helper = (function () {
        const me = {};

        /**
         * character to HTML entity lookup table
         *
         * @see    {@link https://github.com/janl/mustache.js/blob/master/mustache.js#L60}
         * @name Helper.entityMap
         * @private
         * @enum   {Object}
         * @readonly
         */
        const entityMap = {
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
         * number of seconds in a minute
         *
         * @name Helper.minute
         * @private
         * @enum   {number}
         * @readonly
         */
        const minute = 60;

        /**
         * number of seconds in an hour
         *
         * = 60 * 60 seconds
         *
         * @name Helper.minute
         * @private
         * @enum   {number}
         * @readonly
         */
        const hour = 3600;

        /**
         * number of seconds in a day
         *
         * = 60 * 60 * 24 seconds
         *
         * @name Helper.day
         * @private
         * @enum   {number}
         * @readonly
         */
        const day = 86400;

        /**
         * number of seconds in a month (30 days, an approximation)
         *
         * = 60 * 60 * 24 * 30 seconds
         *
         * @name Helper.month
         * @private
         * @enum   {number}
         * @readonly
         */
        const month = 2592000;

        /**
         * number of seconds in a non-leap year
         *
         * = 60 * 60 * 24 * 365 seconds
         *
         * @name Helper.year
         * @private
         * @enum   {number}
         * @readonly
         */
        const year = 31536000;

        /**
         * cache for script location
         *
         * @name Helper.baseUri
         * @private
         * @enum   {string|null}
         */
        let baseUri = null;

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
            let v;
            if (seconds < minute)
            {
                v = Math.floor(seconds);
                return [v, 'second'];
            }
            if (seconds < hour)
            {
                v = Math.floor(seconds / minute);
                return [v, 'minute'];
            }
            if (seconds < day)
            {
                v = Math.floor(seconds / hour);
                return [v, 'hour'];
            }
            // If less than 2 months, display in days:
            if (seconds < (2 * month))
            {
                v = Math.floor(seconds / day);
                return [v, 'day'];
            }
            v = Math.floor(seconds / month);
            return [v, 'month'];
        };

        /**
         * converts a duration string into seconds
         *
         * The string is expected to be optional digits, followed by a time.
         * Supported times are: min, hour, day, month, year, never
         * Examples: 5min, 13hour, never
         *
         * @name Helper.durationToSeconds
         * @function
         * @param  {String} duration
         * @return {number}
         */
        me.durationToSeconds = function(duration)
        {
            let pieces   = duration.split(/\d+/),
                factor   = pieces[0] || 0,
                timespan = pieces[1] || pieces[0];
            switch (timespan)
            {
                case 'min':
                    return factor * minute;
                case 'hour':
                    return factor * hour;
                case 'day':
                    return factor * day;
                case 'month':
                    return factor * month;
                case 'year':
                    return factor * year;
                case 'never':
                    return 0;
                default:
                    return factor;
            }
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
            let range, selection;

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
         * convert URLs to clickable links in the provided element.
         *
         * URLs to handle:
         * <pre>
         *     magnet:?xt.1=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C&xt.2=urn:sha1:TXGCZQTH26NL6OUQAJJPFALHG2LTGBC7
         *     https://example.com:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
         *     http://user:example.com@localhost:8800/zero/?6f09182b8ea51997#WtLEUO5Epj9UHAV9JFs+6pUQZp13TuspAUjnF+iM+dM=
         * </pre>
         *
         * @name   Helper.urls2links
         * @function
         * @param  {HTMLElement} element
         */
        me.urls2links = function(element)
        {
            element.html(
                element.html().replace(
                    /(((https?|ftp):\/\/[\w?!=&.\/-;#@~%+*-]+(?![\w\s?!&.\/;#~%"=-]>))|((magnet):[\w?=&.\/-;#@~%+*-]+))/ig,
                    '<a href="$1" rel="nofollow">$1</a>'
                )
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
            const args = Array.prototype.slice.call(arguments);
            let format = args[0],
                i = 1;
            return format.replace(/%(s|d)/g, function (m) {
                let val = args[i];
                if (m === '%d') {
                    val = parseFloat(val);
                    if (isNaN(val)) {
                        val = 0;
                    }
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
            const name = cname + '=',
                  ca   = document.cookie.split(';');
            for (let i = 0; i < ca.length; ++i) {
                let c = ca[i];
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

            baseUri = window.location.origin + window.location.pathname;
            return baseUri;
        };

        /**
         * wrap an object into a Paste, used for mocking in the unit tests
         *
         * @name   Helper.PasteFactory
         * @function
         * @param  {object} data
         * @return {Paste}
         */
        me.PasteFactory = function(data)
        {
            return new Paste(data);
        };

        /**
         * wrap an object into a Comment, used for mocking in the unit tests
         *
         * @name   Helper.CommentFactory
         * @function
         * @param  {object} data
         * @return {Comment}
         */
        me.CommentFactory = function(data)
        {
            return new Comment(data);
        };

        /**
         * convert all applicable characters to HTML entities
         *
         * @see    {@link https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html}
         * @name   Helper.htmlEntities
         * @function
         * @param  {string} str
         * @return {string} escaped HTML
         */
        me.htmlEntities = function(str) {
            return String(str).replace(
                /[&<>"'`=\/]/g, function(s) {
                    return entityMap[s];
                }
            );
        }

        /**
         * calculate expiration date given initial date and expiration period
         * 
         * @name   Helper.calculateExpirationDate
         * @function
         * @param  {Date} initialDate - may not be empty
         * @param  {string|number} expirationDisplayStringOrSecondsToExpire - may not be empty
         * @return {Date}
         */
        me.calculateExpirationDate = function(initialDate, expirationDisplayStringOrSecondsToExpire) {
            let expirationDate      = new Date(initialDate),
                secondsToExpiration = expirationDisplayStringOrSecondsToExpire;
            if (typeof expirationDisplayStringOrSecondsToExpire === 'string') {
                secondsToExpiration = me.durationToSeconds(expirationDisplayStringOrSecondsToExpire);
            }
            
            if (typeof secondsToExpiration !== 'number') {
                throw new Error('Cannot calculate expiration date.');
            }
            if (secondsToExpiration === 0) {
                return null;
            }

            expirationDate = expirationDate.setUTCSeconds(expirationDate.getUTCSeconds() + secondsToExpiration);
            return expirationDate;
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

        return me;
    })();

    /**
     * internationalization module
     *
     * @name I18n
     * @class
     */
    const I18n = (function () {
        const me = {};

        /**
         * const for string of loaded language
         *
         * @name I18n.languageLoadedEvent
         * @private
         * @prop   {string}
         * @readonly
         */
        const languageLoadedEvent = 'languageLoaded';

        /**
         * supported languages, minus the built in 'en'
         *
         * @name I18n.supportedLanguages
         * @private
         * @prop   {string[]}
         * @readonly
         */
        const supportedLanguages = ['bg', 'cs', 'de', 'es', 'fr', 'it', 'hu', 'no', 'nl', 'pl', 'pt', 'oc', 'ru', 'sl', 'uk', 'zh'];

        /**
         * built in language
         *
         * @name I18n.language
         * @private
         * @prop   {string|null}
         */
        let language = null;

        /**
         * translation cache
         *
         * @name I18n.translations
         * @private
         * @enum   {Object}
         */
        let translations = {};

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
         * loaded) language is not downloaded yet, this will make sure the string
         * is replaced when it eventually gets loaded. Using this is both simpler
         * and more secure, as it avoids potential XSS when inserting text.
         * The next parameter is the message ID, matching the ones found in
         * the translation files under the i18n directory.
         * Any additional parameters will get inserted into the message ID in
         * place of %s (strings) or %d (digits), applying the appropriate plural
         * in case of digits. See also Helper.sprintf().
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
            let args = Array.prototype.slice.call(arguments),
                messageId,
                $element = null;

            // parse arguments
            if (args[0] instanceof jQuery) {
                // optional jQuery element as first parameter
                $element = args[0];
                args.shift();
            }

            // extract messageId from arguments
            let usesPlurals = $.isArray(args[0]);
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
                    let orgArguments = arguments;
                    $(document).on(languageLoadedEvent, function () {
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
                let n = parseInt(args[1] || 1, 10),
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

            // messageID may contain links, but should be from a trusted source (code or translation JSON files)
            let containsLinks = args[0].indexOf('<a') !== -1;

            // prevent double encoding, when we insert into a text node
            if (containsLinks || $element === null) {
                for (let i = 0; i < args.length; ++i) {
                    // parameters (i > 0) may never contain HTML as they may come from untrusted parties
                    if ((containsLinks ? i > 1 : i > 0) || !containsLinks) {
                        args[i] = Helper.htmlEntities(args[i]);
                    }
                }
            }
            // format string
            let output = Helper.sprintf.apply(this, args);

            if (containsLinks) {
                // only allow tags/attributes we actually use in translations
                output = DOMPurify.sanitize(
                    output, {
                        ALLOWED_TAGS: ['a', 'i', 'span'],
                        ALLOWED_ATTR: ['href', 'id']
                    }
                );
            }

            // if $element is given, insert translation
            if ($element !== null) {
                if (containsLinks) {
                    $element.html(output);
                } else {
                    // text node takes care of entity encoding
                    $element.text(output);
                }
                return '';
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
                case 'cs':
                    return n === 1 ? 0 : (n >= 2 && n <=4 ? 1 : 2);
                case 'fr':
                case 'oc':
                case 'zh':
                    return n > 1 ? 1 : 0;
                case 'pl':
                    return n === 1 ? 0 : (n % 10 >= 2 && n %10 <=4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2);
                case 'ru':
                case 'uk':
                    return n % 10 === 1 && n % 100 !== 11 ? 0 : (n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2);
                case 'sl':
                    return n % 100 === 1 ? 1 : (n % 100 === 2 ? 2 : (n % 100 === 3 || n % 100 === 4 ? 3 : 0));
                // bg, de, en, es, hu, it, nl, no, pt
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
            let newLanguage = Helper.getCookie('lang');

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
    const CryptTool = (function () {
        const me = {};

        /**
         * base58 encoder & decoder
         *
         * @private
         */
        let base58 = new baseX('123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz');

        /**
         * convert UTF-8 string stored in a DOMString to a standard UTF-16 DOMString
         *
         * Iterates over the bytes of the message, converting them all hexadecimal
         * percent encoded representations, then URI decodes them all
         *
         * @name   CryptTool.utf8To16
         * @function
         * @private
         * @param  {string} message UTF-8 string
         * @return {string} UTF-16 string
         */
        function utf8To16(message)
        {
            return decodeURIComponent(
                message.split('').map(
                    function(character)
                    {
                        return '%' + ('00' + character.charCodeAt(0).toString(16)).slice(-2);
                    }
                ).join('')
            );
        }

        /**
         * convert DOMString (UTF-16) to a UTF-8 string stored in a DOMString
         *
         * URI encodes the message, then finds the percent encoded characters
         * and transforms these hexadecimal representation back into bytes
         *
         * @name   CryptTool.utf16To8
         * @function
         * @private
         * @param  {string} message UTF-16 string
         * @return {string} UTF-8 string
         */
        function utf16To8(message)
        {
            return encodeURIComponent(message).replace(
                /%([0-9A-F]{2})/g,
                function (match, hexCharacter)
                {
                    return String.fromCharCode('0x' + hexCharacter);
                }
            );
        }

        /**
         * convert ArrayBuffer into a UTF-8 string
         *
         * Iterates over the bytes of the array, catenating them into a string
         *
         * @name   CryptTool.arraybufferToString
         * @function
         * @private
         * @param  {ArrayBuffer} messageArray
         * @return {string} message
         */
        function arraybufferToString(messageArray)
        {
            const array = new Uint8Array(messageArray);
            let message = '',
                i       = 0;
            while(i < array.length) {
                message += String.fromCharCode(array[i++]);
            }
            return message;
        }

        /**
         * convert UTF-8 string into a Uint8Array
         *
         * Iterates over the bytes of the message, writing them to the array
         *
         * @name   CryptTool.stringToArraybuffer
         * @function
         * @private
         * @param  {string} message UTF-8 string
         * @return {Uint8Array} array
         */
        function stringToArraybuffer(message)
        {
            const messageArray = new Uint8Array(message.length);
            for (let i = 0; i < message.length; ++i) {
                messageArray[i] = message.charCodeAt(i);
            }
            return messageArray;
        }

        /**
         * compress a string (deflate compression), returns buffer
         *
         * @name   CryptTool.compress
         * @async
         * @function
         * @private
         * @param  {string} message
         * @param  {string} mode
         * @param  {object} zlib
         * @throws {string}
         * @return {ArrayBuffer} data
         */
        async function compress(message, mode, zlib)
        {
            message = stringToArraybuffer(
                utf16To8(message)
            );
            if (mode === 'zlib') {
                if (typeof zlib === 'undefined') {
                    throw 'Error compressing paste, due to missing WebAssembly support.'
                }
                return zlib.deflate(message).buffer;
            }
            return message;
        }

        /**
         * decompress potentially base64 encoded, deflate compressed buffer, returns string
         *
         * @name   CryptTool.decompress
         * @async
         * @function
         * @private
         * @param  {ArrayBuffer} data
         * @param  {string} mode
         * @param  {object} zlib
         * @throws {string}
         * @return {string} message
         */
        async function decompress(data, mode, zlib)
        {
            if (mode === 'zlib' || mode === 'none') {
                if (mode === 'zlib') {
                    if (typeof zlib === 'undefined') {
                        throw 'Error decompressing paste, due to missing WebAssembly support.'
                    }
                    data = zlib.inflate(
                        new Uint8Array(data)
                    ).buffer;
                }
                return utf8To16(
                    arraybufferToString(data)
                );
            }
            // detect presence of Base64.js, indicating legacy ZeroBin paste
            if (typeof Base64 === 'undefined') {
                return utf8To16(
                    RawDeflate.inflate(
                        utf8To16(
                            atob(
                                arraybufferToString(data)
                            )
                        )
                    )
                );
            } else {
                return Base64.btou(
                    RawDeflate.inflate(
                        Base64.fromBase64(
                            arraybufferToString(data)
                        )
                    )
                );
            }
        }

        /**
         * returns specified number of random bytes
         *
         * @name   CryptTool.getRandomBytes
         * @function
         * @private
         * @param  {int} length number of random bytes to fetch
         * @throws {string}
         * @return {string} random bytes
         */
        function getRandomBytes(length)
        {
            let bytes       = '';
            const byteArray = new Uint8Array(length);
            window.crypto.getRandomValues(byteArray);
            for (let i = 0; i < length; ++i) {
                bytes += String.fromCharCode(byteArray[i]);
            }
            return bytes;
        }

        /**
         * derive cryptographic key from key string and password
         *
         * @name   CryptTool.deriveKey
         * @async
         * @function
         * @private
         * @param  {string} key
         * @param  {string} password
         * @param  {array}  spec cryptographic specification
         * @return {CryptoKey} derived key
         */
        async function deriveKey(key, password, spec)
        {
            let keyArray = stringToArraybuffer(key);
            if (password.length > 0) {
                // version 1 pastes did append the passwords SHA-256 hash in hex
                if (spec[7] === 'rawdeflate') {
                    let passwordBuffer = await window.crypto.subtle.digest(
                        {name: 'SHA-256'},
                        stringToArraybuffer(
                            utf16To8(password)
                        )
                    ).catch(Alert.showError);
                    password = Array.prototype.map.call(
                        new Uint8Array(passwordBuffer),
                        x => ('00' + x.toString(16)).slice(-2)
                    ).join('');
                }
                let passwordArray = stringToArraybuffer(password),
                    newKeyArray = new Uint8Array(keyArray.length + passwordArray.length);
                newKeyArray.set(keyArray, 0);
                newKeyArray.set(passwordArray, keyArray.length);
                keyArray = newKeyArray;
            }

            // import raw key
            const importedKey = await window.crypto.subtle.importKey(
                'raw', // only 'raw' is allowed
                keyArray,
                {name: 'PBKDF2'}, // we use PBKDF2 for key derivation
                false, // the key may not be exported
                ['deriveKey'] // we may only use it for key derivation
            ).catch(Alert.showError);

            // derive a stronger key for use with AES
            return window.crypto.subtle.deriveKey(
                {
                    name: 'PBKDF2', // we use PBKDF2 for key derivation
                    salt: stringToArraybuffer(spec[1]), // salt used in HMAC
                    iterations: spec[2], // amount of iterations to apply
                    hash: {name: 'SHA-256'} // can be "SHA-1", "SHA-256", "SHA-384" or "SHA-512"
                },
                importedKey,
                {
                    name: 'AES-' + spec[6].toUpperCase(), // can be any supported AES algorithm ("AES-CTR", "AES-CBC", "AES-CMAC", "AES-GCM", "AES-CFB", "AES-KW", "ECDH", "DH" or "HMAC")
                    length: spec[3] // can be 128, 192 or 256
                },
                false, // the key may not be exported
                ['encrypt', 'decrypt'] // we may only use it for en- and decryption
            ).catch(Alert.showError);
        }

        /**
         * gets crypto settings from specification and authenticated data
         *
         * @name   CryptTool.cryptoSettings
         * @function
         * @private
         * @param  {string} adata authenticated data
         * @param  {array}  spec cryptographic specification
         * @return {object} crypto settings
         */
        function cryptoSettings(adata, spec)
        {
            return {
                name: 'AES-' + spec[6].toUpperCase(), // can be any supported AES algorithm ("AES-CTR", "AES-CBC", "AES-CMAC", "AES-GCM", "AES-CFB", "AES-KW", "ECDH", "DH" or "HMAC")
                iv: stringToArraybuffer(spec[0]), // the initialization vector you used to encrypt
                additionalData: stringToArraybuffer(adata), // the addtional data you used during encryption (if any)
                tagLength: spec[4] // the length of the tag you used to encrypt (if any)
            };
        }

        /**
         * compress, then encrypt message with given key and password
         *
         * @name   CryptTool.cipher
         * @async
         * @function
         * @param  {string} key
         * @param  {string} password
         * @param  {string} message
         * @param  {array}  adata
         * @return {array}  encrypted message in base64 encoding & adata containing encryption spec
         */
        me.cipher = async function(key, password, message, adata)
        {
            let zlib = (await z);
            // AES in Galois Counter Mode, keysize 256 bit,
            // authentication tag 128 bit, 10000 iterations in key derivation
            const compression = (
                    typeof zlib === 'undefined' ?
                    'none' : // client lacks support for WASM
                    ($('body').data('compression') || 'zlib')
                ),
                spec = [
                    getRandomBytes(16), // initialization vector
                    getRandomBytes(8),  // salt
                    100000,             // iterations
                    256,                // key size
                    128,                // tag size
                    'aes',              // algorithm
                    'gcm',              // algorithm mode
                    compression         // compression
                ], encodedSpec = [];
            for (let i = 0; i < spec.length; ++i) {
                encodedSpec[i] = i < 2 ? btoa(spec[i]) : spec[i];
            }
            if (adata.length === 0) {
                // comment
                adata = encodedSpec;
            } else if (adata[0] === null) {
                // paste
                adata[0] = encodedSpec;
            }

            // finally, encrypt message
            return [
                btoa(
                    arraybufferToString(
                        await window.crypto.subtle.encrypt(
                            cryptoSettings(JSON.stringify(adata), spec),
                            await deriveKey(key, password, spec),
                            await compress(message, compression, zlib)
                        ).catch(Alert.showError)
                    )
                ),
                adata
            ];
        };

        /**
         * decrypt message with key, then decompress
         *
         * @name   CryptTool.decipher
         * @async
         * @function
         * @param  {string} key
         * @param  {string} password
         * @param  {string|object} data encrypted message
         * @return {string} decrypted message, empty if decryption failed
         */
        me.decipher = async function(key, password, data)
        {
            let adataString, spec, cipherMessage, plaintext;
            let zlib = (await z);
            if (data instanceof Array) {
                // version 2
                adataString = JSON.stringify(data[1]);
                // clone the array instead of passing the reference
                spec = (data[1][0] instanceof Array ? data[1][0] : data[1]).slice();
                cipherMessage = data[0];
            } else if (typeof data === 'string') {
                // version 1
                let object = JSON.parse(data);
                adataString = atob(object.adata);
                spec = [
                    object.iv,
                    object.salt,
                    object.iter,
                    object.ks,
                    object.ts,
                    object.cipher,
                    object.mode,
                    'rawdeflate'
                ];
                cipherMessage = object.ct;
            } else {
                throw 'unsupported message format';
            }
            spec[0] = atob(spec[0]);
            spec[1] = atob(spec[1]);
            if (spec[7] === 'zlib') {
                if (typeof zlib === 'undefined') {
                    throw 'Error decompressing paste, due to missing WebAssembly support.'
                }
            }
            try {
                plaintext = await window.crypto.subtle.decrypt(
                    cryptoSettings(adataString, spec),
                    await deriveKey(key, password, spec),
                    stringToArraybuffer(
                        atob(cipherMessage)
                    )
                );
            } catch(err) {
                console.error(err);
                return '';
            }
            try {
                return await decompress(plaintext, spec[7], zlib);
            } catch(err) {
                Alert.showError(err);
                return err;
            }
        };

        /**
         * returns a random symmetric key
         *
         * generates 256 bit long keys (8 Bits * 32) for AES with 256 bit long blocks
         *
         * @name   CryptTool.getSymmetricKey
         * @function
         * @throws {string}
         * @return {string} raw bytes
         */
        me.getSymmetricKey = function()
        {
            return getRandomBytes(32);
        };

        /**
         * base58 encode a DOMString (UTF-16)
         *
         * @name   CryptTool.base58encode
         * @function
         * @param  {string} input
         * @return {string} output
         */
        me.base58encode = function(input)
        {
            return base58.encode(
                stringToArraybuffer(input)
            );
        }

        /**
         * base58 decode a DOMString (UTF-16)
         *
         * @name   CryptTool.base58decode
         * @function
         * @param  {string} input
         * @return {string} output
         */
        me.base58decode = function(input)
        {
            return arraybufferToString(
                base58.decode(input)
            );
        }

        return me;
    })();

    /**
     * (Model) Data source (aka MVC)
     *
     * @name   Model
     * @class
     */
    const Model = (function () {
        const me = {};

        let id = null,
            pasteData = null,
            symmetricKey = null,
            $templates;

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
            ServerInteraction.prepare();
            ServerInteraction.setUrl(Helper.baseUri() + '?pasteid=' + me.getPasteId());

            ServerInteraction.setFailure(function (status, data) {
                // revert loading status…
                Alert.hideLoading();
                TopNav.showViewButtons();

                // show error message
                Alert.showError(ServerInteraction.parseUploadError(status, data, 'get paste data'));
            });
            ServerInteraction.setSuccess(function (status, data) {
                pasteData = new Paste(data);

                if (typeof callback === 'function') {
                    return callback(pasteData);
                }
            });
            ServerInteraction.run();
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
            const idRegEx = /^[a-z0-9]{16}$/;

            // return cached value
            if (id !== null) {
                return id;
            }

            // do use URL interface, if possible
            const url = new URL(window.location);

            for (const param of url.searchParams) {
                const key = param[0];
                const value = param[1];

                if (value === '' && idRegEx.test(key)) {
                    // safe, as the whole regex is matched
                    id = key;
                    return key;
                }
            }

            if (id === null) {
                throw 'no paste id given';
            }

            return id;
        }

        /**
         * returns true, when the URL has a delete token and the current call was used for deleting a paste.
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
                let newKey = window.location.hash.substring(1);
                if (newKey === '') {
                    throw 'no encryption key given';
                }

                // Some web 2.0 services and redirectors add data AFTER the anchor
                // (such as &utm_source=...). We will strip any additional data.
                let ampersandPos = newKey.indexOf('&');
                if (ampersandPos > -1)
                {
                    newKey = newKey.substring(0, ampersandPos);
                }

                // version 2 uses base58, version 1 uses base64 without decoding
                try {
                    // base58 encode strips NULL bytes at the beginning of the
                    // string, so we re-add them if necessary
                    symmetricKey = CryptTool.base58decode(newKey).padStart(32, '\u0000');
                } catch(e) {
                    symmetricKey = newKey;
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
            let $element = $templates.find('#' + name + 'template').clone(true);
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
    const UiHelper = (function () {
        const me = {};

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
            let currentLocation = Helper.baseUri();
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
            let elementTop     = $element.offset().top,
                viewportTop    = $(window).scrollTop(),
                viewportBottom = viewportTop + $(window).height();
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
            let $body = $('html, body'),
                margin = 50,
                callbackCalled = false,
                dest = 0;

            // calculate destination place
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
    const Alert = (function () {
        const me = {};

        let $errorMessage,
            $loadingIndicator,
            $statusMessage,
            $remainingTime,
            currentIcon,
            customHandler;

        const alertType = [
            'loading', // not in bootstrap CSS, but using a plausible value here
            'info',    // status icon
            'warning', // warning icon
            'danger'   // error icon
        ];

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
            } else if (args  instanceof Error) {
                // extract message into array if needed
                args = [args.message];
            }

            // pass to custom handler if defined
            if (typeof customHandler === 'function') {
                let handlerResult = customHandler(alertType[id], $element, args, icon);
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
            let $translationTarget = $element;

            // handle icon, if template uses one
            const $glyphIcon = $element.find(':first');
            if ($glyphIcon.length) {
                // if there is an icon, we need to provide an inner element
                // to translate the message into, instead of the parent
                $translationTarget = $('<span>');
                $element.html(' ').prepend($glyphIcon).append($translationTarget);

                if (icon !== null && // icon was passed
                    icon !== currentIcon[id] // and it differs from current icon
                ) {
                    // remove (previous) icon
                    $glyphIcon.removeClass(currentIcon[id]);

                    // any other thing as a string (e.g. 'null') (only) removes the icon
                    if (typeof icon === 'string') {
                        // set new icon
                        currentIcon[id] = 'glyphicon-' + icon;
                        $glyphIcon.addClass(currentIcon[id]);
                    }
                }
            }

            // show text
            if (args !== null) {
                // add jQuery object to it as first parameter
                args.unshift($translationTarget);
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
            handleNotification(1, $statusMessage, message, icon);
        };

        /**
         * display a warning message
         *
         * This automatically passes the text to I18n for translation.
         *
         * @name   Alert.showWarning
         * @function
         * @param  {string|array} message     string, use an array for %s/%d options
         * @param  {string|null}  icon        optional, the icon to show, default:
         *                                    leave previous icon
         */
        me.showWarning = function(message, icon)
        {
            $errorMessage.find(':first')
                         .removeClass(currentIcon[3])
                         .addClass(currentIcon[2]);
            handleNotification(2, $errorMessage, message, icon);
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
                'glyphicon-warning-sign', // warning icon
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
    const PasteStatus = (function () {
        const me = {};

        let $pasteSuccess,
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
            if ($shortenButton.hasClass('buttondisabled')) {
                return;
            }
            $.ajax({
                type: 'GET',
                url: `${$shortenButton.data('shortener')}${encodeURIComponent($pasteUrl.attr('href'))}`,
                headers: {'Accept': 'text/html, application/xhtml+xml, application/xml, application/json'},
                processData: false,
                timeout: 10000,
                xhrFields: {
                    withCredentials: false
                },
                success: function(response) {
                    let responseString = response;
                    if (typeof responseString === 'object') {
                        responseString = JSON.stringify(responseString);
                    }
                    if (typeof responseString === 'string' && responseString.length > 0) {
                        const shortUrlMatcher = /https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)/g;
                        const shortUrl = (responseString.match(shortUrlMatcher) || []).sort(function(a, b) {
                            return a.length - b.length;
                        })[0];
                        if (typeof shortUrl === 'string' && shortUrl.length > 0) {
                            I18n._(
                                $('#pastelink'),
                                'Your paste is <a id="pasteurl" href="%s">%s</a> <span id="copyhint">(Hit [Ctrl]+[c] to copy)</span>',
                                shortUrl, shortUrl
                            );
                            // we disable the button to avoid calling shortener again
                            $shortenButton.addClass('buttondisabled');
                            // save newly created element
                            $pasteUrl = $('#pasteurl');
                            // we pre-select the link so that the user only has to [Ctrl]+[c] the link
                            Helper.selectText($pasteUrl[0]);
                            return;
                        }
                    }
                    Alert.showError('Cannot parse response from URL shortener.');
                }
            })
            .fail(function(data, textStatus, errorThrown) {
                console.error(textStatus, errorThrown);
                // we don't know why it failed, could be CORS of the external
                // server not setup properly, in which case we follow old
                // behavior to open it in new tab
                window.open(
                    `${$shortenButton.data('shortener')}${encodeURIComponent($pasteUrl.attr('href'))}`,
                    '_blank',
                    'noopener, noreferrer'
                );
            });
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
            I18n._(
                $('#pastelink'),
                'Your paste is <a id="pasteurl" href="%s">%s</a> <span id="copyhint">(Hit [Ctrl]+[c] to copy)</span>',
                url, url
            );
            // save newly created element
            $pasteUrl = $('#pasteurl');
            // and add click event
            $pasteUrl.click(pasteLinkClick);

            // delete link
            $('#deletelink').html('<a href="' + deleteUrl + '"></a>');
            I18n._($('#deletelink a').first(), 'Delete data');

            // enable shortener button
            $shortenButton.removeClass('buttondisabled');

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
         * @param {Paste} paste
         */
        me.showRemainingTime = function(paste)
        {
            if (paste.isBurnAfterReadingEnabled()) {
                // display paste "for your eyes only" if it is deleted

                // the paste has been deleted when the JSON with the ciphertext
                // has been downloaded

                Alert.showRemaining('FOR YOUR EYES ONLY. Don\'t close this window, this message can\'t be displayed again.');
                $remainingTime.addClass('foryoureyesonly');
            } else if (paste.getTimeToLive() > 0) {
                // display paste expiration
                let expiration = Helper.secondsToHuman(paste.getTimeToLive()),
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
    const Prompt = (function () {
        const me = {};

        let $passwordDecrypt,
            $passwordForm,
            $passwordModal,
            password = '';

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
    const Editor = (function () {
        const me = {};

        let $editorTabs,
            $messageEdit,
            $messagePreview,
            $message,
            isPreview = false;

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
            const keyCode = event.keyCode || event.which;
            // tab was pressed
            if (keyCode === 9) {
                // get caret position & selection
                const val   = this.value,
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

            $('#messageedit').attr('aria-selected','true');
            $('#messagepreview').attr('aria-selected','false');

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

            $('#messageedit').attr('aria-selected','false');
            $('#messagepreview').attr('aria-selected','true');

            // hide input as now preview is shown
            $message.addClass('hidden');

            // show preview
            PasteViewer.setText($message.val());
            if (AttachmentViewer.hasAttachmentData()) {
                const attachment = AttachmentViewer.getAttachment();
                AttachmentViewer.handleBlobAttachmentPreview(
                    AttachmentViewer.getAttachmentPreview(),
                    attachment[0], attachment[1]
                );
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
    const PasteViewer = (function () {
        const me = {};

        let $placeholder,
            $prettyMessage,
            $prettyPrint,
            $plainText,
            text,
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

            if (format === 'markdown') {
                const converter = new showdown.Converter({
                    strikethrough: true,
                    tables: true,
                    tablesHeaderId: true,
                    simplifiedAutoLink: true,
                    excludeTrailingPunctuationFromURLs: true
                });
                // let showdown convert the HTML and sanitize HTML *afterwards*!
                $plainText.html(
                    DOMPurify.sanitize(
                        converter.makeHtml(text)
                    )
                );
                // add table classes from bootstrap css
                $plainText.find('table').addClass('table-condensed table-bordered');
            } else {
                if (format === 'syntaxhighlighting') {
                    // yes, this is really needed to initialize the environment
                    if (typeof prettyPrint === 'function')
                    {
                        prettyPrint();
                    }

                    $prettyPrint.html(
                        prettyPrintOne(
                            Helper.htmlEntities(text), null, true
                        )
                    );
                } else {
                    // = 'plaintext'
                    $prettyPrint.text(text);
                }
                Helper.urls2links($prettyPrint);
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
                return;
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
    const AttachmentViewer = (function () {
        const me = {};

        let $attachmentLink,
            $attachmentPreview,
            $attachment,
            attachmentData,
            file,
            $fileInput,
            $dragAndDropFileName,
            attachmentHasPreview = false,
            $dropzone;

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
            // data URI format: data:[<mediaType>][;base64],<data>

            // position in data URI string of where data begins
            const base64Start = attachmentData.indexOf(',') + 1;
            // position in data URI string of where mediaType ends
            const mediaTypeEnd = attachmentData.indexOf(';');

            // extract mediaType
            const mediaType = attachmentData.substring(5, mediaTypeEnd);
            // extract data and convert to binary
            const decodedData = atob(attachmentData.substring(base64Start));

            // Transform into a Blob
            const buf = new Uint8Array(decodedData.length);
            for (let i = 0; i < decodedData.length; ++i) {
                buf[i] = decodedData.charCodeAt(i);
            }
            const blob = new window.Blob([ buf ], { type: mediaType });

            // Get Blob URL
            const blobUrl = window.URL.createObjectURL(blob);

            // IE does not support setting a data URI on an a element
            // Using msSaveBlob to download
            if (window.Blob && navigator.msSaveBlob) {
                $attachmentLink.off('click').on('click', function () {
                    navigator.msSaveBlob(blob, fileName);
                });
            } else {
                $attachmentLink.attr('href', blobUrl);
            }

            if (typeof fileName !== 'undefined') {
                $attachmentLink.attr('download', fileName);
            }

            me.handleBlobAttachmentPreview($attachmentPreview, blobUrl, mediaType);
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
            $dragAndDropFileName.text('');

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
            const link = $attachmentLink.prop('href');
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

            // update text - ensuring no HTML is inserted into the text node
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
                Alert.showWarning('Your browser does not support uploading encrypted files. Please use a newer browser.');
                return;
            }

            const fileReader = new FileReader();
            if (loadedFile === undefined) {
                loadedFile = $fileInput[0].files[0];
                $dragAndDropFileName.text('');
            } else {
                $dragAndDropFileName.text(loadedFile.name);
            }

            if (typeof loadedFile !== 'undefined') {
                file = loadedFile;
                fileReader.onload = function (event) {
                    const dataURL = event.target.result;
                    attachmentData = dataURL;

                    if (Editor.isPreview()) {
                        me.handleAttachmentPreview($attachmentPreview, dataURL);
                        $attachmentPreview.removeClass('hidden');
                    }

                    TopNav.highlightFileupload();
                };
                fileReader.readAsDataURL(loadedFile);
            } else {
                me.removeAttachmentData();
            }
        }

        /**
         * handle the preview of files decoded to blob that can either be an image, video, audio or pdf element
         *
         * @name   AttachmentViewer.handleBlobAttachmentPreview
         * @function
         * @argument {jQuery} $targetElement element where the preview should be appended
         * @argument {string} file as a blob URL
         * @argument {string} mime type
         */
        me.handleBlobAttachmentPreview = function ($targetElement, blobUrl, mimeType) {
            if (blobUrl) {
                attachmentHasPreview = true;
                if (mimeType.match(/image\//i)) {
                    $targetElement.html(
                        $(document.createElement('img'))
                            .attr('src', blobUrl)
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
                            .attr('src', blobUrl))
                    );
                } else if (mimeType.match(/audio\//i)) {
                    $targetElement.html(
                        $(document.createElement('audio'))
                            .attr('controls', 'true')
                            .attr('autoplay', 'true')

                            .append($(document.createElement('source'))
                            .attr('type', mimeType)
                            .attr('src', blobUrl))
                    );
                } else if (mimeType.match(/\/pdf/i)) {
                    // Fallback for browsers, that don't support the vh unit
                    const clientHeight = $(window).height();

                    $targetElement.html(
                        $(document.createElement('embed'))
                            .attr('src', blobUrl)
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

            const handleDragEnterOrOver = function(event) {
                event.stopPropagation();
                event.preventDefault();
                return false;
            };

            const handleDrop = function(event) {
                const evt = event.originalEvent;
                evt.stopPropagation();
                evt.preventDefault();

                if (TopNav.isAttachmentReadonly()) {
                    return false;
                }

                if ($fileInput) {
                    const file = evt.dataTransfer.files[0];
                    //Clear the file input:
                    $fileInput.wrap('<form>').closest('form').get(0).reset();
                    $fileInput.unwrap();
                    //Only works in Chrome:
                    //fileInput[0].files = e.dataTransfer.files;

                    readFileData(file);
                }
            };

            $(document).draghover().on({
                'draghoverstart': function(e) {
                    if (TopNav.isAttachmentReadonly()) {
                        e.stopPropagation();
                        e.preventDefault();
                        return false;
                    }
                    // show dropzone to indicate drop support
                    $dropzone.removeClass('hidden');
                },
                'draghoverend': function() {
                    $dropzone.addClass('hidden');
                }
            });

            $(document).on('drop', handleDrop);
            $(document).on('dragenter dragover', handleDragEnterOrOver);

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
                if (TopNav.isAttachmentReadonly()) {
                    event.stopPropagation();
                    event.preventDefault();
                    return false;
                }
                const items = (event.clipboardData || event.originalEvent.clipboardData).items;
                for (let i = 0; i < items.length; ++i) {
                    if (items[i].kind === 'file') {
                        //Clear the file input:
                        $fileInput.wrap('<form>').closest('form').get(0).reset();
                        $fileInput.unwrap();

                        readFileData(items[i].getAsFile());
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
            $dragAndDropFileName = $('#dragAndDropFileName');
            $dropzone = $('#dropzone');
            $attachmentLink = $('#attachment a') || $('<a>');
            if($attachment.length) {
                $attachmentPreview = $('#attachmentPreview');

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
    const DiscussionViewer = (function () {
        const me = {};

        let $commentTail,
            $discussion,
            $reply,
            $replyMessage,
            $replyNickname,
            $replyStatus,
            $commentContainer,
            replyCommentId;

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
            const $source = $(event.target);

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
         * @param {Comment} comment
         * @param {string} commentText
         * @param {string} nickname
         */
        me.addComment = function(comment, commentText, nickname)
        {
            if (commentText === '') {
                commentText = 'comment decryption failed';
            }

            // create new comment based on template
            const $commentEntry = Model.getTemplate('comment');
            $commentEntry.prop('id', 'comment_' + comment.id);
            const $commentEntryData = $commentEntry.find('div.commentdata');

            // set & parse text
            $commentEntryData.text(commentText);
            Helper.urls2links($commentEntryData);

            // set nickname
            if (nickname.length > 0) {
                $commentEntry.find('span.nickname').text(nickname);
            } else {
                $commentEntry.find('span.nickname').html('<i></i>');
                I18n._($commentEntry.find('span.nickname i'), 'Anonymous');
            }

            // set date
            $commentEntry.find('span.commentdate')
                      .text(' (' + (new Date(comment.getCreated() * 1000).toLocaleString()) + ')')
                      .attr('title', 'CommentID: ' + comment.id);

            // if an avatar is available, display it
            const icon = comment.getIcon();
            if (icon) {
                $commentEntry.find('span.nickname')
                             .before(
                                '<img src="' + icon + '" class="vizhash" /> '
                             );
                $(document).on('languageLoaded', function () {
                    $commentEntry.find('img.vizhash')
                                 .prop('title', I18n._('Avatar generated from IP address'));
                });
            }

            // starting point (default value/fallback)
            let $place = $commentContainer;

            // if parent comment exists
            const $parentComment = $('#comment_' + comment.parentid);
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
            const $comment = $('#comment_' + commentId);
            // in case comment does not exist, cancel
            if ($comment.length === 0) {
                return;
            }

            $comment.addClass('highlight');
            const highlightComment = function () {
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
    const TopNav = (function (window, document) {
        const me = {};

        let createButtonsDisplayed = false,
            viewButtonsDisplayed = false,
            $attach,
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
            $emailLink,
            $sendButton,
            $retryButton,
            pasteExpiration = null,
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
            const target = $(event.target);

            // update dropdown display and save new expiration time
            $('#pasteExpirationDisplay').text(target.text());
            pasteExpiration = target.data('expiration');

            event.preventDefault();
        }

        /**
         * set the format on bootstrap templates in dropdown from user interaction
         *
         * @name   TopNav.updateFormat
         * @private
         * @function
         * @param  {Event} event
         */
        function updateFormat(event)
        {
            // get selected option
            const $target = $(event.target);

            // update dropdown display and save new format
            const newFormat = $target.data('format');
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
            let paste = PasteViewer.getText();

            // push a new state to allow back navigation with browser back button
            history.pushState(
                {type: 'raw'},
                document.title,
                // recreate paste URL
                Helper.baseUri() + '?' + Model.getPasteId() + '#' +
                CryptTool.base58encode(Model.getPasteKey())
            );

            // we use text/html instead of text/plain to avoid a bug when
            // reloading the raw text view (it reverts to type text/html)
            const $head  = $('head').children().not('noscript, script, link[type="text/css"]'),
                  newDoc = document.open('text/html', 'replace');
            newDoc.write('<!DOCTYPE html><html><head>');
            for (let i = 0; i < $head.length; ++i) {
                newDoc.write($head[i].outerHTML);
            }
            newDoc.write('</head><body><pre>' + DOMPurify.sanitize(Helper.htmlEntities(paste)) + '</pre></body></html>');
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
            const qrCanvas = kjua({
                render: 'canvas',
                text: window.location.href
            });
            $('#qrcode-display').html(qrCanvas);
        }

        /**
         * Template Email body.
         * 
         * @name   TopNav.templateEmailBody
         * @private 
         * @param {string} expirationDateString 
         * @param {bool} isBurnafterreading 
         */
        function templateEmailBody(expirationDateString, isBurnafterreading)
        {
            const EOL = '\n';
            const BULLET = '  - ';
            let emailBody = '';
            if (expirationDateString !== null || isBurnafterreading) {
                emailBody += I18n._('Notice:');
                emailBody += EOL;

                if (expirationDateString !== null) {
                    emailBody += EOL;
                    emailBody += BULLET;
                    emailBody += I18n._(
                        'This link will expire after %s.',
                        expirationDateString
                    );
                }
                if (isBurnafterreading) {
                    emailBody += EOL;
                    emailBody += BULLET;
                    emailBody += I18n._(
                        'This link can only be accessed once, do not use back or refresh button in your browser.'
                    );
                }

                emailBody += EOL;
                emailBody += EOL;
            }
            emailBody += I18n._('Link:');
            emailBody += EOL;
            emailBody += `${window.location.href}`;
            return emailBody;
        }

        /**
         * Trigger Email send.
         * 
         * @name   TopNav.triggerEmailSend
         * @private 
         * @param {string} emailBody 
         */
        function triggerEmailSend(emailBody)
        {
            window.open(
                `mailto:?body=${encodeURIComponent(emailBody)}`,
                '_self',
                'noopener, noreferrer'
            );
        }

        /**
         * Send Email with current paste (URL).
         *
         * @name   TopNav.sendEmail
         * @private
         * @function
         * @param  {Date|null} expirationDate date of expiration
         * @param  {bool} isBurnafterreading whether it is burn after reading
         */
        function sendEmail(expirationDate, isBurnafterreading)
        {
            const expirationDateRoundedToSecond = new Date(expirationDate);

            // round down at least 30 seconds to make up for the delay of request
            expirationDateRoundedToSecond.setUTCSeconds(
                expirationDateRoundedToSecond.getUTCSeconds() - 30
            );
            expirationDateRoundedToSecond.setUTCSeconds(0);

            const $emailconfirmmodal = $('#emailconfirmmodal');
            if ($emailconfirmmodal.length > 0) {
                if (expirationDate !== null) {
                    I18n._(
                        $emailconfirmmodal.find('#emailconfirm-display'),
                        'Recipient may become aware of your timezone, convert time to UTC?'
                    );
                    const $emailconfirmTimezoneCurrent = $emailconfirmmodal.find('#emailconfirm-timezone-current');
                    const $emailconfirmTimezoneUtc = $emailconfirmmodal.find('#emailconfirm-timezone-utc');
                    $emailconfirmTimezoneCurrent.off('click.sendEmailCurrentTimezone');
                    $emailconfirmTimezoneCurrent.on('click.sendEmailCurrentTimezone', () => {
                        const emailBody = templateEmailBody(expirationDateRoundedToSecond.toLocaleString(), isBurnafterreading);
                        $emailconfirmmodal.modal('hide');
                        triggerEmailSend(emailBody);
                    });
                    $emailconfirmTimezoneUtc.off('click.sendEmailUtcTimezone');
                    $emailconfirmTimezoneUtc.on('click.sendEmailUtcTimezone', () => {
                        const emailBody = templateEmailBody(expirationDateRoundedToSecond.toLocaleString(
                            undefined,
                            // we don't use Date.prototype.toUTCString() because we would like to avoid GMT
                            { timeZone: 'UTC', dateStyle: 'long', timeStyle: 'long' }
                        ), isBurnafterreading);
                        $emailconfirmmodal.modal('hide');
                        triggerEmailSend(emailBody);
                    });
                    $emailconfirmmodal.modal('show');
                } else {
                    triggerEmailSend(templateEmailBody(null, isBurnafterreading));
                }
            } else {
                let emailBody = '';
                if (expirationDate !== null) {
                    const expirationDateString = window.confirm(
                        I18n._('Recipient may become aware of your timezone, convert time to UTC?')
                    ) ? expirationDateRoundedToSecond.toLocaleString(
                        undefined,
                        // we don't use Date.prototype.toUTCString() because we would like to avoid GMT
                        { timeZone: 'UTC', dateStyle: 'long', timeStyle: 'long' }
                    ) : expirationDateRoundedToSecond.toLocaleString();
                    emailBody = templateEmailBody(expirationDateString, isBurnafterreading);
                } else {
                    emailBody = templateEmailBody(null, isBurnafterreading);
                }
                triggerEmailSend(emailBody);
            }
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
                return;
            }

            $cloneButton.addClass('hidden');
            $newButton.addClass('hidden');
            $rawTextButton.addClass('hidden');
            $qrCodeLink.addClass('hidden');
            me.hideEmailButton();

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
         * show the "email" button
         * 
         * @name   TopNav.showEmailbutton
         * @function
         * @param {int|undefined} optionalRemainingTimeInSeconds
         */
        me.showEmailButton = function(optionalRemainingTimeInSeconds)
        {
            try {
                // we cache expiration date in closure to avoid inaccurate expiration datetime
                const expirationDate = Helper.calculateExpirationDate(
                    new Date(),
                    typeof optionalRemainingTimeInSeconds === 'number' ? optionalRemainingTimeInSeconds : TopNav.getExpiration()
                );
                const isBurnafterreading = TopNav.getBurnAfterReading();

                $emailLink.removeClass('hidden');
                $emailLink.off('click.sendEmail');
                $emailLink.on('click.sendEmail', () => {
                    sendEmail(expirationDate, isBurnafterreading);
                });
            } catch (error) {
                console.error(error);
                Alert.showError('Cannot calculate expiration date.');
            }
        }

        /**
         * hide the "email" button
         * 
         * @name   TopNav.hideEmailButton
         * @function
         */
        me.hideEmailButton = function()
        {
            $emailLink.addClass('hidden');
            $emailLink.off('click.sendEmail');
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
         * only hides the qr code button
         * 
         * @name   TopNav.hideQrCodeButton
         * @function
         */
        me.hideQrCodeButton = function()
        {
            $qrCodeLink.addClass('hidden');
        }

        /**
         * hide all irrelevant buttons when viewing burn after reading paste
         * 
         * @name   TopNav.hideBurnAfterReadingButtons
         * @function
         */
        me.hideBurnAfterReadingButtons = function()
        {
            me.hideCloneButton();
            me.hideQrCodeButton();
            me.hideEmailButton();
        }

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
         * hides the custom attachment
         * 
         * @name  TopNav.hideCustomAttachment
         * @function
         */
        me.hideCustomAttachment = function()
        {
            $customAttachment.addClass('hidden');
            $fileWrap.removeClass('hidden');
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
            const $file = $('#file');

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
         * @name   TopNav.getBurnAfterReading
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
            // when password is disabled $passwordInput.val() will return undefined
            return $passwordInput.val() || '';
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
         * Highlight file upload
         * 
         * @name  TopNav.highlightFileupload
         * @function
         */
        me.highlightFileupload = function()
        {
            // visually indicate file uploaded
            const $attachDropdownToggle = $attach.children('.dropdown-toggle');
            if ($attachDropdownToggle.attr('aria-expanded') === 'false') {
                $attachDropdownToggle.click();
            }
            $fileWrap.addClass('highlight');
            setTimeout(function () {
                $fileWrap.removeClass('highlight');
            }, 300);
        }

        /**
         * set the format on bootstrap templates in dropdown programmatically
         * 
         * @name    TopNav.setFormat
         * @function
         */
        me.setFormat = function(format)
        {
            $formatter.parent().find(`a[data-format="${format}"]`).click();
        }

        /**
         * returns if attachment dropdown is readonly, not editable
         * 
         * @name   TopNav.isAttachmentReadonly
         * @function
         * @return {bool}
         */
        me.isAttachmentReadonly = function()
        {
            return createButtonsDisplayed && $attach.hasClass('hidden');
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
            $emailLink = $('#emaillink');

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
     * @name   ServerInteraction
     * @class
     */
    const ServerInteraction = (function () {
        const me = {};

        let successFunc = null,
            failureFunc = null,
            symmetricKey = null,
            url,
            data,
            password;

        /**
         * public variable ('constant') for errors to prevent magic numbers
         *
         * @name   ServerInteraction.error
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
         * @name   ServerInteraction.ajaxHeaders
         * @private
         * @readonly
         * @enum   {Object}
         */
        const ajaxHeaders = {'X-Requested-With': 'JSONHttpRequest'};

        /**
         * called after successful upload
         *
         * @name   ServerInteraction.success
         * @private
         * @function
         * @param {int} status
         * @param {int} result - optional
         */
        function success(status, result)
        {
            if (successFunc !== null) {
                // add useful data to result
                result.encryptionKey = symmetricKey;
                successFunc(status, result);
            }
        }

        /**
         * called after a upload failure
         *
         * @name   ServerInteraction.fail
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
         * @name   ServerInteraction.run
         * @function
         */
        me.run = function()
        {
            let isPost = Object.keys(data).length > 0,
                ajaxParams = {
                    type: isPost ? 'POST' : 'GET',
                    url: url,
                    headers: ajaxHeaders,
                    dataType: 'json',
                    success: function(result) {
                        if (result.status === 0) {
                            success(0, result);
                        } else if (result.status === 1) {
                            fail(1, result);
                        } else {
                            fail(2, result);
                        }
                    }
                };
            if (isPost) {
                ajaxParams.data = JSON.stringify(data);
            }
            $.ajax(ajaxParams).fail(function(jqXHR, textStatus, errorThrown) {
                console.error(textStatus, errorThrown);
                fail(3, jqXHR);
            });
        };

        /**
         * return currently set data, used in unit testing
         *
         * @name   ServerInteraction.getData
         * @function
         */
        me.getData = function()
        {
            return data;
        };

        /**
         * set success function
         *
         * @name   ServerInteraction.setUrl
         * @function
         * @param {function} newUrl
         */
        me.setUrl = function(newUrl)
        {
            url = newUrl;
        };

        /**
         * sets the password to use (first value) and optionally also the
         * encryption key (not recommended, it is automatically generated).
         *
         * Note: Call this after prepare() as prepare() resets these values.
         *
         * @name   ServerInteraction.setCryptValues
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
         * @name   ServerInteraction.setSuccess
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
         * @name   ServerInteraction.setFailure
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
         * @name   ServerInteraction.prepare
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
         * @name   ServerInteraction.setCipherMessage
         * @async
         * @function
         * @param {object} cipherMessage
         */
        me.setCipherMessage = async function(cipherMessage)
        {
            if (
                symmetricKey === null ||
                (typeof symmetricKey === 'string' && symmetricKey === '')
            ) {
                symmetricKey = CryptTool.getSymmetricKey();
            }
            if (!data.hasOwnProperty('adata')) {
                data['adata'] = [];
            }
            let cipherResult = await CryptTool.cipher(symmetricKey, password, JSON.stringify(cipherMessage), data['adata']);
            data['v'] = 2;
            data['ct'] = cipherResult[0];
            data['adata'] = cipherResult[1];

        };

        /**
         * set the additional metadata to send unencrypted
         *
         * @name   ServerInteraction.setUnencryptedData
         * @function
         * @param {string} index
         * @param {mixed} element
         */
        me.setUnencryptedData = function(index, element)
        {
            data[index] = element;
        };

        /**
         * Helper, which parses shows a general error message based on the result of the ServerInteraction
         *
         * @name    ServerInteraction.parseUploadError
         * @function
         * @param {int} status
         * @param {object} data
         * @param {string} doThisThing - a human description of the action, which was tried
         * @return {array}
         */
        me.parseUploadError = function(status, data, doThisThing) {
            let errorArray;

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

        return me;
    })();

    /**
     * (controller) Responsible for encrypting paste and sending it to server.
     *
     * Does upload, encryption is done transparently by ServerInteraction.
     *
     * @name PasteEncrypter
     * @class
     */
    const PasteEncrypter = (function () {
        const me = {};

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
            Alert.hideMessages();

            // show notification
            const baseUri   = Helper.baseUri() + '?',
                  url       = baseUri + data.id + '#' + CryptTool.base58encode(data.encryptionKey),
                  deleteUrl = baseUri + 'pasteid=' + data.id + '&deletetoken=' + data.deletetoken;
            PasteStatus.createPasteNotification(url, deleteUrl);

            // show new URL in browser bar
            history.pushState({type: 'newpaste'}, document.title, url);

            TopNav.showViewButtons();

            // this cannot be grouped with showViewButtons due to remaining time calculation
            TopNav.showEmailButton();

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
         * send a reply in a discussion
         *
         * @name   PasteEncrypter.sendComment
         * @async
         * @function
         */
        me.sendComment = async function()
        {
            Alert.hideMessages();
            Alert.setCustomHandler(DiscussionViewer.handleNotification);

            // UI loading state
            TopNav.hideAllButtons();
            Alert.showLoading('Sending comment…', 'cloud-upload');

            // get data
            const plainText = DiscussionViewer.getReplyMessage(),
                  nickname  = DiscussionViewer.getReplyNickname(),
                  parentid  = DiscussionViewer.getReplyCommentId();

            // do not send if there is no data
            if (plainText.length === 0) {
                // revert loading status…
                Alert.hideLoading();
                Alert.setCustomHandler(null);
                TopNav.showViewButtons();
                return;
            }

            // prepare server interaction
            ServerInteraction.prepare();
            ServerInteraction.setCryptParameters(Prompt.getPassword(), Model.getPasteKey());

            // set success/fail functions
            ServerInteraction.setSuccess(showUploadedComment);
            ServerInteraction.setFailure(function (status, data) {
                // revert loading status…
                Alert.hideLoading();
                TopNav.showViewButtons();

                // …show error message…
                Alert.showError(
                    ServerInteraction.parseUploadError(status, data, 'post comment')
                );

                // …and reset error handler
                Alert.setCustomHandler(null);
            });

            // fill it with unencrypted params
            ServerInteraction.setUnencryptedData('pasteid', Model.getPasteId());
            if (typeof parentid === 'undefined') {
                // if parent id is not set, this is the top-most comment, so use
                // paste id as parent, as the root element of the discussion tree
                ServerInteraction.setUnencryptedData('parentid', Model.getPasteId());
            } else {
                ServerInteraction.setUnencryptedData('parentid', parentid);
            }

            // prepare cypher message
            let cipherMessage = {
                'comment': plainText
            };
            if (nickname.length > 0) {
                cipherMessage['nickname'] = nickname;
            }

            await ServerInteraction.setCipherMessage(cipherMessage).catch(Alert.showError);
            ServerInteraction.run();
        };

        /**
         * sends a new paste to server
         *
         * @name   PasteEncrypter.sendPaste
         * @async
         * @function
         */
        me.sendPaste = async function()
        {
            // hide previous (error) messages
            Controller.hideStatusMessages();

            // UI loading state
            TopNav.hideAllButtons();
            Alert.showLoading('Sending paste…', 'cloud-upload');
            TopNav.collapseBar();

            // get data
            const plainText = Editor.getText(),
                  format    = PasteViewer.getFormat(),
                  // the methods may return different values if no files are attached (null, undefined or false)
                  files     = TopNav.getFileList() || AttachmentViewer.getFile() || AttachmentViewer.hasAttachment();

            // do not send if there is no data
            if (plainText.length === 0 && !files) {
                // revert loading status…
                Alert.hideLoading();
                TopNav.showCreateButtons();
                return;
            }

            // prepare server interaction
            ServerInteraction.prepare();
            ServerInteraction.setCryptParameters(TopNav.getPassword());

            // set success/fail functions
            ServerInteraction.setSuccess(showCreatedPaste);
            ServerInteraction.setFailure(function (status, data) {
                // revert loading status…
                Alert.hideLoading();
                TopNav.showCreateButtons();

                // show error message
                Alert.showError(
                    ServerInteraction.parseUploadError(status, data, 'create paste')
                );
            });

            // fill it with unencrypted submitted options
            ServerInteraction.setUnencryptedData('adata', [
                null, format,
                TopNav.getOpenDiscussion() ? 1 : 0,
                TopNav.getBurnAfterReading() ? 1 : 0
            ]);
            ServerInteraction.setUnencryptedData('meta', {'expire': TopNav.getExpiration()});

            // prepare PasteViewer for later preview
            PasteViewer.setText(plainText);
            PasteViewer.setFormat(format);

            // prepare cypher message
            let file = AttachmentViewer.getAttachmentData(),
                cipherMessage = {
                    'paste': plainText
                };
            if (typeof file !== 'undefined' && file !== null) {
                cipherMessage['attachment'] = file;
                cipherMessage['attachment_name'] = AttachmentViewer.getFile().name;
            } else if (AttachmentViewer.hasAttachment()) {
                // fall back to cloned part
                let attachment = AttachmentViewer.getAttachment();
                cipherMessage['attachment'] = attachment[0];
                cipherMessage['attachment_name'] = attachment[1];

                // we need to retrieve data from blob if browser already parsed it in memory
                if (typeof attachment[0] === 'string' && attachment[0].startsWith('blob:')) {
                    Alert.showStatus(
                        [
                            'Retrieving cloned file \'%s\' from memory...',
                            attachment[1]
                        ],
                        'copy'
                    );
                    try {
                        const blobData = await $.ajax({
                            type: 'GET',
                            url: `${attachment[0]}`,
                            processData: false,
                            timeout: 10000,
                            xhrFields: {
                                withCredentials: false,
                                responseType: 'blob'
                            }
                        });
                        if (blobData instanceof window.Blob) {
                            const fileReading = new Promise(function(resolve, reject) {
                                const fileReader = new FileReader();
                                fileReader.onload = function (event) {
                                    resolve(event.target.result);
                                };
                                fileReader.onerror = function (error) {
                                    reject(error);
                                }
                                fileReader.readAsDataURL(blobData);
                            });
                            cipherMessage['attachment'] = await fileReading;
                        } else {
                            const error = 'Cannot process attachment data.';
                            Alert.showError(error);
                            throw new TypeError(error);
                        }
                    } catch (error) {
                        console.error(error);
                        Alert.showError('Cannot retrieve attachment.');
                        throw error;
                    }
                }
            }

            // encrypt message
            await ServerInteraction.setCipherMessage(cipherMessage).catch(Alert.showError);

            // send data
            ServerInteraction.run();
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
    const PasteDecrypter = (function () {
        const me = {};

        /**
         * decrypt data or prompts for password in case of failure
         *
         * @name   PasteDecrypter.decryptOrPromptPassword
         * @private
         * @async
         * @function
         * @param  {string} key
         * @param  {string} password - optional, may be an empty string
         * @param  {string} cipherdata
         * @throws {string}
         * @return {false|string} false, when unsuccessful or string (decrypted data)
         */
        async function decryptOrPromptPassword(key, password, cipherdata)
        {
            // try decryption without password
            const plaindata = await CryptTool.decipher(key, password, cipherdata);

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
                return false;
            }

            return plaindata;
        }

        /**
         * decrypt the actual paste text
         *
         * @name   PasteDecrypter.decryptPaste
         * @private
         * @async
         * @function
         * @param  {Paste} paste - paste data in object form
         * @param  {string} key
         * @param  {string} password
         * @throws {string}
         * @return {Promise}
         */
        async function decryptPaste(paste, key, password)
        {
            let pastePlain = await decryptOrPromptPassword(
                key, password,
                paste.getCipherData()
            );
            if (pastePlain === false) {
                if (password.length === 0) {
                    throw 'waiting on user to provide a password';
                } else {
                    Alert.hideLoading();
                    // reset password, so it can be re-entered
                    Prompt.reset();
                    TopNav.showRetryButton();
                    throw 'Could not decrypt data. Did you enter a wrong password? Retry with the button at the top.';
                }
            }

            if (paste.v > 1) {
                // version 2 paste
                const pasteMessage = JSON.parse(pastePlain);
                if (pasteMessage.hasOwnProperty('attachment') && pasteMessage.hasOwnProperty('attachment_name')) {
                    AttachmentViewer.setAttachment(pasteMessage.attachment, pasteMessage.attachment_name);
                    AttachmentViewer.showAttachment();
                }
                pastePlain = pasteMessage.paste;
            } else {
                // version 1 paste
                if (paste.hasOwnProperty('attachment') && paste.hasOwnProperty('attachmentname')) {
                    Promise.all([
                        CryptTool.decipher(key, password, paste.attachment),
                        CryptTool.decipher(key, password, paste.attachmentname)
                    ]).then((attachment) => {
                        AttachmentViewer.setAttachment(attachment[0], attachment[1]);
                        AttachmentViewer.showAttachment();
                    });
                }
            }
            PasteViewer.setFormat(paste.getFormat());
            PasteViewer.setText(pastePlain);
            PasteViewer.run();
        }

        /**
         * decrypts all comments and shows them
         *
         * @name   PasteDecrypter.decryptComments
         * @private
         * @async
         * @function
         * @param  {Paste} paste - paste data in object form
         * @param  {string} key
         * @param  {string} password
         * @return {Promise}
         */
        async function decryptComments(paste, key, password)
        {
            // remove potential previous discussion
            DiscussionViewer.prepareNewDiscussion();

            const commentDecryptionPromises = [];
            // iterate over comments
            for (let i = 0; i < paste.comments.length; ++i) {
                const comment        = new Comment(paste.comments[i]),
                      commentPromise = CryptTool.decipher(key, password, comment.getCipherData());
                paste.comments[i] = comment;
                if (comment.v > 1) {
                    // version 2 comment
                    commentDecryptionPromises.push(
                        commentPromise.then(function (commentJson) {
                            const commentMessage = JSON.parse(commentJson);
                            return [
                                commentMessage.comment  || '',
                                commentMessage.nickname || ''
                            ];
                        })
                    );
                } else {
                    // version 1 comment
                    commentDecryptionPromises.push(
                        Promise.all([
                            commentPromise,
                            paste.comments[i].meta.hasOwnProperty('nickname') ?
                                CryptTool.decipher(key, password, paste.comments[i].meta.nickname) :
                                Promise.resolve('')
                        ])
                    );
                }
            }
            return Promise.all(commentDecryptionPromises).then(function (plaintexts) {
                for (let i = 0; i < paste.comments.length; ++i) {
                    if (plaintexts[i][0].length === 0) {
                        continue;
                    }
                    DiscussionViewer.addComment(
                        paste.comments[i],
                        plaintexts[i][0],
                        plaintexts[i][1]
                    );
                }
            });
        }

        /**
         * show decrypted text in the display area, including discussion (if open)
         *
         * @name   PasteDecrypter.run
         * @function
         * @param  {Paste} [paste] - (optional) object including comments to display (items = array with keys ('data','meta'))
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

            let key = Model.getPasteKey(),
                password = Prompt.getPassword(),
                decryptionPromises = [];

            TopNav.setRetryCallback(function () {
                TopNav.hideRetryButton();
                me.run(paste);
            });

            // decrypt paste & attachments
            decryptionPromises.push(decryptPaste(paste, key, password));

            // if the discussion is opened on this paste, display it
            if (paste.isDiscussionEnabled()) {
                decryptionPromises.push(decryptComments(paste, key, password));
            }

            // shows the remaining time (until) deletion
            PasteStatus.showRemainingTime(paste);

            Promise.all(decryptionPromises)
                .then(() => {
                    Alert.hideLoading();
                    TopNav.showViewButtons();

                    // discourage cloning (it cannot really be prevented)
                    if (paste.isBurnAfterReadingEnabled()) {
                        TopNav.hideBurnAfterReadingButtons();
                    } else {
                        // we have to pass in remaining_time here
                        TopNav.showEmailButton(paste.getTimeToLive());
                    }

                    // only offer adding comments, after paste was successfully decrypted
                    if (paste.isDiscussionEnabled()) {
                        DiscussionViewer.finishDiscussion();
                    }

                })
                .catch((err) => {
                    // wait for the user to type in the password,
                    // then PasteDecrypter.run will be called again
                    Alert.showError(err);
                });
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
    const Controller = (function (window, document) {
        const me = {};

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

            // newPaste could be called when user is on paste clone editing view
            TopNav.hideCustomAttachment();
            AttachmentViewer.clearDragAndDrop();
            AttachmentViewer.removeAttachmentData();

            Alert.hideLoading();
            history.pushState({type: 'create'}, document.title, Helper.baseUri());

            // clear discussion
            DiscussionViewer.prepareNewDiscussion();
        };

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
            const orgPosition = $(window).scrollTop();

            Model.getPasteData(function (data) {
                ServerInteraction.prepare();
                ServerInteraction.setUrl(Helper.baseUri() + '?pasteid=' + Model.getPasteId());

                ServerInteraction.setFailure(function (status, data) {
                    // revert loading status…
                    Alert.hideLoading();
                    TopNav.showViewButtons();

                    // show error message
                    Alert.showError(
                        ServerInteraction.parseUploadError(status, data, 'refresh display')
                    );
                });
                ServerInteraction.setSuccess(function (status, data) {
                    PasteDecrypter.run(new Paste(data));

                    // restore position
                    window.scrollTo(0, orgPosition);

                    // NOTE: could create problems as callback may be called
                    // asyncronously if PasteDecrypter e.g. needs to wait for a
                    // password being entered
                    callback();
                });
                ServerInteraction.run();
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
            // also clone the format
            TopNav.setFormat(PasteViewer.getFormat());
            PasteViewer.hide();
            Editor.show();

            TopNav.showCreateButtons();

            // clear discussion
            DiscussionViewer.prepareNewDiscussion();
        };

        /**
         * try initializing zlib or display a warning if it fails,
         * extracted from main init to allow unit testing
         *
         * @name   Controller.initZ
         * @function
         */
        me.initZ = function()
        {
            z = zlib.catch(function () {
                if ($('body').data('compression') !== 'none') {
                    Alert.showWarning('Your browser doesn\'t support WebAssembly, used for zlib compression. You can create uncompressed documents, but can\'t read compressed ones.');
                }
            });
        }

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

            DOMPurify.setConfig({
                ALLOWED_URI_REGEXP: /^(?:(?:(?:f|ht)tps?|mailto|magnet):)/i,
                SAFE_FOR_JQUERY: true
            });

            // center all modals
            $('.modal').on('show.bs.modal', function(e) {
                $(e.target).css({
                    display: 'flex'
                });
            });

            // initialize other modules/"classes"
            Alert.init();
            Model.init();
            AttachmentViewer.init();
            DiscussionViewer.init();
            Editor.init();
            PasteStatus.init();
            PasteViewer.init();
            Prompt.init();
            TopNav.init();
            UiHelper.init();

            // check for legacy browsers before going any further
            if (!Legacy.Check.getInit()) {
                // Legacy check didn't complete, wait and try again
                setTimeout(init, 500);
                return;
            }
            if (!Legacy.Check.getStatus()) {
                // something major is wrong, stop right away
                return;
            }
            me.initZ();

            // check whether existing paste needs to be shown
            try {
                Model.getPasteId();
            } catch (e) {
                // otherwise create a new paste
                return me.newPaste();
            }

            // if delete token is passed (i.e. paste has been deleted by this
            // access), there is nothing more to do
            if (Model.hasDeleteToken()) {
                return;
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
        ServerInteraction: ServerInteraction,
        PasteEncrypter: PasteEncrypter,
        PasteDecrypter: PasteDecrypter,
        Controller: Controller
    };
})(jQuery, RawDeflate);
