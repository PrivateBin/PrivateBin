'use strict';
const common = require('../common');
const fc = require('fast-check');

describe('Helper', function () {
    describe('secondsToHuman', function () {
        it('returns an array with a number and a word', () => {
            fc.assert(fc.property(fc.integer(), function (number) {
                var result = PrivateBin.Helper.secondsToHuman(number);
                return Array.isArray(result) &&
                    result.length === 2 &&
                    result[0] === parseInt(result[0], 10) &&
                    typeof result[1] === 'string';
            }));
        });
        it('returns seconds on the first array position', () => {
            fc.assert(fc.property(fc.integer({max: 59}), function (number) {
                return PrivateBin.Helper.secondsToHuman(number)[0] === number;
            }));
        });
        it('returns seconds on the second array position', () => {
            fc.assert(fc.property(fc.integer({max: 59}), function (number) {
                return PrivateBin.Helper.secondsToHuman(number)[1] === 'second';
            }));
        });
        it('returns minutes on the first array position', () => {
            fc.assert(fc.property(fc.integer({min: 60, max: 3599}), function (number) {
                return PrivateBin.Helper.secondsToHuman(number)[0] === Math.floor(number / 60);
            }));
        });
        it('returns minutes on the second array position', () => {
            fc.assert(fc.property(fc.integer({min: 60, max: 3599}), function (number) {
                return PrivateBin.Helper.secondsToHuman(number)[1] === 'minute';
            }));
        });
        it('returns hours on the first array position', () => {
            fc.assert(fc.property(fc.integer({min: 3600, max: 86399}), function (number) {
                return PrivateBin.Helper.secondsToHuman(number)[0] === Math.floor(number / (60 * 60));
            }));
        });
        it('returns hours on the second array position', () => {
            fc.assert(fc.property(fc.integer({min: 3600, max: 86399}), function (number) {
                return PrivateBin.Helper.secondsToHuman(number)[1] === 'hour';
            }));
        });
        it('returns days on the first array position', () => {
            fc.assert(fc.property(fc.integer({min: 86400, max: 5184000}), function (number) {
                return PrivateBin.Helper.secondsToHuman(number)[0] === Math.floor(number / (60 * 60 * 24));
            }));
        });
        it('returns days on the second array position', () => {
            fc.assert(fc.property(fc.integer({min: 86400, max: 5184000}), function (number) {
                return PrivateBin.Helper.secondsToHuman(number)[1] === 'day';
            }));
        });
        // max safe integer as per http://ecma262-5.com/ELS5_HTML.htm#Section_8.5
        it('returns months on the first array position', () => {
            fc.assert(fc.property(fc.integer({min: 5184001}), function (number) {
                return PrivateBin.Helper.secondsToHuman(number)[0] === Math.floor(number / (60 * 60 * 24 * 30));
            }));
        });
        it('returns months on the second array position', () => {
            fc.assert(fc.property(fc.integer({min: 5184001}), function (number) {
                return PrivateBin.Helper.secondsToHuman(number)[1] === 'month';
            }));
        });
    });

    // this test is not yet meaningful using jsdom, as it does not contain getSelection support.
    // TODO: This needs to be tested using a browser.
    describe('selectText', function () {
        this.timeout(30000);
        it('selection contains content of given ID', () => {
            fc.assert(fc.property(
                fc.array(fc.array(common.fcAlnumString(), {minLength: 1}), {minLength: 1}),
                fc.array(fc.string(), {minLength: 1}),
                function (ids, contents) {
                    var html = '',
                        result = true,
                        clean = globalThis.cleanup(html);
                    ids.forEach(function(item, i) {
                        html += '<div id="' + item.join('') + '">' + PrivateBin.Helper.htmlEntities(contents[i] || contents[0]) + '</div>';
                    });
                    // TODO: As per https://github.com/tmpvar/jsdom/issues/321 there is no getSelection in jsdom, yet.
                    // Once there is one, uncomment the block below to actually check the result.
                    /*
                    ids.forEach(function(item, i) {
                        PrivateBin.Helper.selectText(item.join(''));
                        result *= (contents[i] || contents[0]) === window.getSelection().toString();
                    });
                    */
                    clean();
                    return Boolean(result);
                }
            ));
        });
    });

    describe('urls2links', function () {
        function getTextAsRenderedHtml(stringContent) {
            const tempDiv = document.createElement('div');
            tempDiv.textContent = stringContent;
            return tempDiv.innerHTML;
        }

        this.timeout(30000);
        before(function () {
            globalThis.cleanup();
        });

        it('ignores non-URL content', () => {
            fc.assert(fc.property(
                fc.string(),
                function (content) {
                    // eslint-disable-next-line no-control-regex
                    content = content.replace(/\r|\f/g, '\n').replace(/\u0000|\u000b/g, '');
                    let clean = globalThis.cleanup();
                    document.body.innerHTML = '<div id="foo"></div>';
                    let e = document.getElementById('foo');
                    e.textContent = content;
                    PrivateBin.Helper.urls2links(e);
                    let result = e.textContent;
                    clean();
                    return content === result;
                }
            ));
        });
        it('replaces URLs with anchors', () => {
            fc.assert(fc.property(
                fc.string(),
                common.fcUrl(),
                fc.array(common.fcHashString()),
                fc.string(),
                function (prefix, url, fragment, postfix) {
                    // eslint-disable-next-line no-control-regex
                    prefix = prefix.replace(/\r|\f/g, '\n').replace(/\u0000|\u000b/g, '');
                    // eslint-disable-next-line no-control-regex
                    postfix  = ' ' + postfix.replace(/\r/g, '\n').replace(/\u0000/g, '');
                    url.fragment = fragment.join('');
                    let urlString = common.urlToString(url),
                        clean = globalThis.cleanup();
                    document.body.innerHTML = '<div id="foo"></div>';
                    let e = document.getElementById('foo');

                    // special cases: When the query string and fragment imply the beginning of an HTML entity, eg. &#0 or &#x
                    if (
                        url.query[-1] === '&' &&
                        (parseInt(url.fragment.charAt(0), 10) >= 0 || url.fragment.charAt(0) === 'x')
                    ) {
                        url.query.pop();
                        urlString = common.urlToString(url);
                        postfix = '';
                    }
                    e.textContent = prefix + urlString + postfix;
                    PrivateBin.Helper.urls2links(e);

                    let result = e.innerHTML;
                    clean();

                    urlString = getTextAsRenderedHtml(urlString);
                    const expected = getTextAsRenderedHtml(prefix) + '<a href="' + urlString + '" target="_blank" rel="nofollow noopener noreferrer">' + urlString + '</a>' + getTextAsRenderedHtml(postfix);
                    const expectedElement = document.createElement('div');
                    expectedElement.innerHTML = expected;
                    return expectedElement.innerHTML === result;
                }
            ));
        });
        it('replaces magnet links with anchors', () => {
            fc.assert(fc.property(
                fc.string(),
                fc.array(common.fcQueryString()),
                fc.string(),
                function (prefix, query, postfix) {
                    // eslint-disable-next-line no-control-regex
                    prefix = prefix.replace(/\r|\f/g, '\n').replace(/\u0000|\u000b/g, '');
                    // eslint-disable-next-line no-control-regex
                    postfix = ' ' + postfix.replace(/\r/g, '\n').replace(/\u0000/g, '');
                    let url  = 'magnet:?' + query.join('').replace(/^&+|&+$/gm, ''),
                        clean = globalThis.cleanup();
                    document.body.innerHTML = '<div id="foo"></div>';
                    let e = document.getElementById('foo');
                    e.textContent = prefix + url + postfix;
                    PrivateBin.Helper.urls2links(e);
                    let result = e.innerHTML;
                    clean();
                    url = getTextAsRenderedHtml(url);
                    const expected = getTextAsRenderedHtml(prefix) + '<a href="' + url + '" target="_blank" rel="nofollow noopener noreferrer">' + url + '</a>' + getTextAsRenderedHtml(postfix);
                    const expectedElement = document.createElement('div');
                    expectedElement.innerHTML = expected;
                    return expectedElement.innerHTML === result;
                }
            ));
        });
    });

    describe('sprintf', function () {
        it('replaces %s in strings with first given parameter', () => {
            fc.assert(fc.property(
                fc.string(),
                fc.array(fc.string(), {minLength: 1}),
                fc.string(),
                function (prefix, params, postfix) {
                    prefix    =    prefix.replace(/%(s|d)/g, '%%');
                    params[0] = params[0].replace(/%(s|d)/g, '%%');
                    postfix   =   postfix.replace(/%(s|d)/g, '%%');
                    var result = prefix + params[0] + postfix;
                    params.unshift(prefix + '%s' + postfix);
                    return result === PrivateBin.Helper.sprintf.apply(this, params);
                }
            ));
        });
        it('replaces %d in strings with first given parameter', () => {
            fc.assert(fc.property(
                fc.string(),
                fc.array(fc.nat(), {minLength: 1}),
                fc.string(),
                function (prefix, params, postfix) {
                    prefix  =  prefix.replace(/%(s|d)/g, '%%');
                    postfix = postfix.replace(/%(s|d)/g, '%%');
                    var result = prefix + params[0] + postfix;
                    params.unshift(prefix + '%d' + postfix);
                    return result === PrivateBin.Helper.sprintf.apply(this, params);
                }
            ));
        });
        it('replaces %d in strings with 0 if first parameter is not a number', () => {
            fc.assert(fc.property(
                fc.string(),
                fc.array(fc.falsy(), {minLength: 1}),
                fc.string(),
                function (prefix, params, postfix) {
                    prefix  =  prefix.replace(/%(s|d)/g, '%%');
                    postfix = postfix.replace(/%(s|d)/g, '%%');
                    var result = prefix + '0' + postfix;
                    params.unshift(prefix + '%d' + postfix);
                    return result === PrivateBin.Helper.sprintf.apply(this, params);
                }
            ));
        });
        it('replaces %d and %s in strings in order', () => {
            fc.assert(fc.property(
                fc.string(),
                fc.nat(),
                fc.string(),
                fc.string(),
                fc.string(),
                function (prefix, uint, middle, string, postfix) {
                    prefix  =  prefix.replace(/%(s|d)/g, '');
                    middle  =  middle.replace(/%(s|d)/g, '');
                    postfix = postfix.replace(/%(s|d)/g, '');
                    var params = [prefix + '%d' + middle + '%s' + postfix, uint, string],
                        result = prefix + uint + middle + string + postfix;
                    return result === PrivateBin.Helper.sprintf.apply(this, params);
                }
            ));
        });
        it('replaces %d and %s in strings in reverse order', () => {
            fc.assert(fc.property(
                fc.string(),
                fc.nat(),
                fc.string(),
                fc.string(),
                fc.string(),
                function (prefix, uint, middle, string, postfix) {
                    prefix  =  prefix.replace(/%(s|d)/g, '');
                    middle  =  middle.replace(/%(s|d)/g, '');
                    postfix = postfix.replace(/%(s|d)/g, '');
                    var params = [prefix + '%s' + middle + '%d' + postfix, string, uint],
                        result = prefix + string + middle + uint + postfix;
                    return result === PrivateBin.Helper.sprintf.apply(this, params);
                }
            ));
        });
    });

    describe('getCookie', function () {
        this.timeout(30000);
        before(function () {
            globalThis.cleanup();
        });

/* TODO test fails since jsDOM version 17 - document.cookie remains empty
        it('returns the requested cookie', () => {
            fc.assert(fc.property(
                fc.array(fc.array(common.fcAlnumString(), {minLength: 1}), {minLength: 1}),
                fc.array(fc.array(common.fcAlnumString(), {minLength: 1}), {minLength: 1}),
                function (labels, values) {
                    let selectedKey = '', selectedValue = '';
                    const clean = globalThis.cleanup();
                    labels.forEach(function(item, i) {
                        const key = item.join(''),
                            value = (values[i] || values[0]).join('');
                        document.cookie = key + '=' + value;
                        if (Math.random() < 1 / i || selectedKey === key)
                        {
                            selectedKey = key;
                            selectedValue = value;
                        }
                    });
                    const result = PrivateBin.Helper.getCookie(selectedKey);
                    PrivateBin.Helper.reset();
                    clean();
                    return result === selectedValue;
                }
            ));
        }); */
    });

    describe('baseUri', function () {
        this.timeout(30000);
        it('returns the URL without query & fragment', () => {
            fc.assert(fc.property(
                common.fcSchemas(false),
                common.fcUrl(),
                function (schema, url) {
                    url.schema = schema;
                    const fullUrl = common.urlToString(url);
                    delete(url.query);
                    delete(url.fragment);
                    PrivateBin.Helper.reset();
                    const expected = common.urlToString(url),
                        clean = globalThis.cleanup('', {url: fullUrl}),
                        result = PrivateBin.Helper.baseUri();
                    clean();
                    return expected === result;
                }
            ));
        });
    });

    describe('htmlEntities', function () {
        before(function () {
            globalThis.cleanup();
        });

        it('removes all HTML entities from any given string', () => {
            fc.assert(fc.property(
                fc.string(),
                function (string) {
                    var result = PrivateBin.Helper.htmlEntities(string);
                    return !(/[<>]/.test(result)) && !(string.indexOf('&') > -1 && !(/&amp;/.test(result)));
                }
            ));
        });
    });

    describe('formatBytes', function () {
        it('returns 0 B for 0 bytes', function () {
            return PrivateBin.Helper.formatBytes(0) === '0 B';
        });

        it('formats bytes < 1000 as B', function () {
            return PrivateBin.Helper.formatBytes(500) === '500 B';
        });

        it('formats kilobytes correctly', function () {
            return PrivateBin.Helper.formatBytes(1500) === '1.5 kB';
        });

        it('formats megabytes correctly', function () {
            return PrivateBin.Helper.formatBytes(2 * 1000 * 1000) === '2 MB';
        });

        it('formats gigabytes correctly', function () {
            return PrivateBin.Helper.formatBytes(3.45 * 1000 * 1000 * 1000) === '3.45 GB';
        });

        it('formats terabytes correctly', function () {
            return PrivateBin.Helper.formatBytes(1.75 * 1000 ** 4) === '1.75 TB';
        });

        it('formats petabytes correctly', function () {
            return PrivateBin.Helper.formatBytes(1.5 * 1000 ** 5) === '1.5 PB';
        });

        it('formats exabytes correctly', function () {
            return PrivateBin.Helper.formatBytes(1.2345 * 1000 ** 6).startsWith('1.23 EB');
        });

        it('formats yottabytes correctly', function () {
            return PrivateBin.Helper.formatBytes(1.23 * 1000 ** 8).startsWith('1.23 YB');
        });

        it('rounds to two decimal places', function () {
            return PrivateBin.Helper.formatBytes(1234567) === '1.23 MB';
        });
    });


    describe('isBootstrap5', function () {
        it('Bootstrap 5 has been detected', function () {
            global.bootstrap = {};
            return PrivateBin.Helper.isBootstrap5() === true;
        });

        it('Bootstrap 5 has not been detected', function () {
            delete global.bootstrap;
            return PrivateBin.Helper.isBootstrap5() === false;
        });
    });
});
