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

        it('creates a notification after a successful document upload', function () {
            cleanup();
            document.body.innerHTML = '<a href="#" id="deletelink"><span></span></a><div id="pastelink"></div><div id="pastesuccess"></div>';
            PrivateBin.PasteStatus.init();
            const expected1 = 'https://example.com/long';
            const expected2 = 'https://example.com/short';
            PrivateBin.PasteStatus.createPasteNotification(expected1, expected2);

            assert.strictEqual(document.getElementById('pasteurl').href, expected1);
            assert.strictEqual(document.getElementById('deletelink').href, expected2);
        });

        jsc.property(
            'creates a notification after a successful document upload (jsc)',
            common.jscUrl(),
            common.jscUrl(false),
            function (url1, url2) {
                    // sometimes the generator returns incomplete objects, bail out
                    if (!url1 || !url1.address || !url2 || !url2.address) {
                        return true;
                    }
                    const expected1 = common.urlToString(url1).replace(/&(gt|lt)$/, '&$1a'),
                        expected2 = common.urlToString(url2).replace(/&(gt|lt)$/, '&$1a');
                    cleanup();
                    document.body.innerHTML = '<a href="#" id="deletelink"><span></span></a><div id="pastelink"></div><div id="pastesuccess"></div>';
                    PrivateBin.PasteStatus.init();
                    PrivateBin.PasteStatus.createPasteNotification(expected1, expected2);
                    const result2 = document.getElementById('deletelink').href;
                    return document.getElementById('pasteurl').href === expected1 && result2 === expected2;
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
                domain = domain.replace(/\P{Letter}|[\u{AA}-\u{BA}]/gu, '').toLowerCase();
                if (domain.length === 0) {
                    domain = 'a';
                }
                url.schema = schema;
                url.address.unshift('.');
                url.address = domain.split('').concat(url.address);
                const urlString = common.urlToString(url),
                    expected = urlString.substring((schema + '://' + domain).length),
                    clean = globalThis.cleanup();

                document.body.innerHTML = '<div><div id="pastelink"></div></div>';
                PrivateBin.PasteStatus.init();
                PrivateBin.PasteStatus.createPasteNotification('', '');
                PrivateBin.PasteStatus.extractUrl(urlString);

                const result = document.getElementById('pasteurl').href;
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
                            title: 'example title',
                            date: '2014-10-24 16:01:39',
                            ip: '127.0.0.1'
                        },
                        status: 'success',
                        message: longUrlString + ' added to database',
                        title: 'example title',
                        shorturl: shortUrlString,
                        statusCode: 200
                    },
                    clean = globalThis.cleanup();

                document.body.innerHTML = '<div><div id="pastelink"></div></div>';
                PrivateBin.PasteStatus.init();
                PrivateBin.PasteStatus.createPasteNotification('', '');
                PrivateBin.PasteStatus.extractUrl(JSON.stringify(yourlsResponse, undefined, 4));

                const result = document.getElementById('pasteurl').href;
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
                    clean = globalThis.cleanup();

                document.body.innerHTML = '<div><div id="pastelink"></div></div>';
                PrivateBin.PasteStatus.init();
                PrivateBin.PasteStatus.createPasteNotification('', '');
                PrivateBin.PasteStatus.extractUrl(yourlsResponse);

                const result = document.getElementById('pasteurl').href;
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
                const [_, shortUrlString] = urlStrings(schema, longUrl, shortUrl),
                    yourlsResponse = '<!DOCTYPE html>\n' +
                        '<html lang="en">\n' +
                        '\t<head>\n' +
                        '\t\t<meta charset="utf-8" />\n' +
                        '\t\t<meta name="robots" content="noindex" />\n' +
                        '\t\t<meta name="google" content="notranslate">\n' +
                        '\t\t<title>PrivateBin</title>\n' +
                        '\t</head>\n' +
                        '\t<body>\n' +
                        '\t\t<p>Your document is <a id="pasteurl" href="' + shortUrlString + '">' + shortUrlString + '</a> <span id="copyhint">(Hit <kbd>Ctrl</kbd>+<kbd>c</kbd> to copy)</span></p>\n' +
                        '\t</body>\n' +
                        '</html>',
                    clean = globalThis.cleanup();

                document.body.innerHTML = '<div><div id="pastelink"></div></div>';
                PrivateBin.PasteStatus.init();
                PrivateBin.PasteStatus.createPasteNotification('', '');
                PrivateBin.PasteStatus.extractUrl(yourlsResponse);

                const result = document.getElementById('pasteurl').href;
                clean();

                return result === shortUrlString;
            }
        );
    });

    describe('showRemainingTime', function () {
        this.timeout(30000);

        jsc.property(
            'shows burn after reading message or remaining time',
            'bool',
            'nat',
            common.jscUrl(),
            function (burnafterreading, remainingTime, url) {
                let clean = globalThis.cleanup('', {url: common.urlToString(url)}),
                    result;
                document.body.innerHTML = '<div id="remainingtime" class="hidden"></div>';
                PrivateBin.PasteStatus.init();
                PrivateBin.PasteStatus.showRemainingTime(PrivateBin.Helper.PasteFactory({
                    'adata': [null, null, null, burnafterreading],
                    'v': 2,
                    'meta': {
                        'time_to_live': remainingTime
                    }
                }));
                if (burnafterreading) {
                    result = document.getElementById('remainingtime').classList.contains('foryoureyesonly') &&
                            !document.getElementById('remainingtime').classList.contains('hidden');
                } else if (remainingTime) {
                    result =!document.getElementById('remainingtime').classList.contains('foryoureyesonly') &&
                            !document.getElementById('remainingtime').classList.contains('hidden');
                } else {
                    result = document.getElementById('remainingtime').classList.contains('hidden') &&
                            !document.getElementById('remainingtime').classList.contains('foryoureyesonly');
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
                document.body.innerHTML = (
                    '<div id="remainingtime"></div><div id="pastesuccess"></div>'
                );
                PrivateBin.PasteStatus.init();
                PrivateBin.PasteStatus.hideMessages();
                assert.ok(document.getElementById('remainingtime').classList.contains('hidden'));
                assert.ok(document.getElementById('pastesuccess').classList.contains('hidden'));
            }
        );
    });
});
