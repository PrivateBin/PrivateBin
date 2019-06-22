'use strict';
var common = require('../common');

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
            {tests: 1});
        });

        it('shows error, if no webcrypto is detected', function () {
            [true, false].map(
                function (secureProtocol) {
                    const clean = jsdom('', {
                        'url': (secureProtocol ? 'https' : 'http' ) + '://[::1]/'
                    });
                    $('body').html(
                        '<html><body><div id="errormessage" class="hidden"></div>'+
                        '<div id="oldnotice" class="hidden"></div></body></html>'
                    );
                    const crypto = window.crypto;
                    window.crypto = null;
                    $.PrivateBin.Alert.init();
                    assert(!$.PrivateBin.InitialCheck.init());
                    assert(secureProtocol === $('#errormessage').hasClass('hidden'));
                    assert(!$('#oldnotice').hasClass('hidden'));
                    window.crypto = crypto;
                    clean();
                }
            );
        });

        it('shows error, if HTTP only site is detected', function () {
            [true, false].map(
                function (secureProtocol) {
                    const clean = jsdom('', {
                        'url': (secureProtocol ? 'https' : 'http' ) + '://[::1]/'
                    });
                    $('body').html(
                        '<html><body><div id="httpnotice" class="hidden"></div></body></html>'
                    );
                    assert($.PrivateBin.InitialCheck.init());
                    assert(secureProtocol === $('#httpnotice').hasClass('hidden'));
                    clean();
                }
            );
        });
    });
});

