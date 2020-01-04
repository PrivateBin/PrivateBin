'use strict';
var common = require('../common');
/* global Legacy, WebCrypto */

describe('Check', function () {
    describe('init', function () {
        this.timeout(30000);

        it('returns false and shows error, if a bot UA is detected', function () {
            jsc.assert(jsc.forall(
                'string',
                jsc.elements(['Bot', 'bot']),
                'string',
                function (prefix, botBit, suffix) {
                    const clean = jsdom(
                        '<html><body><div id="errormessage" class="hidden"></div>' +
                        '</body></html>', {
                            'userAgent': prefix + botBit + suffix
                        }
                    );
                    Legacy.Check.init();
                    const result1 = Legacy.Check.getInit() && !Legacy.Check.getStatus(),
                          result2 = (document.getElementById('errormessage').className !== 'hidden');
                    clean();
                    return result1 && result2;
                }
            ),
            {tests: 10});
        });

        jsc.property(
            'shows error, if no webcrypto is detected',
            'bool',
            jsc.elements(['localhost', '127.0.0.1', '[::1]', '']),
            jsc.nearray(common.jscA2zString()),
            jsc.elements(['.onion', '.i2p', '']),
            function (secureProtocol, localhost, domain, tld) {
                const isDomain = localhost === '',
                      isSecureContext = secureProtocol || !isDomain || tld.length > 0,
                      clean = jsdom(
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
                return result1 && result2 && result3;
            }
        );

        jsc.property(
            'shows error, if HTTP only site is detected',
            'bool',
            jsc.nearray(common.jscA2zString()),
            function (secureProtocol, domain) {
                const clean = jsdom(
                          '<html><body><div id="httpnotice" class="hidden"></div>' +
                          '</body></html>',
                          {
                              'url': (secureProtocol ? 'https' : 'http' ) + '://' + domain.join('') + '/'
                          }
                      );
                window.crypto = new WebCrypto();
                Legacy.Check.init();
                const result1 = Legacy.Check.getInit() && Legacy.Check.getStatus(),
                      result2 = secureProtocol === (document.getElementById('httpnotice').className === 'hidden');
                clean();
                return result1 && result2;
            }
        );
    });
});

