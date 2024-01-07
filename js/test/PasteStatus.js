'use strict';
var common = require('../common');

describe('PasteStatus', function () {
    describe('createPasteNotification', function () {
        this.timeout(30000);

        jsc.property(
            'creates a notification after a successfull paste upload',
            common.jscUrl(),
            common.jscUrl(false),
            function (url1, url2) {
                const expected1 = common.urlToString(url1),
                    expected2 = common.urlToString(url2),
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

    describe('extractUrl', function () {
        this.timeout(30000);

        jsc.property(
            'extracts and updates URLs found in given response',
            jsc.elements(['http','https']),
            'nestring',
            common.jscUrl(),
            function (schema, domain, url) {
                domain = domain.replace(/\P{Letter}|[\u00AA-\u00BA]/gu,'').toLowerCase();
                if (domain.length === 0) {
                    domain = 'a';
                }
                url.schema = schema;
                url.address.unshift('.');
                url.address = domain.split('').concat(url.address);
                const urlString = common.urlToString(url),
                    expected = urlString.substring((schema + '://' + domain).length),
                    clean = jsdom();

                $('body').html('<div><div id="pastelink"></div></div>');
                $.PrivateBin.PasteStatus.init();
                $.PrivateBin.PasteStatus.createPasteNotification('', '');
                $.PrivateBin.PasteStatus.extractUrl(urlString);

                const result = $('#pasteurl')[0].href;
                clean();

                return result.endsWith(expected) && (
                    result.startsWith(schema + '://xn--') ||
                    result.startsWith(schema + '://' + domain)
                );
            }
        );
    });

    describe('showRemainingTime', function () {
        this.timeout(30000);

        jsc.property(
            'shows burn after reading message or remaining time v1',
            'bool',
            'nat',
            common.jscUrl(),
            function (burnafterreading, remainingTime, url) {
                let clean = jsdom('', {url: common.urlToString(url)}),
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
            common.jscUrl(),
            function (burnafterreading, remainingTime, url) {
                let clean = jsdom('', {url: common.urlToString(url)}),
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

