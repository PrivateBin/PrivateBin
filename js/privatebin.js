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

'use strict';
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

// jQuery(document).ready(function() {
//     // startup
// }

jQuery.PrivateBin = function($, sjcl, Base64, RawDeflate) {
    /**
     * static helper methods
     *
     * @param  {object} window
     * @param  {object} document
     * @name helper
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
         * set text of a DOM element (required for IE),
         *
         * @name   helper.setElementText
         * @function
         * @param  {Object} element - a DOM element
         * @param  {string} text - the text to enter
         * @this is equivalent to element.text(text)
         * @TODO check for XSS attacks, usually no CSS can prevent them so this looks weird on the first look
         */
        me.setElementText = function(element, text)
        {
            // For IE<10: Doesn't support white-space:pre-wrap; so we have to do this...
            if ($('#oldienotice').is(':visible')) {
                var html = me.htmlEntities(text).replace(/\n/ig, '\r\n<br>');
                element.html('<pre>' + html + '</pre>');
            }
            // for other (sane) browsers:
            else
            {
                element.text(text);
            }
        };

        /**
         * replace last child of element with message
         *
         * @name   helper.setMessage
         * @function
         * @param  {Object} element - a jQuery wrapped DOM element
         * @param  {string} message - the message to append
         */
        me.setMessage = function(element, message)
        {
            var content = element.contents();
            if (content.length > 0)
            {
                content[content.length - 1].nodeValue = ' ' + message;
            }
            else
            {
                me.setElementText(element, message);
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
     * @name i18n
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
            if (newLanguage === language)
            {
                controller.init();
                return;
            }

            // if language is not supported, show error
            if (supportedLanguages.indexOf(newLanguage) === -1)
            {
                console.error('Language \'%s\' is not supported. Translation failed, fallback to English.', newLanguage);
                controller.init();
            }

            // load strongs from JSON
            $.getJSON('i18n/' + newLanguage + '.json', function(data) {
                language = newLanguage;
                translations = data;
            }).fail(function (data, textStatus, errorMsg) {
                console.error('Language \'%s\' could not be loaded (%s: %s). Translation failed, fallback to English.', newLanguage, textStatus, errorMsg);
            });

            controller.init();
        };

        return me;
    })(window, document);

    /**
     * filter methods
     *
     * @param  {object} window
     * @param  {object} document
     * @name filter
     * @class
     */
    var filter = (function (window, document) {
        var me = {};

        /**
         * compress a message (deflate compression), returns base64 encoded data
         *
         * @name   filter.compress
         * @function
         * @param  {string} message
         * @return {string} base64 data
         */
        me.compress = function(message)
        {
            return Base64.toBase64( RawDeflate.deflate( Base64.utob(message) ) );
        },

        /**
         * decompress a message compressed with filter.compress()
         *
         * @name   filter.decompress
         * @function
         * @param  {string} data - base64 data
         * @return {string} message
         */
        me.decompress = function(data)
        {
            return Base64.btou( RawDeflate.inflate( Base64.fromBase64(data) ) );
        },

        /**
         * compress, then encrypt message with given key and password
         *
         * @name   filter.cipher
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
                return sjcl.encrypt(key, me.compress(message), options);
            }
            return sjcl.encrypt(key + sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash(password)), me.compress(message), options);
        },

        /**
         * decrypt message with key, then decompress
         *
         * @name   filter.decipher
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
                    return me.decompress(sjcl.decrypt(key, data));
                }
                catch(err)
                {
                    try
                    {
                        return me.decompress(sjcl.decrypt(key + sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash(password)), data));
                    }
                    catch(e)
                    {
                        // ignore error, because ????? @TODO
                    }
                }
            }
            return '';
        }

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

        // jQuery pre-loaded objects
        var $attach,
            $attachment,
            $attachmentLink,
            $burnAfterReading,
            $burnAfterReadingOption,
            $cipherData,
            $clearText,
            $cloneButton,
            $clonedFile,
            $comments,
            $discussion,
            $errorMessage,
            $expiration,
            $fileRemoveButton,
            $fileWrap,
            $formatter,
            $image,
            $loadingIndicator,
            $message,
            $messageEdit,
            $messagePreview,
            $newButton,
            $openDisc, // @TODO: rename - too similar to openDiscussion, difference unclear
            $openDiscussion,
            $password,
            $passwordInput,
            $passwordModal,
            $passwordForm,
            $passwordDecrypt,
            $pasteResult,
            $pasteUrl,
            $prettyMessage,
            $prettyPrint,
            $preview,
            $rawTextButton,
            $remainingTime,
            $replyStatus,
            $sendButton,
            $status;

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
         * use given format on paste, defaults to plain text
         *
         * @name   controller.formatPaste
         * @function
         * @param  {string} format
         * @param  {string} text
         */
        me.formatPaste = function(format, text)
        {
            helper.setElementText($clearText, text);
            helper.setElementText($prettyPrint, text);

            switch (format || 'plaintext') {
                case 'markdown':
                    // silently fail if showdown is not available
                    // @TODO: maybe better show an error message? At least a warning?
                    if (typeof showdown === 'object')
                    {
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

                        $clearText.removeClass('hidden');
                    } else {
                        console.error('showdown is not loaded, could not parse Markdown');
                    }
                    $prettyMessage.addClass('hidden');
                    break;
                case 'syntaxhighlighting':
                    // silently fail if prettyprint is not available
                    // @TODO: maybe better show an error message? At least a warning?
                    if (typeof prettyPrintOne === 'function')
                    {
                        if (typeof prettyPrint === 'function')
                        {
                            prettyPrint();
                        }
                        $prettyPrint.html(
                            prettyPrintOne(
                                helper.htmlEntities(text), null, true
                            )
                        );
                    } else {
                        console.error('pretty print is not loaded, could not link ');
                    }
                    // fall through, as the rest is the same
                default: // = 'plaintext'
                    // convert URLs to clickable links
                    helper.urls2links($clearText);
                    helper.urls2links($prettyPrint);
                    $clearText.addClass('hidden');


                    $prettyPrint.css('white-space', 'pre-wrap');
                    $prettyPrint.css('word-break', 'normal');
                    $prettyPrint.removeClass('prettyprint');

                    $prettyMessage.removeClass('hidden');
            }
        };

        /**
         * show decrypted text in the display area, including discussion (if open)
         *
         * @name   controller.displayMessages
         * @function
         * @param  {Object} [paste] - (optional) object including comments to display (items = array with keys ('data','meta'))
         */
        me.displayMessages = function(paste)
        {
            paste = paste || $.parseJSON($cipherData.text());
            var key = helper.pageKey(),
                password = $passwordInput.val();
            if (!$prettyPrint.hasClass('prettyprinted')) {
                // Try to decrypt the paste.
                try
                {
                    if (paste.attachment)
                    {
                        var attachment = filter.decipher(key, password, paste.attachment);
                        if (attachment.length === 0)
                        {
                            if (password.length === 0)
                            {
                                me.requestPassword();
                                return;
                            }
                            attachment = filter.decipher(key, password, paste.attachment);
                        }
                        if (attachment.length === 0)
                        {
                            throw 'failed to decipher attachment';
                        }

                        if (paste.attachmentname)
                        {
                            var attachmentname = filter.decipher(key, password, paste.attachmentname);
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
                    var cleartext = filter.decipher(key, password, paste.data);
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
                        $('#pasteFormatter').val(paste.meta.formatter);
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
                helper.setMessage($remainingTime, i18n._(expirationLabel, expiration[0]));
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
                helper.setMessage($remainingTime, i18n._(
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

                // iterate over comments
                for (var i = 0; i < paste.comments.length; ++i)
                {
                    var $place = $comments,
                        comment = paste.comments[i],
                        commenttext = filter.decipher(key, password, comment.data),
                        // if parent comment exists, display below (CSS will automatically shift it to the right)
                        cname = '#comment_' + comment.parentid,
                        divComment = $('<article><div class="comment" id="comment_' + comment.id
                                   + '"><div class="commentmeta"><span class="nickname"></span>'
                                   + '<span class="commentdate"></span></div>'
                                   + '<div class="commentdata"></div>'
                                   + '<button class="btn btn-default btn-sm">'
                                   + i18n._('Reply') + '</button></div></article>'),
                        divCommentData = divComment.find('div.commentdata');

                    // if the element exists in page
                    if ($(cname).length)
                    {
                        $place = $(cname);
                    }
                    divComment.find('button').click({commentid: comment.id}, $.proxy(me.openReply, me));
                    helper.setElementText(divCommentData, commenttext);
                    helper.urls2links(divCommentData);

                    // try to get optional nickname
                    var nick = filter.decipher(key, password, comment.meta.nickname);
                    if (nick.length > 0)
                    {
                        divComment.find('span.nickname').text(nick);
                    }
                    else
                    {
                        divComment.find('span.nickname').html('<i>' + i18n._('Anonymous') + '</i>');
                    }
                    divComment.find('span.commentdate')
                              .text(' (' + (new Date(comment.meta.postdate * 1000).toLocaleString()) + ')')
                              .attr('title', 'CommentID: ' + comment.id);

                    // if an avatar is available, display it
                    if (comment.meta.vizhash)
                    {
                        divComment.find('span.nickname')
                                  .before(
                                    '<img src="' + comment.meta.vizhash + '" class="vizhash" title="' +
                                    i18n._('Anonymous avatar (Vizhash of the IP address)') + '" /> '
                                  );
                    }

                    $place.append(divComment);
                }
                var divComment = $(
                    '<div class="comment"><button class="btn btn-default btn-sm">' +
                    i18n._('Add comment') + '</button></div>'
                );
                divComment.find('button').click({commentid: helper.pasteId()}, $.proxy(me.openReply, me));
                $comments.append(divComment);
                $discussion.removeClass('hidden');
            }
        };

        /**
         * open the comment entry when clicking the "Reply" button of a comment
         *
         * @name   controller.openReply
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
                reply = $(
                    '<div class="reply"><input type="text" id="nickname" ' +
                    'class="form-control" title="' + hint + '" placeholder="' +
                    hint + '" /><textarea id="replymessage" class="replymessage ' +
                    'form-control" cols="80" rows="7"></textarea><br />' +
                    '<div id="replystatus"></div><button id="replybutton" ' +
                    'class="btn btn-default btn-sm">' + i18n._('Post comment') +
                    '</button></div>'
                );
            reply.find('button').click(
                {parentid: commentid},
                $.proxy(me.sendComment, me)
            );
            source.after(reply);
            $replyStatus = $('#replystatus');
            $('#replymessage').focus();
        };

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
                cipherdata = filter.cipher(key, $passwordInput.val(), replyMessage.val()),
                ciphernickname = '',
                nick = $('#nickname').val();
            if (nick.length > 0)
            {
                ciphernickname = filter.cipher(key, $passwordInput.val(), nick);
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
                            filter.cipher(randomkey, password, e.target.result),
                            filter.cipher(randomkey, password, theFile.name)
                        );
                    };
                })(files[0]);
                reader.readAsDataURL(files[0]);
            }
            else if($attachmentLink.attr('href'))
            {
                me.sendDataContinue(
                    randomkey,
                    filter.cipher(randomkey, password, $attachmentLink.attr('href')),
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
            var cipherdata = filter.cipher(randomkey, $passwordInput.val(), $message.val()),
                data_to_send = {
                    data:             cipherdata,
                    expire:           $('#pasteExpiration').val(),
                    formatter:        $('#pasteFormatter').val(),
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
                        me.showStatus('');
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
                        $pasteUrl.click($.proxy(me.pasteLinkClick, me));

                        var shortenButton = $('#shortenbutton');
                        if (shortenButton) {
                            shortenButton.click($.proxy(me.sendToShortener, me));
                        }
                        $('#deletelink').html('<a href="' + deleteUrl + '">' + i18n._('Delete data') + '</a>');
                        $pasteResult.removeClass('hidden');
                        // we pre-select the link so that the user only has to [Ctrl]+[c] the link
                        helper.selectText($pasteUrl[0]);
                        me.showStatus('');
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
         * put the screen in "New paste" mode
         *
         * @name   controller.stateNewPaste
         * @function
         */
        me.stateNewPaste = function()
        {
            $message.text('');
            $attachment.addClass('hidden');
            $cloneButton.addClass('hidden');
            $rawTextButton.addClass('hidden');
            $remainingTime.addClass('hidden');
            $pasteResult.addClass('hidden');
            $clearText.addClass('hidden');
            $discussion.addClass('hidden');
            $prettyMessage.addClass('hidden');
            $loadingIndicator.addClass('hidden');
            $sendButton.removeClass('hidden');
            $expiration.removeClass('hidden');
            $formatter.removeClass('hidden');
            $burnAfterReadingOption.removeClass('hidden');
            $openDisc.removeClass('hidden');
            $newButton.removeClass('hidden');
            $password.removeClass('hidden');
            $attach.removeClass('hidden');
            $message.removeClass('hidden');
            $preview.removeClass('hidden');
            $message.focus();
        };

        /**
         * put the screen in mode after submitting a paste
         *
         * @name   controller.stateSubmittingPaste
         * @function
         */
        me.stateSubmittingPaste = function()
        {
            $message.text('');
            $attachment.addClass('hidden');
            $cloneButton.addClass('hidden');
            $rawTextButton.addClass('hidden');
            $remainingTime.addClass('hidden');
            $pasteResult.addClass('hidden');
            $clearText.addClass('hidden');
            $discussion.addClass('hidden');
            $prettyMessage.addClass('hidden');
            $sendButton.addClass('hidden');
            $expiration.addClass('hidden');
            $formatter.addClass('hidden');
            $burnAfterReadingOption.addClass('hidden');
            $openDisc.addClass('hidden');
            $newButton.addClass('hidden');
            $password.addClass('hidden');
            $attach.addClass('hidden');
            $message.addClass('hidden');
            $preview.addClass('hidden');

            $loadingIndicator.removeClass('hidden');
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
            $message.text('');
            $attachment.addClass('hidden');
            $cloneButton.addClass('hidden');
            $rawTextButton.addClass('hidden');
            $remainingTime.addClass('hidden');
            $pasteResult.addClass('hidden');
            $clearText.addClass('hidden');
            $discussion.addClass('hidden');
            $prettyMessage.addClass('hidden');
            $sendButton.addClass('hidden');
            $expiration.addClass('hidden');
            $formatter.addClass('hidden');
            $burnAfterReadingOption.addClass('hidden');
            $openDisc.addClass('hidden');
            $password.addClass('hidden');
            $attach.addClass('hidden');
            $message.addClass('hidden');
            $preview.addClass('hidden');
            $loadingIndicator.addClass('hidden');

            $newButton.removeClass('hidden');
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

            if (!preview)
            {
                // no "clone" for IE<10.
                if ($('#oldienotice').is(":visible"))
                {
                    $cloneButton.addClass('hidden');
                }
                else
                {
                    $cloneButton.removeClass('hidden');
                }

                $rawTextButton.removeClass('hidden');
                $sendButton.addClass('hidden');
                $attach.addClass('hidden');
                $expiration.addClass('hidden');
                $formatter.addClass('hidden');
                $burnAfterReadingOption.addClass('hidden');
                $openDisc.addClass('hidden');
                $newButton.removeClass('hidden');
                $preview.addClass('hidden');
            }

            $pasteResult.addClass('hidden');
            $message.addClass('hidden');
            $clearText.addClass('hidden');
            $prettyMessage.addClass('hidden');
            $loadingIndicator.addClass('hidden');
        };

        /**
         * when "burn after reading" is checked, disable discussion
         *
         * @name   controller.changeBurnAfterReading
         * @function
         */
        me.changeBurnAfterReading = function()
        {
            if ($burnAfterReading.is(':checked') )
            {
                $openDisc.addClass('buttondisabled');
                $openDiscussion.attr({checked: false, disabled: true});
            }
            else
            {
                $openDisc.removeClass('buttondisabled');
                $openDiscussion.removeAttr('disabled');
            }
        };

        /**
         * when discussion is checked, disable "burn after reading"
         *
         * @name   controller.changeOpenDisc
         * @function
         */
        me.changeOpenDisc = function()
        {
            if ($openDiscussion.is(':checked') )
            {
                $burnAfterReadingOption.addClass('buttondisabled');
                $burnAfterReading.attr({checked: false, disabled: true});
            }
            else
            {
                $burnAfterReadingOption.removeClass('buttondisabled');
                $burnAfterReading.removeAttr('disabled');
            }
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
         * return raw text
         *
         * @name   controller.rawText
         * @function
         * @param  {Event} event
         */
        me.rawText = function(event)
        {
            var paste = $('#pasteFormatter').val() === 'markdown' ?
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
            event.preventDefault();
            me.stateNewPaste();

            // erase the id and the key in url
            history.replaceState(null, document.title, helper.scriptLocation());

            me.showStatus('');
            if ($attachmentLink.attr('href'))
            {
                $clonedFile.removeClass('hidden');
                $fileWrap.addClass('hidden');
            }
            $message.text(
                $('#pasteFormatter').val() === 'markdown' ?
                    $prettyPrint.text() : $clearText.text()
            );
            $('.navbar-toggle').click();
        };

        /**
         * set the expiration on bootstrap templates
         *
         * @name   controller.setExpiration
         * @function
         * @param  {Event} event
         */
        me.setExpiration = function(event)
        {
            event.preventDefault();
            var target = $(event.target);
            $('#pasteExpiration').val(target.data('expiration'));
            $('#pasteExpirationDisplay').text(target.text());
        };

        /**
         * set the format on bootstrap templates
         *
         * @name   controller.setFormat
         * @function
         * @param  {Event} event
         */
        me.setFormat = function(event)
        {
            var target = $(event.target);
            $('#pasteFormatter').val(target.data('format'));
            $('#pasteFormatterDisplay').text(target.text());

            if ($messagePreview.parent().hasClass('active')) {
                me.viewPreview(event);
            }
            event.preventDefault();
        };

        /**
         * set the language in a cookie and reload the page
         *
         * @name   controller.setLanguage
         * @function
         * @param  {Event} event
         */
        me.setLanguage = function(event)
        {
            document.cookie = 'lang=' + $(event.target).data('lang');
            me.reloadPage(event);
        };

        /**
         * support input of tab character
         *
         * @name   controller.supportTabs
         * @function
         * @param  {Event} event
         * @TODO doc what is @this here?
         */
        me.supportTabs = function(event)
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
        };

        /**
         * view the editor tab
         *
         * @name   controller.viewEditor
         * @function
         * @param  {Event} event
         */
        me.viewEditor = function(event)
        {
            $messagePreview.parent().removeClass('active');
            $messageEdit.parent().addClass('active');
            $message.focus();
            me.stateNewPaste();

            event.preventDefault();
        };

        /**
         * view the preview tab
         *
         * @name   controller.viewPreview
         * @function
         * @param  {Event} event
         */
        me.viewPreview = function(event)
        {
            $messageEdit.parent().removeClass('active');
            $messagePreview.parent().addClass('active');
            $message.focus();
            me.stateExistingPaste(true);
            me.formatPaste($('#pasteFormatter').val(), $message.val());

            event.preventDefault();
        };

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
         * create a new paste
         *
         * @name   controller.newPaste
         * @function
         */
        me.newPaste = function()
        {
            me.stateNewPaste();
            me.showStatus('');
            $message.text('');
            me.changeBurnAfterReading();
            me.changeOpenDisc();
        };

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
         * display an error message,
         * we use the same function for paste and reply to comments
         *
         * @name   controller.showError
         * @function
         * @param  {string} message - text to display
         */
        me.showError = function(message)
        {
            if ($status.length)
            {
                $status.addClass('errorMessage').text(message);
            }
            else
            {
                $errorMessage.removeClass('hidden');
                helper.setMessage($errorMessage, message);
            }
            if (typeof $replyStatus !== 'undefined') {
                $replyStatus.addClass('errorMessage');
                $replyStatus.addClass($errorMessage.attr('class'));
                if ($status.length)
                {
                    $replyStatus.html($status.html());
                }
                else
                {
                    $replyStatus.html($errorMessage.html());
                }
            }
        };

        /**
         * display a status message,
         * we use the same function for paste and reply to comments
         *
         * @name   controller.showStatus
         * @function
         * @param  {string} message - text to display
         * @param  {boolean} [spin=false] - (optional) tell if the "spinning" animation should be displayed, defaults to false
         */
        me.showStatus = function(message, spin)
        {
            if (spin || false)
            {
                var img = '<img src="img/busy.gif" style="width:16px;height:9px;margin:0 4px 0 0;" />';
                $status.prepend(img);
                if (typeof $replyStatus !== 'undefined') {
                    $replyStatus.prepend(img);
                }
            }
            if (typeof $replyStatus !== 'undefined') {
                $replyStatus.removeClass('errorMessage').text(message);
            }
            if (!message)
            {
                $status.html(' ');
                return;
            }
            if (message === '')
            {
                $status.html(' ');
                return;
            }
            $status.removeClass('errorMessage').text(message);
        };

        /**
         * bind events to DOM elements
         *
         * @private
         * @function
         */
        function bindEvents()
        {
            $burnAfterReading.change($.proxy(me.changeBurnAfterReading, me));
            $openDisc.change($.proxy(me.changeOpenDisc, me));
            $sendButton.click($.proxy(me.sendData, me));
            $cloneButton.click($.proxy(me.clonePaste, me));
            $rawTextButton.click($.proxy(me.rawText, me));
            $fileRemoveButton.click($.proxy(me.removeAttachment, me));
            $('.reloadlink').click($.proxy(me.reloadPage, me));
            $message.keydown(me.supportTabs);
            $messageEdit.click($.proxy(me.viewEditor, me));
            $messagePreview.click($.proxy(me.viewPreview, me));

            // bootstrap template drop downs
            $('ul.dropdown-menu li a', $('#expiration').parent()).click($.proxy(me.setExpiration, me));
            $('ul.dropdown-menu li a', $('#formatter').parent()).click($.proxy(me.setFormat, me));
            $('#language ul.dropdown-menu li a').click($.proxy(me.setLanguage, me));

            // page template drop down
            $('#language select option').click($.proxy(me.setLanguage, me));

            // handle modal password request on decryption
            $passwordModal.on('shown.bs.modal', $.proxy($passwordDecrypt.focus, me));
            $passwordModal.on('hidden.bs.modal', $.proxy(me.decryptPasswordModal, me));
            $passwordForm.submit($.proxy(me.submitPasswordModal, me));

            $(window).on('popstate', $.proxy(me.historyChange, me));
        };

        /**
         * main application
         *
         * @name   controller.init
         * @function
         */
        me.init = function()
        {
            // hide "no javascript" message
            $('#noscript').hide();

            // preload jQuery wrapped DOM elements and bind events
            $attach = $('#attach');
            $attachment = $('#attachment');
            $attachmentLink = $('#attachment a');
            $burnAfterReading = $('#burnafterreading');
            $burnAfterReadingOption = $('#burnafterreadingoption');
            $cipherData = $('#cipherdata');
            $clearText = $('#cleartext');
            $cloneButton = $('#clonebutton');
            $clonedFile = $('#clonedfile');
            $comments = $('#comments');
            $discussion = $('#discussion');
            $errorMessage = $('#errormessage');
            $expiration = $('#expiration');
            $fileRemoveButton = $('#fileremovebutton');
            $fileWrap = $('#filewrap');
            $formatter = $('#formatter');
            $image = $('#image');
            $loadingIndicator = $('#loadingindicator');
            $message = $('#message');
            $messageEdit = $('#messageedit');
            $messagePreview = $('#messagepreview');
            $newButton = $('#newbutton');
            $openDisc = $('#opendisc');
            $openDiscussion = $('#opendiscussion');
            $password = $('#password');
            $passwordInput = $('#passwordinput');
            $passwordModal = $('#passwordmodal');
            $passwordForm = $('#passwordform');
            $passwordDecrypt = $('#passworddecrypt');
            $pasteResult = $('#pasteresult');
            // $pasteUrl is saved in sendDataContinue() if/after it is
            // actually created
            $prettyMessage = $('#prettymessage');
            $prettyPrint = $('#prettyprint');
            $preview = $('#preview');
            $rawTextButton = $('#rawtextbutton');
            $remainingTime = $('#remainingtime');
            // $replyStatus is saved in openReply()
            $sendButton = $('#sendbutton');
            $status = $('#status');
            bindEvents();

            // display status returned by php code, if any (eg. paste was properly deleted)
            if ($status.text().length > 0)
            {
                me.showStatus($status.text());
                return;
            }

            // keep line height even if content empty
            $status.html(' ');

            // display an existing paste
            if ($cipherData.text().length > 1)
            {
                // missing decryption key in URL?
                if (window.location.hash.length === 0)
                {
                    me.showError(i18n._('Cannot decrypt paste: Decryption key missing in URL (Did you use a redirector or an URL shortener which strips part of the URL?)'));
                    return;
                }

                // show proper elements on screen
                me.stateExistingPaste();
                me.displayMessages();
            }
            // display error message from php code
            else if ($errorMessage.text().length > 1)
            {
                me.showError($errorMessage.text());
            }
            // create a new paste
            else
            {
                me.newPaste();
            }
        };

        return me;
    })(window, document);

    /**
     * main application start, called when DOM is fully loaded and
     * runs controller initalization after translations are loaded
     */
    $(i18n.loadTranslations);

    return {
        helper: helper,
        i18n: i18n,
        filter: filter,
        controller: controller
    };
}(jQuery, sjcl, Base64, RawDeflate);
