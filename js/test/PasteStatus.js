'use strict';
var common = require('../common');

function urlStrings(schema, longUrl, shortUrl) {
    longUrl.schema = schema;
    shortUrl.schema = schema;
    let longUrlString = common.urlToString(longUrl),
        shortUrlString = common.urlToString(shortUrl);
    // ensure the two random URLs actually are sorted as expected
    if (longUrlString.length <= shortUrlString.length) {
        if (longUrlString.length === shortUrlString.length) {
            longUrl.address.unshift('a');
            longUrlString = common.urlToString(longUrl);
        } else {
            [longUrlString, shortUrlString] = [shortUrlString, longUrlString];
        }
    }
    return [longUrlString, shortUrlString];
}

describe('PasteStatus', function () {
    describe('createPasteNotification', function () {
        this.timeout(30000);

        jsc.property(
            'creates a notification after a successfull paste upload',
            common.jscUrl(),
            common.jscUrl(false),
            function (url1, url2) {
                const expected1 = common.urlToString(url1).replace(/&(gt|lt)$/, '&$1a'),
                    expected2 = common.urlToString(url2).replace(/&(gt|lt)$/, '&$1a'),
                    clean = jsdom();
                $('body').html('<a href="#" id="deletelink"><span></span></a><div id="pastelink"></div>');
                $.PrivateBin.PasteStatus.init();
                $.PrivateBin.PasteStatus.createPasteNotification(expected1, expected2);
                const result1 = $('#pasteurl')[0].href,
                    result2 = $('#deletelink')[0].href;
                clean();
                return result1 === expected1 && result2 === expected2;
            }
        );
    });

    describe('extractUrl', function () {
        this.timeout(30000);

        jsc.property(
            'extracts and updates IDN URLs found in given response',
            common.jscSchemas(false),
            'nestring',
            common.jscUrl(),
            function (schema, domain, url) {
                domain = domain.replace(/\P{Letter}|[\u00AA-\u00BA]/gu, '').toLowerCase();
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

        // YOURLS API samples from: https://yourls.org/readme.html#API;apireturn
        jsc.property(
            'extracts and updates URLs found in YOURLS API JSON response',
            common.jscSchemas(false),
            common.jscUrl(),
            common.jscUrl(false),
            function (schema, longUrl, shortUrl) {
                const [longUrlString, shortUrlString] = urlStrings(schema, longUrl, shortUrl),
                    yourlsResponse = {
                        url: {
                            keyword: longUrl.address.join(''),
                            url: longUrlString,
                            title: "example title",
                            date: "2014-10-24 16:01:39",
                            ip: "127.0.0.1"
                        },
                        status: "success",
                        message: longUrlString + " added to database",
                        title: "example title",
                        shorturl: shortUrlString,
                        statusCode: 200
                    },
                    clean = jsdom();

                $('body').html('<div><div id="pastelink"></div></div>');
                $.PrivateBin.PasteStatus.init();
                $.PrivateBin.PasteStatus.createPasteNotification('', '');
                $.PrivateBin.PasteStatus.extractUrl(JSON.stringify(yourlsResponse, undefined, 4));

                const result = $('#pasteurl')[0].href;
                clean();

                return result === shortUrlString;
            }
        );
        jsc.property(
            'extracts and updates URLs found in YOURLS API XML response',
            common.jscSchemas(false),
            common.jscUrl(),
            common.jscUrl(false),
            function (schema, longUrl, shortUrl) {
                const [longUrlString, shortUrlString] = urlStrings(schema, longUrl, shortUrl),
                    yourlsResponse = '<result>\n' +
                        '    <keyword>' + longUrl.address.join('') + '</keyword>\n' +
                        '    <shorturl>' + shortUrlString + '</shorturl>\n' +
                        '    <longurl>' + longUrlString + '</longurl>\n' +
                        '    <message>success</message>\n' +
                        '    <statusCode>200</statusCode>\n' +
                        '</result>',
                    clean = jsdom();

                $('body').html('<div><div id="pastelink"></div></div>');
                $.PrivateBin.PasteStatus.init();
                $.PrivateBin.PasteStatus.createPasteNotification('', '');
                $.PrivateBin.PasteStatus.extractUrl(yourlsResponse);

                const result = $('#pasteurl')[0].href;
                clean();

                return result === shortUrlString;
            }
        );
        jsc.property(
            'extracts and updates URLs found in YOURLS proxy HTML response',
            common.jscSchemas(false),
            common.jscUrl(),
            common.jscUrl(false),
            function (schema, longUrl, shortUrl) {
                const [longUrlString, shortUrlString] = urlStrings(schema, longUrl, shortUrl),
                    yourlsResponse = '<!DOCTYPE html>\n' +
                        '<html lang="en">\n' +
                        '\t<head>\n' +
                        '\t\t<meta charset="utf-8" />\n' +
                        '\t\t<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; base-uri \'self\'; form-action \'none\'; manifest-src \'self\'; connect-src * blob:; script-src \'self\' \'unsafe-eval\'; style-src \'self\'; font-src \'self\'; frame-ancestors \'none\'; img-src \'self\' data: blob:; media-src blob:; object-src blob:; sandbox allow-same-origin allow-scripts allow-forms allow-popups allow-modals allow-downloads">\n' +
                        '\t\t<meta name="robots" content="noindex" />\n' +
                        '\t\t<meta name="google" content="notranslate">\n' +
                        '\t\t<title>PrivateBin</title>\n' +
                        '\t</head>\n' +
                        '\t<body>\n' +
                        '\t\t<p>Your paste is <a id="pasteurl" href="' + shortUrlString + '">' + shortUrlString + '</a> <span id="copyhint">(Hit <kbd>Ctrl</kbd>+<kbd>c</kbd> to copy)</span></p>\n' +
                        '\t</body>\n' +
                        '</html>',
                    clean = jsdom();

                $('body').html('<div><div id="pastelink"></div></div>');
                $.PrivateBin.PasteStatus.init();
                $.PrivateBin.PasteStatus.createPasteNotification('', '');
                $.PrivateBin.PasteStatus.extractUrl(yourlsResponse);

                const result = $('#pasteurl')[0].href;
                clean();

                return result === shortUrlString;
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
