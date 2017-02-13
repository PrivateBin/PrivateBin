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
            var options = {
                mode: 'gcm',
                ks: 256,
                ts: 128
            };

            if ((password || '').trim().length === 0)
            {
                return sjcl.encrypt(key, compress(message), options);
            }
            return sjcl.encrypt(key + sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash(password)), compress(message), options);
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

        /**
         * checks whether the crypt tool is ready.
         *
         * @name   cryptTool.isReady
         * @function
         * @return {bool}
         */
        me.isEntropyReady = function()
        {
            return sjcl.random.isReady();
        };

        /**
         * checks whether the crypt tool is ready.
         *
         * @name   cryptTool.isReady
         * @function
         * @param {function} - the function to add
         */
        me.addEntropySeedListener = function(func)
        {
            sjcl.random.addEventListener('seeded', func);
        };

        /**
         * returns a random symmetric key
         *
         * @name   cryptTool.getSymmetricKey
         * @function
         * @return {string}
         */
        me.getSymmetricKey = function(func)
        {
            return sjcl.codec.base64.fromBits(sjcl.random.randomWords(8, 0), 0);
        };

        /**
         * initialize crypt tool
         *
         * @name   cryptTool.init
         * @function
         */
        me.init = function()
        {
            // will fail earlier as sjcl is already passed as a parameter
            // if (typeof sjcl !== 'object') {
            //     alert.showError(
            //         i18n._('The library %s is not available.', 'sjcl') +
            //         i18n._('Messages cannot be decrypted or encrypted.')
            //     );
            // }
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

            // bind events
            $('.reloadlink').click(me.reloadPage);

            $(window).on('popstate', me.historyChange);
        };

        return me;
    })(window, document);

    /**
     * alert/notification manager
     *
     * @param  {object} window
     * @param  {object} document
     * @class
     */
    var alert = (function (window, document) {
        var me = {};

        var $attachment,
            $attachmentLink,
            $errorMessage,
            $clonedFile,
            $fileWrap,
            $status,
            $pasteSuccess,
            $shortenButton,
            $pasteUrl;

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
                $replyalert.find('.spinner').removeClass('hidden')
            }
            $replyalert.text(message);
        };

        /**
         * hides any status messages
         *
         * @name   controller.hideMessages
         * @function
         */
        me.hideMessages = function()
        {
            $status.html(' ');
        };

        /**
         * display an error message
         *
         * @name   alert.showError
         * @function
         * @param  {string} message - text to display
         */
        me.showError = function(message)
        {
            $errorMessage.removeClass('hidden');
            $errorMessage.find(':last').text(message);
        };

        /**
         * display an error message
         *
         * @name   alert.showError
         * @function
         * @param  {string} message - text to display
         */
        me.showReplyError = function(message)
        {
            $replyalert.addClass('alert-danger');
            $replyalert.addClass($errorMessage.attr('class')); // @TODO ????

            $replyalert.text(message);
        };

        /**
         * removes the existing attachment
         *
         * @name   alert.removeAttachment
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
         * checks if there is an attachment
         *
         * @name   alert.hasAttachment
         * @function
         */
        me.hasAttachment = function()
        {
            return typeof $attachmentLink.attr('href') !== 'undefined'
        };

        /**
         * return the attachment
         *
         * @name   alert.getAttachment
         * @function
         * @returns {array}
         */
        me.getAttachment = function()
        {
            return [
                $attachmentLink.attr('href'),
                $attachmentLink.attr('download')
            ];
        };

        /**
         * forward to URL shortener
         *
         * @private
         * @function
         * @param  {Event} event
         */
        function sendToShortener(event)
        {
            window.location.href = $shortenButton.data('shortener')
                                   + encodeURIComponent($pasteUrl.attr('href'));
        }

        /**
         * reload the page
         *
         * This takes the user to the PrivateBin home page.
         *
         * @name   controller.createPasteNotification
         * @function
         * @param  {string} url
         * @param  {string} deleteUrl
         */
        me.createPasteNotification = function(url, deleteUrl)
        {
            $('#pastelink').find(':first').html(
                i18n._(
                    'Your paste is <a id="pasteurl" href="%s">%s</a> <span id="copyhint">(Hit [Ctrl]+[c] to copy)</span>',
                    url, url
                )
            );
            // save newly created element
            $pasteUrl = $('#pasteurl');
            // and add click event
            $pasteUrl.click(pasteLinkClick);

            // shorten button
            $('#deletelink').html('<a href="' + deleteUrl + '">' + i18n._('Delete data') + '</a>');

            // show result
            $pasteSuccess.removeClass('hidden');
            // we pre-select the link so that the user only has to [Ctrl]+[c] the link
            helper.selectText($pasteUrl[0]);
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
        function pasteLinkClick(event)
        {
            // check if location is (already) shown in URL bar
            if (window.location.href === $pasteUrl.attr('href')) {
                // if so we need to load link by reloading the current site
                window.location.reload(true);
            }
        }

        /**
         * init status manager
         *
         * preloads jQuery elements
         *
         * @name   alert.init
         * @function
         */
        me.init = function()
        {
            // hide "no javascript" message
            $('#noscript').hide();

            $shortenButton = $('#shortenbutton');
            $attachment = $('#attachment');
            $attachmentLink = $('#attachment a');
            $clonedFile = $('#clonedfile');
            $errorMessage = $('#errormessage');
            $fileWrap = $('#filewrap');
            $pasteSuccess = $('#pasteSuccess');
            // $pasteUrl is saved in submitPasteUpload() if/after it is
            // actually created
            $status = $('#status');
            // @TODO $replyStatus …

            // bind elements
            $shortenButton.click(sendToShortener);

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

        var $passwordModal,
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
                // old method for page template
                var password = prompt(i18n._('Please enter the password for this paste:'), '');
                if (password === null)
                {
                    // @TODO when does this happen?
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
                // new bootstrap method
                $passwordModal.modal();
            }
        };

        /**
         * decrypt using the password from the modal dialog
         *
         * @name   controller.decryptPasswordModal
         * @function
         */
        me.getPassword = function()
        {
            if ($passwordDecrypt.val().length === 0) {
                me.requestPassword();
            }

            return $passwordDecrypt.val();
            // $passwordInput.val($passwordDecrypt.val());
            // me.displayMessages();
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
            $('#errormessage').find(':last')
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
                $remainingTime.find(':last').text(i18n._(expirationLabel, expiration[0]));
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
                $remainingTime.find(':last').text(i18n._(
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
                alert.showError(
                    i18n._('The library %s is not available.', 'pretty print') +
                    i18n._('This may cause display errors.')
                );
            }
            if (typeof showdown !== 'object') {
                alert.showError(
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
            $burnAfterReading,
            $burnAfterReadingOption,
            $cloneButton,
            $expiration,
            $fileRemoveButton,
            $formatter,
            $newButton,
            $openDiscussionOption,
            $openDiscussion,
            $password,
            $passwordInput,
            $rawTextButton,
            $sendButton,
            $loadingIndicator;

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
         * @param  {string} message optional, default: 'Loading…'
         * @param  {int}    percentage optional, default: null
         */
        me.showLoading = function(message, percentage)
        {
            // default message text
            if (typeof message === 'undefined') {
                message = i18n._('Loading…');
            }

            console.log($loadingIndicator);
            // currently percentage parameter is ignored
            if (message !== null) {
                $loadingIndicator.find(':last').text(message);
            }
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
            $loadingIndicator.addClass('hidden');
        };

        /**
         * collapses the navigation bar if nedded
         *
         * @name   topNav.collapseBar
         * @function
         */
        me.collapseBar = function()
        {
            var $bar = $('.navbar-toggle');

            // check if bar is expanded
            if ($bar.hasClass('collapse in')) {
                // if so, toggle it
                $bar.click();
            }
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
         * returns the currently selected file(s)
         *
         * @name   topNav.getFileList
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
            // @TODO is this really necessary
            if (!($file[0].files && $file[0].files[0])) {
                return null;
            }

            return $file[0].files;
        };

        /**
         * returns the state of the burn after reading checkbox
         *
         * @name   topNav.getExpiration
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
         * @name   topNav.getOpenDiscussion
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
         * @name   topNav.getPassword
         * @function
         * @return {string}
         */
        me.getPassword = function()
        {
            return $passwordInput.val();
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
            $burnAfterReading = $('#burnafterreading');
            $burnAfterReadingOption = $('#burnafterreadingoption');
            $cloneButton = $('#clonebutton');
            $expiration = $('#expiration');
            $fileRemoveButton = $('#fileremovebutton');
            $formatter = $('#formatter');
            $newButton = $('#newbutton');
            $openDiscussionOption = $('#opendiscussionoption');
            $openDiscussion = $('#opendiscussion');
            $password = $('#password');
            $passwordInput = $('#passwordinput');
            $rawTextButton = $('#rawtextbutton');
            $sendButton = $('#sendbutton');
            $loadingIndicator = $('#loadingindicator');

            // bootstrap template drop down
            $('#language ul.dropdown-menu li a').click(me.setLanguage);
            // page template drop down
            $('#language select option').click(me.setLanguage);

            // bind events
            $burnAfterReading.change(changeBurnAfterReading);
            $openDiscussionOption.change(changeOpenDiscussion);
            $newButton.click(controller.newPaste);
            $sendButton.click(controller.submitPaste);
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
     * Responsible for AJAX requests, transparently handles encryption…
     *
     * @name state
     * @class
     */
    var uploader = (function () {
        var me = {};

        var successFunc = null,
            failureFunc = null;

        var url = helper.scriptLocation(),
            data = {},
            randomKey,
            password;

        // public variable ('constant') to prevent magic numbers
        me.error = {
            okay: 0,
            custom: 1,
            unknown: 2,
            serverError: 3
        };

        /**
         * ajaxHeaders to send in AJAX requests
         *
         * @private
         * @readonly
         * @enum   {Object}
         */
        var ajaxHeaders = {'X-Requested-With': 'JSONHttpRequest'};

        /**
         * called after successful upload
         *
         * @function
         * @param {int} status
         * @param {int} data - optional
         */
        function success(status, result)
        {
            // add useful data to result
            result.encryptionKey = randomKey;
            result.requestData = data;

            if (successFunc !== null) {
                successFunc(status, result);
            }
        }

        /**
         * called after a upload failure
         *
         * @name   uploader.submitPasteUpload
         * @function
         * @param {int} status - internal code
         * @param {int} data - original error code
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
         * @name   uploader.submitPasteUpload
         * @function
         */
        me.trigger = function()
        {
            console.log(data);
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
         * @name   uploader.setSuccess
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
         * @name   uploader.setSuccess
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
         * @name   uploader.prepare
         * @function
         * @param {string} newPassword
         * @return {object}
         */
        me.prepare = function(newPassword)
        {
            // set password
            password = newPassword;

            // entropy should already be checked
            // @TODO maybe move it here?

            // generate a new random key
            randomKey = cryptTool.getSymmetricKey();

            // reset data
            data = {};
        };

        /**
         * encrypts and sets the data
         *
         * @name   uploader.setData
         * @function
         * @param {string} index
         * @param {mixed} element
         */
        me.setData = function(index, element)
        {
            data[index] = cryptTool.cipher(randomKey, password, element);
        };

        /**
         * set the additional metadata to send unencrypted
         *
         * @name   uploader.setUnencryptedData
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
         * @name   uploader.setUnencryptedData
         * @function
         * @param {object} newData
         */
        me.setUnencryptedBulkData = function(newData)
        {
            $.extend(data, newData);
        };

        /**
         * init uploader
         *
         * @name   uploader.init
         * @function
         */
        me.init = function()
        {
            // nothing yet
        };

        return me;
    })();

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
         * called after successful upload
         *
         * @function
         * @param {int} status
         * @param {int} data
         */
        function showCreatedPaste(status, data) {
            topNav.hideLoading();
            console.log(data);

            var url = helper.scriptLocation() + '?' + data.id + '#' + data.encryptionKey,
                deleteUrl = helper.scriptLocation() + '?pasteid=' + data.id + '&deletetoken=' + data.deletetoken;

            alert.hideMessages();

            // show notification
            alert.createPasteNotification(url, deleteUrl)

            // show new URL in browser bar
            history.pushState({type: 'newpaste'}, document.title, url);

            topNav.showViewButtons();
            editor.hide();

            // parse and show text
            // (preparation already done in me.submitPaste())
            pasteViewer.trigger();
        }

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
            var dataToSend = {
                data:     cipherdata,
                parentid: parentid,
                pasteid:  helper.pasteId(),
                nickname: ciphernickname
            };

            $.ajax({
                type: 'POST',
                url: helper.scriptLocation(),
                data: dataToSend,
                dataType: 'json',
                headers: ajaxHeaders,
                success: function(data) {
                    if (data.status === 0)
                    {
                        controller.showStatus(i18n._('Comment posted.'));
                        $.ajax({
                            type: 'GET',
                            url: helper.scriptLocation() + '?' + helper.pasteId(),
                            dataType: 'json',
                            headers: ajaxHeaders,
                            success: function(data) {
                                if (data.status === 0)
                                {
                                    me.displayMessages(data);
                                }
                                else if (data.status === 1)
                                {
                                    alert.showError(i18n._('Could not refresh display: %s', data.message));
                                }
                                else
                                {
                                    alert.showError(i18n._('Could not refresh display: %s', i18n._('unknown status')));
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
         * sends a new paste to server
         *
         * @name   controller.submitPaste
         * @function
         */
        me.submitPaste = function()
        {
            // UI loading state
            topNav.hideCreateButtons();
            topNav.showLoading(i18n._('Sending paste...'), 0);
            topNav.collapseBar();

            // get data
            var plainText = editor.getText();

            // do not send if there is no data
            if (plainText.length === 0 && files === null) {
                // revert loading status…
                topNav.hideLoading();
                topNav.showCreateButtons();
                return;
            }

            topNav.showLoading(i18n._('Sending paste...'), 10);

            // check entropy
            if (!cryptTool.isEntropyReady()) {
                // display a message and wait
                alert.showStatus(i18n._('Please move your mouse for more entropy...'));

                cryptTool.addEntropySeedListener(function() {
                    me.submitPaste(event);
                });
            }

            // prepare uploader
            uploader.prepare(topNav.getPassword());

            // encrypt cipher data
            uploader.setData('data', plainText);

            // encrypt attachments
            var files = topNav.getFileList();
            if (files !== null) {
                var reader = new FileReader();

                // closure to capture the file information
                reader.onload = (function(file) {
                    return function(event) {
                        uploader.setData('attachment', event.target.result);
                        uploader.setData('attachmentname', file.name);
                    };
                })(files[0]);

                // actually read first file
                reader.readAsDataURL(files[0]);
            } else if (alert.hasAttachment()) {
                var attachment = alert.getAttachment();

                uploader.setData('attachment', attachment[0]);
                uploader.setUnencryptedData('attachmentname', attachment[1]); // @TODO does not encrypt file name??!
            }

            // set success/fail functions
            uploader.setSuccess(showCreatedPaste);
            uploader.setFailure(function (status, data) {
                // revert loading status…
                topNav.hideLoading();
                topNav.showCreateButtons();

                // show error message
                switch (status) {
                    case uploader.error['custom']:
                        alert.showError(i18n._('Could not create paste: %s', data.message));
                        break;
                    case uploader.error['unknown']:
                        alert.showError(i18n._('Could not create paste: %s', i18n._('unknown status')));
                        break;
                    case uploader.error['serverError']:
                        alert.showError(i18n._('Could not create paste: %s', i18n._('server error or not responding')));
                        break;
                    default:
                        alert.showError(i18n._('Could not create paste: %s', i18n._('unknown error')));
                        break;
                }
            });

            // fill it with unencrypted submitted options
            var format = pasteViewer.getFormat();
            uploader.setUnencryptedBulkData({
                expire:           topNav.getExpiration(),
                formatter:        format,
                burnafterreading: topNav.getBurnAfterReading() ? 1 : 0,
                opendiscussion:   topNav.getOpenDiscussion() ? 1 : 0
            });

            // prepare PasteViewer for later preview
            pasteViewer.setText(plainText);
            pasteViewer.setFormat(format);

            // send data
            uploader.trigger();
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

            alert.hideMessages();
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
            alert.init();
            uploader.init();
            modal.init();
            cryptTool.init();
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
                    alert.showError(i18n._('Cannot decrypt paste: Decryption key missing in URL (Did you use a redirector or an URL shortener which strips part of the URL?)'));
                    return;
                }

                // show proper elements on screen
                // topNav.hideCreateButtons(); // they should not be visible in the first place
                topNav.showViewButtons();
                me.displayMessages();
                return;
            }

            // check requirements for upload
            if (typeof FileReader === 'undefined') {
                alert.showError(i18n._('Your browser does not support uploading encrypted files. Please use a newer browser.'));
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
        topNav: topNav,
        alert: alert,
        uploader: uploader,
        controller: controller
    };
}(jQuery, sjcl, Base64, RawDeflate);
