'use strict';
var common = require('../common');

describe('PasteStatus', function () {
    describe('createPasteNotification', function () {
        this.timeout(30000);

        jsc.property(
            'creates a notification after a successfull paste upload',
            common.jscSchemas(),
            jsc.nearray(common.jscA2zString()),
            jsc.array(common.jscQueryString()),
            'string',
            common.jscSchemas(),
            jsc.nearray(common.jscA2zString()),
            jsc.array(common.jscQueryString()),
            function (
                schema1, address1, query1, fragment1,
                schema2, address2, query2
            ) {
                var expected1 = schema1 + '://' + address1.join('') + '/?' +
                    encodeURI(query1.join('').replace(/^&+|&+$/gm,'') + '#' + fragment1),
                    expected2 = schema2 + '://' + address2.join('') + '/?' +
                    encodeURI(query2.join('').replace(/^&+|&+$/gm,'')),
                    clean = jsdom();
                $('body').html('<div><div id="deletelink"></div><div id="pastelink"></div></div>');
                $.PrivateBin.PasteStatus.init();
                $.PrivateBin.PasteStatus.createPasteNotification(expected1, expected2);
                var result1 = $('#pasteurl')[0].href,
                    result2 = $('#deletelink a')[0].href;
                clean();
                return result1 === expected1 && result2 === expected2;
            }
        );
    });

    describe('showRemainingTime', function () {
        this.timeout(30000);

        jsc.property(
            'shows burn after reading message or remaining time v1',
            'bool',
            'nat',
            jsc.nearray(common.jscA2zString()),
            jsc.nearray(common.jscA2zString()),
            jsc.nearray(common.jscQueryString()),
            'string',
            function (
                burnafterreading, remainingTime,
                schema, address, query, fragment
            ) {
                var clean = jsdom('', {
                        url: schema.join('') + '://' + address.join('') +
                             '/?' + query.join('') + '#' + fragment
                    }),
                    result;
                $('body').html('<div id="remainingtime" class="hidden"></div>');
                $.PrivateBin.PasteStatus.init();
                $.PrivateBin.PasteStatus.showRemainingTime($.PrivateBin.Helper.PasteFactory({'meta': {
                    'burnafterreading': burnafterreading,
                    'remaining_time': remainingTime
                }}));
                if (burnafterreading) {
                    result = $('#remainingtime').hasClass('foryoureyesonly') &&
                            !$('#remainingtime').hasClass('hidden');
                } else if (remainingTime) {
                    result =!$('#remainingtime').hasClass('foryoureyesonly') &&
                            !$('#remainingtime').hasClass('hidden');
                } else {
                    result = $('#remainingtime').hasClass('hidden') &&
                            !$('#remainingtime').hasClass('foryoureyesonly');
                }
                clean();
                return result;
            }
        );

        jsc.property(
            'shows burn after reading message or remaining time v2',
            'bool',
            'nat',
            jsc.nearray(common.jscA2zString()),
            jsc.nearray(common.jscA2zString()),
            jsc.nearray(common.jscQueryString()),
            'string',
            function (
                burnafterreading, remainingTime,
                schema, address, query, fragment
            ) {
                var clean = jsdom('', {
                        url: schema.join('') + '://' + address.join('') +
                             '/?' + query.join('') + '#' + fragment
                    }),
                    result;
                $('body').html('<div id="remainingtime" class="hidden"></div>');
                $.PrivateBin.PasteStatus.init();
                $.PrivateBin.PasteStatus.showRemainingTime($.PrivateBin.Helper.PasteFactory({
                    'adata': [null, null, null, burnafterreading],
                    'v': 2,
                    'meta': {
                        'time_to_live': remainingTime
                    }
                }));
                if (burnafterreading) {
                    result = $('#remainingtime').hasClass('foryoureyesonly') &&
                            !$('#remainingtime').hasClass('hidden');
                } else if (remainingTime) {
                    result =!$('#remainingtime').hasClass('foryoureyesonly') &&
                            !$('#remainingtime').hasClass('hidden');
                } else {
                    result = $('#remainingtime').hasClass('hidden') &&
                            !$('#remainingtime').hasClass('foryoureyesonly');
                }
                clean();
                return result;
            }
        );
    });

    describe('hideMessages', function () {
        it(
            'hides all messages',
            function() {
                $('body').html(
                    '<div id="remainingtime"></div><div id="pastesuccess"></div>'
                );
                $.PrivateBin.PasteStatus.init();
                $.PrivateBin.PasteStatus.hideMessages();
                assert.ok(
                    $('#remainingtime').hasClass('hidden') &&
                    $('#pastesuccess').hasClass('hidden')
                );
            }
        );
    });
});

