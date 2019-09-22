/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @see       {@link https://github.com/PrivateBin/PrivateBin}
 * @copyright 2012 Sébastien SAUVAGE ({@link http://sebsauvage.net})
 * @license   {@link https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License}
 * @version   1.3.1
 * @name      Legacy
 * @namespace
 */

/**
 * IMPORTANT NOTICE FOR DEVELOPERS:
 * The logic in this file is intended to run in legacy browsers. Avoid any use of:
 * - jQuery (doesn't work in older browsers)
 * - ES5 or newer in general
 * - const/let, use the traditional var declarations instead
 * - async/await or Promises, use traditional callbacks
 * - shorthand function notation "() => output", use the full "function() {return output;}" style
 * - IE doesn't support:
 *   - URL(), use the traditional window.location object
 *   - endsWith(), use indexof()
 * - yes, this logic needs to support IE 6, to at least display the error message
 */

'use strict';
(function() {
    /**
     * compatibility check
     *
     * @name   Check
     * @class
     */
    var Check = (function () {
        var me = {};

        /**
         * Status of the initial check, true means it passed
         *
         * @private
         * @prop   {bool}
         */
        var status = false;

        /**
         * Initialization check did run
         *
         * @private
         * @prop   {bool}
         */
        var init = false;

        /**
         * blacklist of UserAgents (parts) known to belong to a bot
         *
         * @private
         * @enum   {Array}
         * @readonly
         */
        var badBotUA = [
            'Bot',
            'bot'
        ];

        /**
         * whitelist of top level domains to consider a secure context,
         * regardless of protocol
         *
         * @private
         * @enum   {Array}
         * @readonly
         */
        var tld = [
            '.onion',
            '.i2p'
        ];

        /**
         * whitelist of hostnames to consider a secure context,
         * regardless of protocol
         *
         * @private
         * @enum   {Array}
         * @readonly
         */
        // whitelists of TLDs & local hostnames
        var hostname = [
            'localhost',
            '127.0.0.1',
            '[::1]'
        ];

        /**
         * check if the context is secure
         *
         * @private
         * @name   Check.isSecureContext
         * @function
         * @return {bool}
         */
        function isSecureContext()
        {
            // use .isSecureContext if available
            if (window.isSecureContext === true || window.isSecureContext === false) {
                return window.isSecureContext;
            }

            // HTTP is obviously insecure
            if (window.location.protocol !== 'http:') {
                return true;
            }

            // filter out actually secure connections over HTTP
            for (var i = 0; i < tld.length; i++) {
                if (
                    window.location.hostname.indexOf(
                        tld[i],
                        window.location.hostname.length - tld[i].length
                    ) !== -1
                ) {
                    return true;
                }
            }

            // whitelist localhost for development
            for (var j = 0; j < hostname.length; j++) {
                if (window.location.hostname === hostname[j]) {
                    return true;
                }
            }

            // totally INSECURE http protocol!
            return false;
        }

        /**
         * checks whether this is a bot we dislike
         *
         * @private
         * @name   Check.isBadBot
         * @function
         * @return {bool}
         */
        function isBadBot() {
            // check whether a bot user agent part can be found in the current
            // user agent
            for (var i = 0; i < badBotUA.length; i++) {
                if (navigator.userAgent.indexOf(badBotUA[i]) !== -1) {
                    return true;
                }
            }
            return false;
        }

        /**
         * checks whether this is an unsupported browser, via feature detection
         *
         * @private
         * @name   Check.isOldBrowser
         * @function
         * @return {bool}
         */
        function isOldBrowser() {
            // webcrypto support
            if (!(
                'crypto' in window &&
                'getRandomValues' in window.crypto &&
                'subtle' in window.crypto &&
                'encrypt' in window.crypto.subtle &&
                'decrypt' in window.crypto.subtle &&
                'Uint8Array' in window &&
                'Uint32Array' in window
            )) {
                return true;
            }

            // async & ES6 support
            try {
                eval('async () => {}');
            } catch (e) {
                if (e instanceof SyntaxError) {
                    return true;
                } else {
                    throw e; // throws CSP error
                }
            }

            return false;
        }

        /**
         * shows an error message
         *
         * @private
         * @name   Check.showError
         * @param  {string} message
         * @function
         */
        function showError(message)
        {
            var element = document.getElementById('errormessage');
            if (message.indexOf('<a') === -1) {
                element.appendChild(
                    document.createTextNode(message)
                );
            } else {
                element.innerHTML = message;
            }
            removeHiddenFromId('errormessage');
        }

        /**
         * removes "hidden" CSS class from element with given ID
         *
         * @private
         * @name   Check.removeHiddenFromId
         * @param  {string} id
         * @function
         */
        function removeHiddenFromId(id)
        {
            var element = document.getElementById(id);
            if (element) {
                element.className = element.className.replace(/\bhidden\b/g, '');
            }
        }

        /**
         * returns if the check has concluded
         *
         * @name   Check.getInit
         * @function
         * @return {bool}
         */
        me.getInit = function()
        {
            return init;
        };
        
        /**
         * returns the current status of the check
         *
         * @name   Check.getStatus
         * @function
         * @return {bool}
         */
        me.getStatus = function()
        {
            return status;
        };
        
        /**
         * init on application start, returns an all-clear signal
         *
         * @name   Check.init
         * @function
         */
        me.init = function()
        {
            // prevent bots from viewing a paste and potentially deleting data
            // when burn-after-reading is set
            if (isBadBot()) {
                showError('I love you too, bot…');
                init = true;
                return;
            }

            if (isOldBrowser()) {
                // some browsers (Chrome based ones) would have webcrypto support if using HTTPS
                if (!isSecureContext()) {
                    removeHiddenFromId('insecurecontextnotice');
                }
                removeHiddenFromId('oldnotice');
                init = true;
                return;
            }

            if (!isSecureContext()) {
                removeHiddenFromId('httpnotice');
            }
            init = true;

            // only if everything passed, we set the status to true
            status = true;
        };

        return me;
    })();

    // main application start, called when DOM is fully loaded
    if (document.readyState === 'complete' || (!document.attachEvent && document.readyState === 'interactive')) {
        Check.init();
    } else {
        if (document.addEventListener) {
            // first choice is DOMContentLoaded event
            document.addEventListener('DOMContentLoaded', Check.init, false);
            // backup is window load event
            window.addEventListener('load', Check.init, false);
        } else {
            // must be IE
            document.attachEvent('onreadystatechange', Check.init);
            window.attachEvent('onload', Check.init);
        }
    }

    this.Legacy = {
        Check: Check
    };
}).call(this);