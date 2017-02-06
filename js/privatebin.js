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

jQuery.PrivateBin = function($, sjcl, Base64, RawDeflate) {
    /**
     * static helper methods
     *
     * @name helper
     * @class
     */
    var helper = {
        /**
         * converts a duration (in seconds) into human friendly approximation
         *
         * @name helper.secondsToHuman
         * @function
         * @param  {number} seconds
         * @return {Array}
         */
        secondsToHuman: function(seconds)
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
        },

        /**
         * text range selection
         *
         * @see    {@link https://stackoverflow.com/questions/985272/jquery-selecting-text-in-an-element-akin-to-highlighting-with-your-mouse}
         * @name   helper.selectText
         * @function
         * @param  {string} element - Indentifier of the element to select (id="")
         */
        selectText: function(element)
        {
            var doc = document,
                text = doc.getElementById(element),
                range,
                selection;

            // MS
            if (doc.body.createTextRange)
            {
                range = doc.body.createTextRange();
                range.moveToElementText(text);
                range.select();
            }
            // all others
            else if (window.getSelection)
            {
                selection = window.getSelection();
                range = doc.createRange();
                range.selectNodeContents(text);
                selection.removeAllRanges();
                selection.addRange(range);
            }
        },

        /**
         * set text of a DOM element (required for IE),
         * this is equivalent to element.text(text)
         *
         * @name   helper.setElementText
         * @function
         * @param  {Object} element - a DOM element
         * @param  {string} text - the text to enter
         */
        setElementText: function(element, text)
        {
            // For IE<10: Doesn't support white-space:pre-wrap; so we have to do this...
            if ($('#oldienotice').is(':visible')) {
                var html = this.htmlEntities(text).replace(/\n/ig, '\r\n<br>');
                element.html('<pre>' + html + '</pre>');
            }
            // for other (sane) browsers:
            else
            {
                element.text(text);
            }
        },

        /**
         * replace last child of element with message
         *
         * @name   helper.setMessage
         * @function
         * @param  {Object} element - a jQuery wrapped DOM element
         * @param  {string} message - the message to append
         */
        setMessage: function(element, message)
        {
            var content = element.contents();
            if (content.length > 0)
            {
                content[content.length - 1].nodeValue = ' ' + message;
            }
            else
            {
                this.setElementText(element, message);
            }
        },

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
        urls2links: function(element)
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
        },

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
        sprintf: function()
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
        },

        /**
         * get value of cookie, if it was set, empty string otherwise
         *
         * @see    {@link http://www.w3schools.com/js/js_cookies.asp}
         * @name   helper.getCookie
         * @function
         * @param  {string} cname
         * @return {string}
         */
        getCookie: function(cname) {
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
        },

        /**
         * get the current script location (without search or hash part of the URL),
         * eg. http://example.com/path/?aaaa#bbbb --> http://example.com/path/
         *
         * @name   helper.scriptLocation
         * @function
         * @return {string} current script location
         */
        scriptLocation: function()
        {
            var scriptLocation = window.location.href.substring(
                    0,
                    window.location.href.length - window.location.search.length - window.location.hash.length
                ),
                hashIndex = scriptLocation.indexOf('?');
            if (hashIndex !== -1)
            {
                scriptLocation = scriptLocation.substring(0, hashIndex);
            }
            return scriptLocation;
        },

        /**
         * get the pastes unique identifier from the URL,
         * eg. http://example.com/path/?c05354954c49a487#c05354954c49a487 returns c05354954c49a487
         *
         * @name   helper.pasteId
         * @function
         * @return {string} unique identifier
         */
        pasteId: function()
        {
            return window.location.search.substring(1);
        },

        /**
         * return the deciphering key stored in anchor part of the URL
         *
         * @name   helper.pageKey
         * @function
         * @return {string} key
         */
        pageKey: function()
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
        },

        /**
         * convert all applicable characters to HTML entities
         *
         * @see    {@link https://www.owasp.org/index.php/XSS_(Cross_Site_Scripting)_Prevention_Cheat_Sheet#RULE_.231_-_HTML_Escape_Before_Inserting_Untrusted_Data_into_HTML_Element_Content}
         * @name   helper.htmlEntities
         * @function
         * @param  {string} str
         * @return {string} escaped HTML
         */
        htmlEntities: function(str) {
            return String(str).replace(
                /[&<>"'`=\/]/g, function(s) {
                    return helper.entityMap[s];
                });
        },

        /**
         * character to HTML entity lookup table
         *
         * @see    {@link https://github.com/janl/mustache.js/blob/master/mustache.js#L60}
         * @name   helper.entityMap
         * @enum   {Object}
         * @readonly
         */
        entityMap: {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        }
    };

    /**
     * internationalization methods
     *
     * @name i18n
     * @class
     */
    var i18n = {
        /**
         * supported languages, minus the built in 'en'
         *
         * @name   i18n.supportedLanguages
         * @prop   {string[]}
         * @readonly
         */
        supportedLanguages: ['de', 'es', 'fr', 'it', 'no', 'pl', 'oc', 'ru', 'sl', 'zh'],

        /**
         * translate a string, alias for i18n.translate()
         *
         * @name   i18n._
         * @function
         * @param  {string} messageId
         * @param  {...*} args - one or multiple parameters injected into placeholders
         * @return {string}
         */
        _: function()
        {
            return this.translate(arguments);
        },

        /**
         * translate a string
         *
         * @name   i18n.translate
         * @function
         * @param  {string} messageId
         * @param  {...*} args - one or multiple parameters injected into placeholders
         * @return {string}
         */
        translate: function()
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
            if (!this.translations.hasOwnProperty(messageId))
            {
                if (this.language !== 'en')
                {
                    console.debug(
                        'Missing ' + this.language + ' translation for: ' + messageId
                    );
                }
                this.translations[messageId] = args[0];
            }
            if (usesPlurals && $.isArray(this.translations[messageId]))
            {
                var n = parseInt(args[1] || 1, 10),
                    key = this.getPluralForm(n),
                    maxKey = this.translations[messageId].length - 1;
                if (key > maxKey)
                {
                    key = maxKey;
                }
                args[0] = this.translations[messageId][key];
                args[1] = n;
            }
            else
            {
                args[0] = this.translations[messageId];
            }
            return helper.sprintf(args);
        },

        /**
         * per language functions to use to determine the plural form
         *
         * @see    {@link http://localization-guide.readthedocs.org/en/latest/l10n/pluralforms.html}
         * @name   i18n.getPluralForm
         * @function
         * @param  {number} n
         * @return {number} array key
         */
        getPluralForm: function(n) {
            switch (this.language)
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
        },

        /**
         * load translations into cache, then trigger controller initialization
         *
         * @name   i18n.loadTranslations
         * @function
         */
        loadTranslations: function()
        {
            var language = helper.getCookie('lang');
            if (language.length === 0)
            {
                language = (navigator.language || navigator.userLanguage).substring(0, 2);
            }
            // note that 'en' is built in, so no translation is necessary
            if (i18n.supportedLanguages.indexOf(language) === -1)
            {
                controller.init();
            }
            else
            {
                $.getJSON('i18n/' + language + '.json', function(data) {
                    i18n.language = language;
                    i18n.translations = data;
                    controller.init();
                });
            }
        },

        /**
         * built in language
         *
         * @name   i18n.language
         * @prop   {string}
         */
        language: 'en',

        /**
         * translation cache
         *
         * @name   i18n.translations
         * @enum   {Object}
         */
        translations: {}
    };

    /**
     * filter methods
     *
     * @name filter
     * @class
     */
    var filter = {
        /**
         * compress a message (deflate compression), returns base64 encoded data
         *
         * @name   filter.compress
         * @function
         * @param  {string} message
         * @return {string} base64 data
         */
        compress: function(message)
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
        decompress: function(data)
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
        cipher: function(key, password, message)
        {
            // Galois Counter Mode, keysize 256 bit, authentication tag 128 bit
            var options = {mode: 'gcm', ks: 256, ts: 128};
            if ((password || '').trim().length === 0)
            {
                return sjcl.encrypt(key, this.compress(message), options);
            }
            return sjcl.encrypt(key + sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash(password)), this.compress(message), options);
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
        decipher: function(key, password, data)
        {
            if (data !== undefined)
            {
                try
                {
                    return this.decompress(sjcl.decrypt(key, data));
                }
                catch(err)
                {
                    try
                    {
                        return this.decompress(sjcl.decrypt(key + sjcl.codec.hex.fromBits(sjcl.hash.sha256.hash(password)), data));
                    }
                    catch(e)
                    {}
                }
            }
            return '';
        }
    };

    /**
     * PrivateBin logic
     *
     * @name controller
     * @class
     */
    var controller = {
        /**
         * headers to send in AJAX requests
         *
         * @name   controller.headers
         * @enum   {Object}
         */
        headers: {'X-Requested-With': 'JSONHttpRequest'},

        /**
         * URL shortners create address
         *
         * @name   controller.shortenerUrl
         * @prop   {string}
         */
        shortenerUrl: '',

        /**
         * URL of newly created paste
         *
         * @name   controller.createdPasteUrl
         * @prop   {string}
         */
        createdPasteUrl: '',

        /**
         * ask the user for the password and set it
         *
         * @name   controller.requestPassword
         * @function
         */
        requestPassword: function()
        {
            if (this.passwordModal.length === 0) {
                var password = prompt(i18n._('Please enter the password for this paste:'), '');
                if (password === null)
                {
                    throw 'password prompt canceled';
                }
                if (password.length === 0)
                {
                    this.requestPassword();
                } else {
                    this.passwordInput.val(password);
                    this.displayMessages();
                }
            } else {
                this.passwordModal.modal();
            }
        },

        /**
         * use given format on paste, defaults to plain text
         *
         * @name   controller.formatPaste
         * @function
         * @param  {string} format
         * @param  {string} text
         */
        formatPaste: function(format, text)
        {
            helper.setElementText(this.clearText, text);
            helper.setElementText(this.prettyPrint, text);
            switch (format || 'plaintext')
            {
                case 'markdown':
                    if (typeof showdown === 'object')
                    {
                        var converter = new showdown.Converter({
                            strikethrough: true,
                            tables: true,
                            tablesHeaderId: true
                        });
                        this.clearText.html(
                            converter.makeHtml(text)
                        );
                        // add table classes from bootstrap css
                        this.clearText.find('table').addClass('table-condensed table-bordered');

                        this.clearText.removeClass('hidden');
                    }
                    this.prettyMessage.addClass('hidden');
                    break;
                case 'syntaxhighlighting':
                    if (typeof prettyPrintOne === 'function')
                    {
                        if (typeof prettyPrint === 'function')
                        {
                            prettyPrint();
                        }
                        this.prettyPrint.html(
                            prettyPrintOne(
                                helper.htmlEntities(text), null, true
                            )
                        );
                    }
                    // fall through, as the rest is the same
                default:
                    // convert URLs to clickable links
                    helper.urls2links(this.clearText);
                    helper.urls2links(this.prettyPrint);
                    this.clearText.addClass('hidden');
                    if (format === 'plaintext')
                    {
                        this.prettyPrint.css('white-space', 'pre-wrap');
                        this.prettyPrint.css('word-break', 'normal');
                        this.prettyPrint.removeClass('prettyprint');
                    }
                    this.prettyMessage.removeClass('hidden');
            }
        },

        /**
         * show decrypted text in the display area, including discussion (if open)
         *
         * @name   controller.displayMessages
         * @function
         * @param  {Object} [paste] - (optional) object including comments to display (items = array with keys ('data','meta'))
         */
        displayMessages: function(paste)
        {
            paste = paste || $.parseJSON(this.cipherData.text());
            var key = helper.pageKey(),
                password = this.passwordInput.val();
            if (!this.prettyPrint.hasClass('prettyprinted')) {
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
                                this.requestPassword();
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
                                this.attachmentLink.attr('download', attachmentname);
                            }
                        }
                        this.attachmentLink.attr('href', attachment);
                        this.attachment.removeClass('hidden');

                        // if the attachment is an image, display it
                        var imagePrefix = 'data:image/';
                        if (attachment.substring(0, imagePrefix.length) === imagePrefix)
                        {
                            this.image.html(
                                $(document.createElement('img'))
                                    .attr('src', attachment)
                                    .attr('class', 'img-thumbnail')
                            );
                            this.image.removeClass('hidden');
                        }
                    }
                    var cleartext = filter.decipher(key, password, paste.data);
                    if (cleartext.length === 0 && password.length === 0 && !paste.attachment)
                    {
                        this.requestPassword();
                        return;
                    }
                    if (cleartext.length === 0 && !paste.attachment)
                    {
                        throw 'failed to decipher message';
                    }

                    this.passwordInput.val(password);
                    if (cleartext.length > 0)
                    {
                        $('#pasteFormatter').val(paste.meta.formatter);
                        this.formatPaste(paste.meta.formatter, cleartext);
                    }
                }
                catch(err)
                {
                    this.clearText.addClass('hidden');
                    this.prettyMessage.addClass('hidden');
                    this.cloneButton.addClass('hidden');
                    this.showError(i18n._('Could not decrypt data (Wrong key?)'));
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
                helper.setMessage(this.remainingTime, i18n._(expirationLabel, expiration[0]));
                this.remainingTime.removeClass('foryoureyesonly')
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
                    headers: this.headers
                })
                .fail(function() {
                    controller.showError(i18n._('Could not delete the paste, it was not stored in burn after reading mode.'));
                });
                helper.setMessage(this.remainingTime, i18n._(
                    'FOR YOUR EYES ONLY. Don\'t close this window, this message can\'t be displayed again.'
                ));
                this.remainingTime.addClass('foryoureyesonly')
                                  .removeClass('hidden');
                // discourage cloning (as it can't really be prevented)
                this.cloneButton.addClass('hidden');
            }

            // if the discussion is opened on this paste, display it
            if (paste.meta.opendiscussion)
            {
                this.comments.html('');

                // iterate over comments
                for (var i = 0; i < paste.comments.length; ++i)
                {
                    var place = this.comments,
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
                        place = $(cname);
                    }
                    divComment.find('button').click({commentid: comment.id}, $.proxy(this.openReply, this));
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

                    place.append(divComment);
                }
                var divComment = $(
                    '<div class="comment"><button class="btn btn-default btn-sm">' +
                    i18n._('Add comment') + '</button></div>'
                );
                divComment.find('button').click({commentid: helper.pasteId()}, $.proxy(this.openReply, this));
                this.comments.append(divComment);
                this.discussion.removeClass('hidden');
            }
        },

        /**
         * open the comment entry when clicking the "Reply" button of a comment
         *
         * @name   controller.openReply
         * @function
         * @param  {Event} event
         */
        openReply: function(event)
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
                $.proxy(this.sendComment, this)
            );
            source.after(reply);
            this.replyStatus = $('#replystatus');
            $('#replymessage').focus();
        },

        /**
         * send a reply in a discussion
         *
         * @name   controller.sendComment
         * @function
         * @param  {Event} event
         */
        sendComment: function(event)
        {
            event.preventDefault();
            this.errorMessage.addClass('hidden');
            // do not send if no data
            var replyMessage = $('#replymessage');
            if (replyMessage.val().length === 0)
            {
                return;
            }

            this.showStatus(i18n._('Sending comment...'), true);
            var parentid = event.data.parentid,
                key = helper.pageKey(),
                cipherdata = filter.cipher(key, this.passwordInput.val(), replyMessage.val()),
                ciphernickname = '',
                nick = $('#nickname').val();
            if (nick.length > 0)
            {
                ciphernickname = filter.cipher(key, this.passwordInput.val(), nick);
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
                headers: this.headers,
                success: function(data)
                {
                    if (data.status === 0)
                    {
                        controller.showStatus(i18n._('Comment posted.'));
                        $.ajax({
                            type: 'GET',
                            url: helper.scriptLocation() + '?' + helper.pasteId(),
                            dataType: 'json',
                            headers: controller.headers,
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
        },

        /**
         * send a new paste to server
         *
         * @name   controller.sendData
         * @function
         * @param  {Event} event
         */
        sendData: function(event)
        {
            event.preventDefault();
            var file = document.getElementById('file'),
                files = (file && file.files) ? file.files : null; // FileList object

            // do not send if no data.
            if (this.message.val().length === 0 && !(files && files[0]))
            {
                return;
            }

            // if sjcl has not collected enough entropy yet, display a message
            if (!sjcl.random.isReady())
            {
                this.showStatus(i18n._('Sending paste (Please move your mouse for more entropy)...'), true);
                sjcl.random.addEventListener('seeded', function() {
                    this.sendData(event);
                });
                return;
            }

            $('.navbar-toggle').click();
            this.password.addClass('hidden');
            this.showStatus(i18n._('Sending paste...'), true);

            this.stateSubmittingPaste();

            var randomkey = sjcl.codec.base64.fromBits(sjcl.random.randomWords(8, 0), 0),
                password = this.passwordInput.val();
            if(files && files[0])
            {
                if(typeof FileReader === undefined)
                {
                    // revert loading status…
                    this.stateNewPaste();
                    this.showError(i18n._('Your browser does not support uploading encrypted files. Please use a newer browser.'));
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
            else if(this.attachmentLink.attr('href'))
            {
                this.sendDataContinue(
                    randomkey,
                    filter.cipher(randomkey, password, this.attachmentLink.attr('href')),
                    this.attachmentLink.attr('download')
                );
            }
            else
            {
                this.sendDataContinue(randomkey, '', '');
            }
        },

        /**
         * send a new paste to server, step 2
         *
         * @name   controller.sendDataContinue
         * @function
         * @param  {string} randomkey
         * @param  {string} cipherdata_attachment
         * @param  {string} cipherdata_attachment_name
         */
        sendDataContinue: function(randomkey, cipherdata_attachment, cipherdata_attachment_name)
        {
            var cipherdata = filter.cipher(randomkey, this.passwordInput.val(), this.message.val()),
                data_to_send = {
                    data:             cipherdata,
                    expire:           $('#pasteExpiration').val(),
                    formatter:        $('#pasteFormatter').val(),
                    burnafterreading: this.burnAfterReading.is(':checked') ? 1 : 0,
                    opendiscussion:   this.openDiscussion.is(':checked') ? 1 : 0
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
                headers: this.headers,
                success: function(data)
                {
                    if (data.status === 0) {
                        controller.stateExistingPaste();
                        var url = helper.scriptLocation() + '?' + data.id + '#' + randomkey,
                            deleteUrl = helper.scriptLocation() + '?pasteid=' + data.id + '&deletetoken=' + data.deletetoken;
                        controller.showStatus('');
                        controller.errorMessage.addClass('hidden');
                        // show new URL in browser bar
                        history.pushState({type: 'newpaste'}, document.title, url);

                        $('#pastelink').html(
                            i18n._(
                                'Your paste is <a id="pasteurl" href="%s">%s</a> <span id="copyhint">(Hit [Ctrl]+[c] to copy)</span>',
                                url, url
                            ) + controller.shortenUrl(url)
                        );
                        // save newly created element
                        controller.pasteUrl = $('#pasteurl');
                        // and add click event
                        controller.pasteUrl.click($.proxy(controller.pasteLinkClick, controller));

                        var shortenButton = $('#shortenbutton');
                        if (shortenButton) {
                            shortenButton.click($.proxy(controller.sendToShortener, controller));
                        }
                        $('#deletelink').html('<a href="' + deleteUrl + '">' + i18n._('Delete data') + '</a>');
                        controller.pasteResult.removeClass('hidden');
                        // we pre-select the link so that the user only has to [Ctrl]+[c] the link
                        helper.selectText('pasteurl');
                        controller.showStatus('');
                        controller.formatPaste(data_to_send.formatter, controller.message.val());
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
                this.stateNewPaste();
                controller.showError(i18n._('Could not create paste: %s', i18n._('server error or not responding')));
            });
        },

        /**
         * check if a URL shortener was defined and create HTML containing a link to it
         *
         * @name   controller.shortenUrl
         * @function
         * @param  {string} url
         * @return {string} html
         */
        shortenUrl: function(url)
        {
            var shortenerHtml = $('#shortenbutton');
            if (shortenerHtml) {
                this.shortenerUrl = shortenerHtml.data('shortener');
                this.createdPasteUrl = url;
                return ' ' + $('<div />').append(shortenerHtml.clone()).html();
            }
            return '';
        },

        /**
         * put the screen in "New paste" mode
         *
         * @name   controller.stateNewPaste
         * @function
         */
        stateNewPaste: function()
        {
            this.message.text('');
            this.attachment.addClass('hidden');
            this.cloneButton.addClass('hidden');
            this.rawTextButton.addClass('hidden');
            this.remainingTime.addClass('hidden');
            this.pasteResult.addClass('hidden');
            this.clearText.addClass('hidden');
            this.discussion.addClass('hidden');
            this.prettyMessage.addClass('hidden');
            this.loadingIndicator.addClass('hidden');
            this.sendButton.removeClass('hidden');
            this.expiration.removeClass('hidden');
            this.formatter.removeClass('hidden');
            this.burnAfterReadingOption.removeClass('hidden');
            this.openDisc.removeClass('hidden');
            this.newButton.removeClass('hidden');
            this.password.removeClass('hidden');
            this.attach.removeClass('hidden');
            this.message.removeClass('hidden');
            this.preview.removeClass('hidden');
            this.message.focus();
        },

        /**
         * put the screen in mode after submitting a paste
         *
         * @name   controller.stateSubmittingPaste
         * @function
         */
        stateSubmittingPaste: function()
        {
            this.message.text('');
            this.attachment.addClass('hidden');
            this.cloneButton.addClass('hidden');
            this.rawTextButton.addClass('hidden');
            this.remainingTime.addClass('hidden');
            this.pasteResult.addClass('hidden');
            this.clearText.addClass('hidden');
            this.discussion.addClass('hidden');
            this.prettyMessage.addClass('hidden');
            this.sendButton.addClass('hidden');
            this.expiration.addClass('hidden');
            this.formatter.addClass('hidden');
            this.burnAfterReadingOption.addClass('hidden');
            this.openDisc.addClass('hidden');
            this.newButton.addClass('hidden');
            this.password.addClass('hidden');
            this.attach.addClass('hidden');
            this.message.addClass('hidden');
            this.preview.addClass('hidden');

            this.loadingIndicator.removeClass('hidden');
        },

        /**
         * put the screen in "Existing paste" mode
         *
         * @name   controller.stateExistingPaste
         * @function
         * @param  {boolean} [preview=false] - (optional) tell if the preview tabs should be displayed, defaults to false
         */
        stateExistingPaste: function(preview)
        {
            preview = preview || false;

            if (!preview)
            {
                // no "clone" for IE<10.
                if ($('#oldienotice').is(":visible"))
                {
                    this.cloneButton.addClass('hidden');
                }
                else
                {
                    this.cloneButton.removeClass('hidden');
                }

                this.rawTextButton.removeClass('hidden');
                this.sendButton.addClass('hidden');
                this.attach.addClass('hidden');
                this.expiration.addClass('hidden');
                this.formatter.addClass('hidden');
                this.burnAfterReadingOption.addClass('hidden');
                this.openDisc.addClass('hidden');
                this.newButton.removeClass('hidden');
                this.preview.addClass('hidden');
            }

            this.pasteResult.addClass('hidden');
            this.message.addClass('hidden');
            this.clearText.addClass('hidden');
            this.prettyMessage.addClass('hidden');
            this.loadingIndicator.addClass('hidden');
        },

        /**
         * when "burn after reading" is checked, disable discussion
         *
         * @name   controller.changeBurnAfterReading
         * @function
         */
        changeBurnAfterReading: function()
        {
            if (this.burnAfterReading.is(':checked') )
            {
                this.openDisc.addClass('buttondisabled');
                this.openDiscussion.attr({checked: false, disabled: true});
            }
            else
            {
                this.openDisc.removeClass('buttondisabled');
                this.openDiscussion.removeAttr('disabled');
            }
        },

        /**
         * when discussion is checked, disable "burn after reading"
         *
         * @name   controller.changeOpenDisc
         * @function
         */
        changeOpenDisc: function()
        {
            if (this.openDiscussion.is(':checked') )
            {
                this.burnAfterReadingOption.addClass('buttondisabled');
                this.burnAfterReading.attr({checked: false, disabled: true});
            }
            else
            {
                this.burnAfterReadingOption.removeClass('buttondisabled');
                this.burnAfterReading.removeAttr('disabled');
            }
        },

        /**
         * forward to URL shortener
         *
         * @name   controller.sendToShortener
         * @function
         * @param  {Event} event
         */
        sendToShortener: function(event)
        {
            event.preventDefault();
            window.location.href = this.shortenerUrl + encodeURIComponent(this.createdPasteUrl);
        },

        /**
         * reload the page
         *
         * This takes the user to the PrivateBin home page.
         *
         * @name   controller.reloadPage
         * @function
         * @param  {Event} event
         */
        reloadPage: function(event)
        {
            event.preventDefault();
            window.location.href = helper.scriptLocation();
        },

        /**
         * return raw text
         *
         * @name   controller.rawText
         * @function
         * @param  {Event} event
         */
        rawText: function(event)
        {
            event.preventDefault();
            var paste = $('#pasteFormatter').val() === 'markdown' ?
                this.prettyPrint.text() : this.clearText.text();
            history.pushState(
                null, document.title, helper.scriptLocation() + '?' +
                helper.pasteId() + '#' + helper.pageKey()
            );
            // we use text/html instead of text/plain to avoid a bug when
            // reloading the raw text view (it reverts to type text/html)
            var newDoc = document.open('text/html', 'replace');
            newDoc.write('<pre>' + helper.htmlEntities(paste) + '</pre>');
            newDoc.close();
        },

        /**
         * clone the current paste
         *
         * @name   controller.clonePaste
         * @function
         * @param  {Event} event
         */
        clonePaste: function(event)
        {
            event.preventDefault();
            this.stateNewPaste();

            // erase the id and the key in url
            history.replaceState(null, document.title, helper.scriptLocation());

            this.showStatus('');
            if (this.attachmentLink.attr('href'))
            {
                this.clonedFile.removeClass('hidden');
                this.fileWrap.addClass('hidden');
            }
            this.message.text(
                $('#pasteFormatter').val() === 'markdown' ?
                    this.prettyPrint.text() : this.clearText.text()
            );
            $('.navbar-toggle').click();
        },

        /**
         * set the expiration on bootstrap templates
         *
         * @name   controller.setExpiration
         * @function
         * @param  {Event} event
         */
        setExpiration: function(event)
        {
            event.preventDefault();
            var target = $(event.target);
            $('#pasteExpiration').val(target.data('expiration'));
            $('#pasteExpirationDisplay').text(target.text());
        },

        /**
         * set the format on bootstrap templates
         *
         * @name   controller.setFormat
         * @function
         * @param  {Event} event
         */
        setFormat: function(event)
        {
            event.preventDefault();
            var target = $(event.target);
            $('#pasteFormatter').val(target.data('format'));
            $('#pasteFormatterDisplay').text(target.text());

            if (this.messagePreview.parent().hasClass('active')) {
                this.viewPreview(event);
            }
        },

        /**
         * set the language in a cookie and reload the page
         *
         * @name   controller.setLanguage
         * @function
         * @param  {Event} event
         */
        setLanguage: function(event)
        {
            document.cookie = 'lang=' + $(event.target).data('lang');
            this.reloadPage(event);
        },

        /**
         * support input of tab character
         *
         * @name   controller.supportTabs
         * @function
         * @param  {Event} event
         */
        supportTabs: function(event)
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
        },

        /**
         * view the editor tab
         *
         * @name   controller.viewEditor
         * @function
         * @param  {Event} event
         */
        viewEditor: function(event)
        {
            event.preventDefault();
            this.messagePreview.parent().removeClass('active');
            this.messageEdit.parent().addClass('active');
            this.message.focus();
            this.stateNewPaste();
        },

        /**
         * view the preview tab
         *
         * @name   controller.viewPreview
         * @function
         * @param  {Event} event
         */
        viewPreview: function(event)
        {
            event.preventDefault();
            this.messageEdit.parent().removeClass('active');
            this.messagePreview.parent().addClass('active');
            this.message.focus();
            this.stateExistingPaste(true);
            this.formatPaste($('#pasteFormatter').val(), this.message.val());
        },

        /**
         * handle history (pop) state changes
         *
         * currently this does only handle redirects to the home page.
         *
         * @name   controller.historyChange
         * @function
         * @param  {Event} event
         */
        historyChange: function(event)
        {
            var currentLocation = helper.scriptLocation();
            if (event.originalEvent.state === null && // no state object passed
                event.originalEvent.target.location.href === currentLocation && // target location is home page
                window.location.href === currentLocation // and we are not already on the home page
            ) {
                // redirect to home page
                window.location.href = currentLocation;
            }
        },

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
        pasteLinkClick: function(event)
        {
            // check if location is (already) shown in URL bar
            if (window.location.href === this.pasteUrl.attr('href')) {
                // if so we need to load link by reloading the current site
                window.location.reload(true);
            }
        },

        /**
         * create a new paste
         *
         * @name   controller.newPaste
         * @function
         */
        newPaste: function()
        {
            this.stateNewPaste();
            this.showStatus('');
            this.message.text('');
            this.changeBurnAfterReading();
            this.changeOpenDisc();
        },

        /**
         * removes an attachment
         *
         * @name   controller.removeAttachment
         * @function
         */
        removeAttachment: function()
        {
            this.clonedFile.addClass('hidden');
            // removes the saved decrypted file data
            this.attachmentLink.attr('href', '');
            // the only way to deselect the file is to recreate the input
            this.fileWrap.html(this.fileWrap.html());
            this.fileWrap.removeClass('hidden');
        },

        /**
         * decrypt using the password from the modal dialog
         *
         * @name   controller.decryptPasswordModal
         * @function
         */
        decryptPasswordModal: function()
        {
            this.passwordInput.val(this.passwordDecrypt.val());
            this.displayMessages();
        },

        /**
         * submit a password in the modal dialog
         *
         * @name   controller.submitPasswordModal
         * @function
         * @param  {Event} event
         */
        submitPasswordModal: function(event)
        {
            event.preventDefault();
            this.passwordModal.modal('hide');
        },

        /**
         * display an error message,
         * we use the same function for paste and reply to comments
         *
         * @name   controller.showError
         * @function
         * @param  {string} message - text to display
         */
        showError: function(message)
        {
            if (this.status.length)
            {
                this.status.addClass('errorMessage').text(message);
            }
            else
            {
                this.errorMessage.removeClass('hidden');
                helper.setMessage(this.errorMessage, message);
            }
            if (typeof this.replyStatus !== 'undefined') {
                this.replyStatus.addClass('errorMessage');
                this.replyStatus.addClass(this.errorMessage.attr('class'));
                if (this.status.length)
                {
                    this.replyStatus.html(this.status.html());
                }
                else
                {
                    this.replyStatus.html(this.errorMessage.html());
                }
            }
        },

        /**
         * display a status message,
         * we use the same function for paste and reply to comments
         *
         * @name   controller.showStatus
         * @function
         * @param  {string} message - text to display
         * @param  {boolean} [spin=false] - (optional) tell if the "spinning" animation should be displayed, defaults to false
         */
        showStatus: function(message, spin)
        {
            if (spin || false)
            {
                var img = '<img src="img/busy.gif" style="width:16px;height:9px;margin:0 4px 0 0;" />';
                this.status.prepend(img);
                if (typeof this.replyStatus !== 'undefined') {
                    this.replyStatus.prepend(img);
                }
            }
            if (typeof this.replyStatus !== 'undefined') {
                this.replyStatus.removeClass('errorMessage').text(message);
            }
            if (!message)
            {
                this.status.html(' ');
                return;
            }
            if (message === '')
            {
                this.status.html(' ');
                return;
            }
            this.status.removeClass('errorMessage').text(message);
        },

        /**
         * bind events to DOM elements
         *
         * @name   controller.bindEvents
         * @function
         */
        bindEvents: function()
        {
            this.burnAfterReading.change($.proxy(this.changeBurnAfterReading, this));
            this.openDisc.change($.proxy(this.changeOpenDisc, this));
            this.sendButton.click($.proxy(this.sendData, this));
            this.cloneButton.click($.proxy(this.clonePaste, this));
            this.rawTextButton.click($.proxy(this.rawText, this));
            this.fileRemoveButton.click($.proxy(this.removeAttachment, this));
            $('.reloadlink').click($.proxy(this.reloadPage, this));
            this.message.keydown(this.supportTabs);
            this.messageEdit.click($.proxy(this.viewEditor, this));
            this.messagePreview.click($.proxy(this.viewPreview, this));

            // bootstrap template drop downs
            $('ul.dropdown-menu li a', $('#expiration').parent()).click($.proxy(this.setExpiration, this));
            $('ul.dropdown-menu li a', $('#formatter').parent()).click($.proxy(this.setFormat, this));
            $('#language ul.dropdown-menu li a').click($.proxy(this.setLanguage, this));

            // page template drop down
            $('#language select option').click($.proxy(this.setLanguage, this));

            // handle modal password request on decryption
            this.passwordModal.on('shown.bs.modal', $.proxy(this.passwordDecrypt.focus, this));
            this.passwordModal.on('hidden.bs.modal', $.proxy(this.decryptPasswordModal, this));
            this.passwordForm.submit($.proxy(this.submitPasswordModal, this));

            $(window).on('popstate', $.proxy(this.historyChange, this));
        },

        /**
         * main application
         *
         * @name   controller.init
         * @function
         */
        init: function()
        {
            // hide "no javascript" message
            $('#noscript').hide();

            // preload jQuery wrapped DOM elements and bind events
            this.attach = $('#attach');
            this.attachment = $('#attachment');
            this.attachmentLink = $('#attachment a');
            this.burnAfterReading = $('#burnafterreading');
            this.burnAfterReadingOption = $('#burnafterreadingoption');
            this.cipherData = $('#cipherdata');
            this.clearText = $('#cleartext');
            this.cloneButton = $('#clonebutton');
            this.clonedFile = $('#clonedfile');
            this.comments = $('#comments');
            this.discussion = $('#discussion');
            this.errorMessage = $('#errormessage');
            this.expiration = $('#expiration');
            this.fileRemoveButton = $('#fileremovebutton');
            this.fileWrap = $('#filewrap');
            this.formatter = $('#formatter');
            this.image = $('#image');
            this.loadingIndicator = $('#loadingindicator');
            this.message = $('#message');
            this.messageEdit = $('#messageedit');
            this.messagePreview = $('#messagepreview');
            this.newButton = $('#newbutton');
            this.openDisc = $('#opendisc');
            this.openDiscussion = $('#opendiscussion');
            this.password = $('#password');
            this.passwordInput = $('#passwordinput');
            this.passwordModal = $('#passwordmodal');
            this.passwordForm = $('#passwordform');
            this.passwordDecrypt = $('#passworddecrypt');
            this.pasteResult = $('#pasteresult');
            // this.pasteUrl is saved in sendDataContinue() if/after it is
            // actually created
            this.prettyMessage = $('#prettymessage');
            this.prettyPrint = $('#prettyprint');
            this.preview = $('#preview');
            this.rawTextButton = $('#rawtextbutton');
            this.remainingTime = $('#remainingtime');
            this.sendButton = $('#sendbutton');
            this.status = $('#status');
            this.bindEvents();

            // display status returned by php code, if any (eg. paste was properly deleted)
            if (this.status.text().length > 0)
            {
                this.showStatus(this.status.text());
                return;
            }

            // keep line height even if content empty
            this.status.html(' ');

            // display an existing paste
            if (this.cipherData.text().length > 1)
            {
                // missing decryption key in URL?
                if (window.location.hash.length === 0)
                {
                    this.showError(i18n._('Cannot decrypt paste: Decryption key missing in URL (Did you use a redirector or an URL shortener which strips part of the URL?)'));
                    return;
                }

                // show proper elements on screen
                this.stateExistingPaste();
                this.displayMessages();
            }
            // display error message from php code
            else if (this.errorMessage.text().length > 1)
            {
                this.showError(this.errorMessage.text());
            }
            // create a new paste
            else
            {
                this.newPaste();
            }
        }
    }

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
