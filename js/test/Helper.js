'use strict';
var common = require('../common');

describe('Helper', function () {
    describe('secondsToHuman', function () {
        after(function () {
            cleanup();
        });

        jsc.property('returns an array with a number and a word', 'integer', function (number) {
            var result = $.PrivateBin.Helper.secondsToHuman(number);
            return Array.isArray(result) &&
                result.length === 2 &&
                result[0] === parseInt(result[0], 10) &&
                typeof result[1] === 'string';
        });
        jsc.property('returns seconds on the first array position', 'integer 59', function (number) {
            return $.PrivateBin.Helper.secondsToHuman(number)[0] === number;
        });
        jsc.property('returns seconds on the second array position', 'integer 59', function (number) {
            return $.PrivateBin.Helper.secondsToHuman(number)[1] === 'second';
        });
        jsc.property('returns minutes on the first array position', 'integer 60 3599', function (number) {
            return $.PrivateBin.Helper.secondsToHuman(number)[0] === Math.floor(number / 60);
        });
        jsc.property('returns minutes on the second array position', 'integer 60 3599', function (number) {
            return $.PrivateBin.Helper.secondsToHuman(number)[1] === 'minute';
        });
        jsc.property('returns hours on the first array position', 'integer 3600 86399', function (number) {
            return $.PrivateBin.Helper.secondsToHuman(number)[0] === Math.floor(number / (60 * 60));
        });
        jsc.property('returns hours on the second array position', 'integer 3600 86399', function (number) {
            return $.PrivateBin.Helper.secondsToHuman(number)[1] === 'hour';
        });
        jsc.property('returns days on the first array position', 'integer 86400 5184000', function (number) {
            return $.PrivateBin.Helper.secondsToHuman(number)[0] === Math.floor(number / (60 * 60 * 24));
        });
        jsc.property('returns days on the second array position', 'integer 86400 5184000', function (number) {
            return $.PrivateBin.Helper.secondsToHuman(number)[1] === 'day';
        });
        // max safe integer as per http://ecma262-5.com/ELS5_HTML.htm#Section_8.5
        jsc.property('returns months on the first array position', 'integer 5184000 9007199254740991', function (number) {
            return $.PrivateBin.Helper.secondsToHuman(number)[0] === Math.floor(number / (60 * 60 * 24 * 30));
        });
        jsc.property('returns months on the second array position', 'integer 5184000 9007199254740991', function (number) {
            return $.PrivateBin.Helper.secondsToHuman(number)[1] === 'month';
        });
    });

    // this test is not yet meaningful using jsdom, as it does not contain getSelection support.
    // TODO: This needs to be tested using a browser.
    describe('selectText', function () {
        this.timeout(30000);
        jsc.property(
            'selection contains content of given ID',
            jsc.nearray(jsc.nearray(common.jscAlnumString())),
            'nearray string',
            function (ids, contents) {
                var html = '',
                    result = true;
                ids.forEach(function(item, i) {
                    html += '<div id="' + item.join('') + '">' + common.htmlEntities(contents[i] || contents[0]) + '</div>';
                });
                var clean = jsdom(html);
                // TODO: As per https://github.com/tmpvar/jsdom/issues/321 there is no getSelection in jsdom, yet.
                // Once there is one, uncomment the block below to actually check the result.
                /*
                ids.forEach(function(item, i) {
                    $.PrivateBin.Helper.selectText(item.join(''));
                    result *= (contents[i] || contents[0]) === window.getSelection().toString();
                });
                */
                clean();
                return Boolean(result);
            }
        );
    });

    describe('urls2links', function () {
        after(function () {
            cleanup();
        });

        jsc.property(
            'ignores non-URL content',
            'string',
            function (content) {
                return content === $.PrivateBin.Helper.urls2links(content);
            }
        );
        jsc.property(
            'replaces URLs with anchors',
            'string',
            jsc.elements(['http', 'https', 'ftp']),
            jsc.nearray(common.jscA2zString()),
            jsc.array(common.jscQueryString()),
            jsc.array(common.jscHashString()),
            'string',
            function (prefix, schema, address, query, fragment, postfix) {
                var query = query.join(''),
                    fragment = fragment.join(''),
                    url = schema + '://' + address.join('') + '/?' + query + '#' + fragment,
                    prefix = common.htmlEntities(prefix),
                    postfix = ' ' + common.htmlEntities(postfix);

                // special cases: When the query string and fragment imply the beginning of an HTML entity, eg. &#0 or &#x
                if (
                    query.slice(-1) === '&' &&
                    (parseInt(fragment.substring(0, 1), 10) >= 0 || fragment.charAt(0) === 'x' )
                )
                {
                    url = schema + '://' + address.join('') + '/?' + query.substring(0, query.length - 1);
                    postfix = '';
                }

                return prefix + '<a href="' + url + '" rel="nofollow">' + url + '</a>' + postfix === $.PrivateBin.Helper.urls2links(prefix + url + postfix);
            }
        );
        jsc.property(
            'replaces magnet links with anchors',
            'string',
            jsc.array(common.jscQueryString()),
            'string',
            function (prefix, query, postfix) {
                var url = 'magnet:?' + query.join('').replace(/^&+|&+$/gm,''),
                    prefix = common.htmlEntities(prefix),
                    postfix = common.htmlEntities(postfix);
                return prefix + '<a href="' + url + '" rel="nofollow">' + url + '</a> ' + postfix === $.PrivateBin.Helper.urls2links(prefix + url + ' ' + postfix);
            }
        );
    });

    describe('sprintf', function () {
        after(function () {
            cleanup();
        });

        jsc.property(
            'replaces %s in strings with first given parameter',
            'string',
            '(small nearray) string',
            'string',
            function (prefix, params, postfix) {
                prefix    =    prefix.replace(/%(s|d)/g, '%%');
                params[0] = params[0].replace(/%(s|d)/g, '%%');
                postfix   =   postfix.replace(/%(s|d)/g, '%%');
                var result = prefix + params[0] + postfix;
                params.unshift(prefix + '%s' + postfix);
                return result === $.PrivateBin.Helper.sprintf.apply(this, params);
            }
        );
        jsc.property(
            'replaces %d in strings with first given parameter',
            'string',
            '(small nearray) nat',
            'string',
            function (prefix, params, postfix) {
                prefix  =  prefix.replace(/%(s|d)/g, '%%');
                postfix = postfix.replace(/%(s|d)/g, '%%');
                var result = prefix + params[0] + postfix;
                params.unshift(prefix + '%d' + postfix);
                return result === $.PrivateBin.Helper.sprintf.apply(this, params);
            }
        );
        jsc.property(
            'replaces %d in strings with 0 if first parameter is not a number',
            'string',
            '(small nearray) falsy',
            'string',
            function (prefix, params, postfix) {
                prefix  =  prefix.replace(/%(s|d)/g, '%%');
                postfix = postfix.replace(/%(s|d)/g, '%%');
                var result = prefix + '0' + postfix;
                params.unshift(prefix + '%d' + postfix);
                return result === $.PrivateBin.Helper.sprintf.apply(this, params);
            }
        );
        jsc.property(
            'replaces %d and %s in strings in order',
            'string',
            'nat',
            'string',
            'string',
            'string',
            function (prefix, uint, middle, string, postfix) {
                prefix  =  prefix.replace(/%(s|d)/g, '%%');
                middle  =  middle.replace(/%(s|d)/g, '%%');
                postfix = postfix.replace(/%(s|d)/g, '%%');
                var params = [prefix + '%d' + middle + '%s' + postfix, uint, string],
                    result = prefix + uint + middle + string + postfix;
                return result === $.PrivateBin.Helper.sprintf.apply(this, params);
            }
        );
        jsc.property(
            'replaces %d and %s in strings in reverse order',
            'string',
            'nat',
            'string',
            'string',
            'string',
            function (prefix, uint, middle, string, postfix) {
                prefix  =  prefix.replace(/%(s|d)/g, '%%');
                middle  =  middle.replace(/%(s|d)/g, '%%');
                postfix = postfix.replace(/%(s|d)/g, '%%');
                var params = [prefix + '%s' + middle + '%d' + postfix, string, uint],
                    result = prefix + string + middle + uint + postfix;
                return result === $.PrivateBin.Helper.sprintf.apply(this, params);
            }
        );
    });

    describe('getCookie', function () {
        this.timeout(30000);
        jsc.property(
            'returns the requested cookie',
            'nearray asciinestring',
            'nearray asciistring',
            function (labels, values) {
                var selectedKey = '', selectedValue = '',
                    cookieArray = [];
                labels.forEach(function(item, i) {
                    // deliberatly using a non-ascii key for replacing invalid characters
                    var key = item.replace(/[\s;,=]/g, Array(i+2).join('£')),
                        value = (values[i] || values[0]).replace(/[\s;,=]/g, '');
                    cookieArray.push(key + '=' + value);
                    if (Math.random() < 1 / i || selectedKey === key)
                    {
                        selectedKey = key;
                        selectedValue = value;
                    }
                });
                var clean = jsdom('', {cookie: cookieArray}),
                    result = $.PrivateBin.Helper.getCookie(selectedKey);
                clean();
                return result === selectedValue;
            }
        );
    });

    describe('baseUri', function () {
        this.timeout(30000);
        before(function () {
            $.PrivateBin.Helper.reset();
        });

        jsc.property(
            'returns the URL without query & fragment',
            common.jscSchemas(),
            jsc.nearray(common.jscA2zString()),
            jsc.array(common.jscQueryString()),
            'string',
            function (schema, address, query, fragment) {
                var expected = schema + '://' + address.join('') + '/',
                    clean = jsdom('', {url: expected + '?' + query.join('') + '#' + fragment}),
                    result = $.PrivateBin.Helper.baseUri();
                $.PrivateBin.Helper.reset();
                clean();
                return expected === result;
            }
        );
    });

    describe('htmlEntities', function () {
        after(function () {
            cleanup();
        });

        jsc.property(
            'removes all HTML entities from any given string',
            'string',
            function (string) {
                var result = common.htmlEntities(string);
                return !(/[<>"'`=\/]/.test(result)) && !(string.indexOf('&') > -1 && !(/&amp;/.test(result)));
            }
        );
    });
});

