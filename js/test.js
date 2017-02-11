'use strict';
var jsc = require('jsverify'),
    jsdom = require('jsdom-global'),
    cleanup = jsdom(),

    a2zString = ['a','b','c','d','e','f','g','h','i','j','k','l','m',
                 'n','o','p','q','r','s','t','u','v','w','x','y','z'],
    alnumString = a2zString.concat(['0','1','2','3','4','5','6','7','8','9']),
    queryString = alnumString.concat(['+','%','&','.','*','-','_']),
    base64String = alnumString.concat(['+','/','=']).concat(
        a2zString.map(function(c) {
            return c.toUpperCase();
        })
    );

global.$ = global.jQuery = require('./jquery-3.1.1');
global.sjcl = require('./sjcl-1.0.6');
global.Base64 = require('./base64-2.1.9');
global.RawDeflate = require('./rawdeflate-0.5');
require('./rawinflate-0.3');
require('./privatebin');

describe('helper', function () {
    describe('secondsToHuman', function () {
        after(function () {
            cleanup();
        });

        jsc.property('returns an array with a number and a word', 'integer', function (number) {
            var result = $.PrivateBin.helper.secondsToHuman(number);
            return Array.isArray(result) &&
                result.length === 2 &&
                result[0] === parseInt(result[0], 10) &&
                typeof result[1] === 'string';
        });
        jsc.property('returns seconds on the first array position', 'integer 59', function (number) {
            return $.PrivateBin.helper.secondsToHuman(number)[0] === number;
        });
        jsc.property('returns seconds on the second array position', 'integer 59', function (number) {
            return $.PrivateBin.helper.secondsToHuman(number)[1] === 'second';
        });
        jsc.property('returns minutes on the first array position', 'integer 60 3599', function (number) {
            return $.PrivateBin.helper.secondsToHuman(number)[0] === Math.floor(number / 60);
        });
        jsc.property('returns minutes on the second array position', 'integer 60 3599', function (number) {
            return $.PrivateBin.helper.secondsToHuman(number)[1] === 'minute';
        });
        jsc.property('returns hours on the first array position', 'integer 3600 86399', function (number) {
            return $.PrivateBin.helper.secondsToHuman(number)[0] === Math.floor(number / (60 * 60));
        });
        jsc.property('returns hours on the second array position', 'integer 3600 86399', function (number) {
            return $.PrivateBin.helper.secondsToHuman(number)[1] === 'hour';
        });
        jsc.property('returns days on the first array position', 'integer 86400 5184000', function (number) {
            return $.PrivateBin.helper.secondsToHuman(number)[0] === Math.floor(number / (60 * 60 * 24));
        });
        jsc.property('returns days on the second array position', 'integer 86400 5184000', function (number) {
            return $.PrivateBin.helper.secondsToHuman(number)[1] === 'day';
        });
        // max safe integer as per http://ecma262-5.com/ELS5_HTML.htm#Section_8.5
        jsc.property('returns months on the first array position', 'integer 5184000 9007199254740991', function (number) {
            return $.PrivateBin.helper.secondsToHuman(number)[0] === Math.floor(number / (60 * 60 * 24 * 30));
        });
        jsc.property('returns months on the second array position', 'integer 5184000 9007199254740991', function (number) {
            return $.PrivateBin.helper.secondsToHuman(number)[1] === 'month';
        });
    });

    // this test is not yet meaningful using jsdom, as it does not contain getSelection support.
    // TODO: This needs to be tested using a browser.
    describe('selectText', function () {
        jsc.property(
            'selection contains content of given ID',
            jsc.nearray(jsc.nearray(jsc.elements(alnumString))),
            'nearray string',
            function (ids, contents) {
                var html = '',
                    result = true;
                ids.forEach(function(item, i) {
                    html += '<div id="' + item.join('') + '">' + $.PrivateBin.helper.htmlEntities(contents[i] || contents[0]) + '</div>';
                });
                var clean = jsdom(html);
                ids.forEach(function(item, i) {
                    $.PrivateBin.helper.selectText(item.join(''));
                    // TODO: As per https://github.com/tmpvar/jsdom/issues/321 there is no getSelection in jsdom, yet.
                    // Once there is one, uncomment the line below to actually check the result.
                    //result *= (contents[i] || contents[0]) === window.getSelection().toString();
                });
                clean();
                return Boolean(result);
            }
        );
    });

    describe('setElementText', function () {
        jsc.property(
            'replaces the content of an element',
            jsc.nearray(jsc.nearray(jsc.elements(alnumString))),
            'nearray string',
            'string',
            function (ids, contents, replacingContent) {
                var html = '',
                    result = true;
                ids.forEach(function(item, i) {
                    html += '<div id="' + item.join('') + '">' + $.PrivateBin.helper.htmlEntities(contents[i] || contents[0]) + '</div>';
                });
                var clean = jsdom(html);
                ids.forEach(function(item, i) {
                    var id = item.join(''),
                        element = $(document.getElementById(id));
                    $.PrivateBin.helper.setElementText(element, replacingContent);
                    result *= replacingContent === $(document.getElementById(id)).text();
                });
                clean();
                return Boolean(result);
            }
        );
    });

    describe('setMessage', function () {
        jsc.property(
            'replaces the content of an empty element, analog to setElementText',
            jsc.nearray(jsc.nearray(jsc.elements(alnumString))),
            'nearray string',
            'string',
            function (ids, contents, replacingContent) {
                var html = '',
                    result = true;
                ids.forEach(function(item, i) {
                    html += '<div id="' + item.join('') + '">' + $.PrivateBin.helper.htmlEntities(contents[i] || contents[0]) + '</div>';
                });
                var clean = jsdom(html);
                ids.forEach(function(item, i) {
                    var id = item.join(''),
                        element = $(document.getElementById(id));
                    $.PrivateBin.helper.setMessage(element, replacingContent);
                    result *= replacingContent === $(document.getElementById(id)).text();
                });
                clean();
                return Boolean(result);
            }
        );
        jsc.property(
            'replaces the last child of a non-empty element',
            jsc.nearray(jsc.nearray(jsc.elements(alnumString))),
            jsc.nearray(jsc.nearray(jsc.elements(alnumString))),
            'nearray string',
            'string',
            function (ids, classes, contents, replacingContent) {
                var html = '',
                    result = true;
                ids.forEach(function(item, i) {
                    html += '<div id="' + item.join('') + '"><span class="' + (classes[i] || classes[0]) + '"></span> ' + $.PrivateBin.helper.htmlEntities(contents[i] || contents[0]) + '</div>';
                });
                var clean = jsdom(html);
                ids.forEach(function(item, i) {
                    var id = item.join(''),
                        element = $(document.getElementById(id));
                    $.PrivateBin.helper.setMessage(element, replacingContent);
                    result *= ' ' + replacingContent === $(document.getElementById(id)).contents()[1].nodeValue;
                });
                clean();
                return Boolean(result);
            }
        );
    });

    describe('urls2links', function () {
        jsc.property(
            'ignores non-URL content',
            'string',
            function (content) {
                var element = $('<div>' + content + '</div>'),
                    before = element.html();
                $.PrivateBin.helper.urls2links(element);
                return before === element.html();
            }
        );
        jsc.property(
            'replaces URLs with anchors',
            'string',
            jsc.elements(['http', 'https', 'ftp']),
            jsc.nearray(jsc.elements(a2zString)),
            jsc.array(jsc.elements(queryString)),
            jsc.array(jsc.elements(queryString)),
            'string',
            function (prefix, schema, address, query, fragment, postfix) {
                var query = query.join(''),
                    fragment = fragment.join(''),
                    url = schema + '://' + address.join('') + '/?' + query + '#' + fragment,
                    prefix = $.PrivateBin.helper.htmlEntities(prefix),
                    postfix = ' ' + $.PrivateBin.helper.htmlEntities(postfix),
                    element = $('<div>' + prefix + url + postfix + '</div>');

                // special cases: When the query string and fragment imply the beginning of an HTML entity, eg. &#0 or &#x
                if (
                    query.slice(-1) === '&' &&
                    (parseInt(fragment.substring(0, 1), 10) >= 0 || fragment.charAt(0) === 'x' )
                )
                {
                    url = schema + '://' + address.join('') + '/?' + query.substring(0, query.length - 1);
                    postfix = '';
                    element = $('<div>' + prefix + url + '</div>');
                }

                $.PrivateBin.helper.urls2links(element);
                return element.html() === $('<div>' + prefix + '<a href="' + url + '" rel="nofollow">' + url + '</a>' + postfix + '</div>').html();
            }
        );
        jsc.property(
            'replaces magnet links with anchors',
            'string',
            jsc.array(jsc.elements(queryString)),
            'string',
            function (prefix, query, postfix) {
                var url = 'magnet:?' + query.join(''),
                    prefix = $.PrivateBin.helper.htmlEntities(prefix),
                    postfix = $.PrivateBin.helper.htmlEntities(postfix),
                    element = $('<div>' + prefix + url + ' ' + postfix + '</div>');
                $.PrivateBin.helper.urls2links(element);
                return element.html() === $('<div>' + prefix + '<a href="' + url + '" rel="nofollow">' + url + '</a> ' + postfix + '</div>').html();
            }
        );
    });

    describe('scriptLocation', function () {
        jsc.property(
            'returns the URL without query & fragment',
            jsc.nearray(jsc.elements(a2zString)),
            jsc.nearray(jsc.elements(a2zString)),
            jsc.array(jsc.elements(queryString)),
            'string',
            function (schema, address, query, fragment) {
                var expected = schema.join('') + '://' + address.join('') + '/',
                    clean = jsdom('', {url: expected + '?' + query.join('') + '#' + fragment}),
                    result = $.PrivateBin.helper.scriptLocation();
                clean();
                return expected === result;
            }
        );
    });

    describe('pasteId', function () {
        jsc.property(
            'returns the query string without separator, if any',
            jsc.nearray(jsc.elements(a2zString)),
            jsc.nearray(jsc.elements(a2zString)),
            jsc.array(jsc.elements(queryString)),
            'string',
            function (schema, address, query, fragment) {
                var queryString = query.join(''),
                    clean = jsdom('', {
                        url: schema.join('') + '://' + address.join('') +
                             '/?' + queryString + '#' + fragment
                    }),
                    result = $.PrivateBin.helper.pasteId();
                clean();
                return queryString === result;
            }
        );
    });

    describe('pageKey', function () {
        jsc.property(
            'returns the fragment of the URL',
            jsc.nearray(jsc.elements(a2zString)),
            jsc.nearray(jsc.elements(a2zString)),
            jsc.array(jsc.elements(queryString)),
            jsc.array(jsc.elements(base64String)),
            function (schema, address, query, fragment) {
                var fragmentString = fragment.join(''),
                    clean = jsdom('', {
                        url: schema.join('') + '://' + address.join('') +
                             '/?' + query.join('') + '#' + fragmentString
                    }),
                    result = $.PrivateBin.helper.pageKey();
                clean();
                return fragmentString === result;
            }
        );
        jsc.property(
            'returns the fragment stripped of trailing query parts',
            jsc.nearray(jsc.elements(a2zString)),
            jsc.nearray(jsc.elements(a2zString)),
            jsc.array(jsc.elements(queryString)),
            jsc.array(jsc.elements(base64String)),
            jsc.array(jsc.elements(queryString)),
            function (schema, address, query, fragment, trail) {
                var fragmentString = fragment.join(''),
                    clean = jsdom('', {
                        url: schema.join('') + '://' + address.join('') + '/?' +
                             query.join('') + '#' + fragmentString + '&' + trail.join('')
                    }),
                    result = $.PrivateBin.helper.pageKey();
                clean();
                return fragmentString === result;
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
                var result = $.PrivateBin.helper.htmlEntities(string);
                return !(/[<>"'`=\/]/.test(result)) && !(string.indexOf('&') > -1 && !(/&amp;/.test(result)));
            }
        );
    });
});
