'use strict';
const common = require('../common');
const fc = require('fast-check');
/* global Legacy, WebCrypto */

describe('Check', function () {
    describe('init', function () {
        this.timeout(30000);

        it('returns false and shows error, if a bot UA is detected', () => {
            fc.assert(fc.property(
                fc.string(),
                fc.constantFrom('Bot', 'bot'),
                fc.string(),
                function (prefix, botBit, suffix) {
                    const clean = globalThis.cleanup(
                        '<html><body><div id="errormessage" class="hidden"></div>' +
                        '</body></html>', {
                            'userAgent': prefix + botBit + suffix
                        }
                    );
                    Legacy.Check.init();
                    const result = Legacy.Check.getInit() && !Legacy.Check.getStatus();
                    clean();
                    return result;
                }
            ),
            {numRuns: 10});
        });

        it('shows error, if no webcrypto is detected', () => {
            fc.assert(fc.property(
                fc.boolean(),
                fc.constantFrom('localhost', '127.0.0.1', '[::1]', ''),
                fc.array(common.fcA2zString(), {minLength: 1}),
                fc.constantFrom('.onion', '.i2p', ''),
                function (secureProtocol, localhost, domain, tld) {
                    const isDomain = localhost === '',
                          isSecureContext = secureProtocol || !isDomain || tld.length > 0,
                          clean = globalThis.cleanup(
                              '<html><body><div id="errormessage" class="hidden"></div>' +
                              '<div id="oldnotice" class="hidden"></div>' +
                              '<div id="insecurecontextnotice" class="hidden"></div></body></html>',
                              {
                                  'url': (secureProtocol ? 'https' : 'http' ) + '://' +
                                         (isDomain ? domain.join('') + tld : localhost) + '/'
                              }
                          );
                    Legacy.Check.init();
                    const result1 = Legacy.Check.getInit() && !Legacy.Check.getStatus(),
                          result2 = isSecureContext === (document.getElementById('insecurecontextnotice').className === 'hidden'),
                          result3 = (document.getElementById('oldnotice').className !== 'hidden');
                    clean();
                    if (result1 && result2 && result3) {
                        return true;
                    }
                    console.log(result1, result2, result3);
                    return false;
                }
            ));
        });

        it('shows error, if HTTP only site is detected', () => {
            fc.assert(fc.property(
                fc.boolean(),
                fc.array(common.fcA2zString(), {minLength: 1}),
                function (secureProtocol, domain) {
                    const clean = globalThis.cleanup(
                              '<html><body><div id="httpnotice" class="hidden"></div>' +
                              '</body></html>',
                              {
                                  'url': (secureProtocol ? 'https' : 'http' ) + '://' + domain.join('') + '/'
                              }
                          );
                    Object.defineProperty(window, 'crypto', {
                        value: new WebCrypto(),
                        configurable: true,
                        enumerable: true,
                        writable: false
                    });
                    Legacy.Check.init();
                    const result1 = Legacy.Check.getInit() && Legacy.Check.getStatus(),
                          result2 = secureProtocol === (document.getElementById('httpnotice').className === 'hidden');
                    clean();
                    if (result1 && result2) {
                        return true;
                    }
                    console.log(result1, result2);
                    return false;
                }
            ));
        });
    });
});
