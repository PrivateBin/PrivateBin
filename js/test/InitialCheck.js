'use strict';
var common = require('../common');

describe('InitialCheck', function () {
    describe('init', function () {
        this.timeout(30000);
        before(function () {
            cleanup();
        });

        jsc.property(
            'returns false and shows error, if a bot UA is detected',
            'string',
            jsc.elements(['Bot', 'bot']),
            'string',
            function (
                prefix, botBit, suffix
            ) {
                const clean = jsdom(
                    '<html><body><div id="errormessage" class="hidden"></div></body></html>',
                    {'userAgent': prefix + botBit + suffix}
                );
                var result1 = $.PrivateBin.InitialCheck.init(),
                    result2 = !$('#errormessage').hasClass('hidden');
                clean();
                return result1 && result2;
            }
        );
    });
});

