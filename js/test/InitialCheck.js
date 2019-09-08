'use strict';
var common = require('../common');
/* global WebCrypto */

describe('InitialCheck', function () {
    describe('init', function () {
        this.timeout(30000);
        before(function () {
            cleanup();
        });

        it('returns false and shows error, if a bot UA is detected', function () {
            jsc.assert(jsc.forall(
                'string',
                jsc.elements(['Bot', 'bot']),
                'string',
                function (prefix, botBit, suffix) {
                    const clean = jsdom('', {
                        'userAgent': prefix + botBit + suffix
                    });
                    $('body').html(
                        '<html><body><div id="errormessage" class="hidden"></div>' +
                        '</body></html>'
                    );
                    $.PrivateBin.Alert.init();
                    const result1 = !$.PrivateBin.InitialCheck.init(),
                          result2 = !$('#errormessage').hasClass('hidden');
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
                      clean = jsdom('', {
                          'url': (secureProtocol ? 'https' : 'http' ) + '://' +
                                 (isDomain ? domain.join('') + tld : localhost) + '/'
                      });
                $('body').html(
                    '<html><body><div id="errormessage" class="hidden"></div>'+
                    '<div id="oldnotice" class="hidden"></div></body></html>'
                );
                $.PrivateBin.Alert.init();
                const result1 = !$.PrivateBin.InitialCheck.init(),
                      result2 = isSecureContext === $('#errormessage').hasClass('hidden'),
                      result3 = !$('#oldnotice').hasClass('hidden');
                clean();
                return result1 && result2 && result3;
            }
        );

        jsc.property(
            'shows error, if HTTP only site is detected',
            'bool',
            jsc.nearray(common.jscA2zString()),
            function (secureProtocol, domain) {
                const clean = jsdom('', {
                          'url': (secureProtocol ? 'https' : 'http' ) + '://' + domain.join('') + '/'
                      });
                $('body').html(
                    '<html><body><div id="httpnotice" class="hidden"></div>'+
                    '</body></html>'
                );
                $.PrivateBin.Alert.init();
                window.crypto = new WebCrypto();
                const result1 = $.PrivateBin.InitialCheck.init(),
                      result2 = secureProtocol === $('#httpnotice').hasClass('hidden');
                clean();
                return result1 && result2;
            }
        );
    });
});

