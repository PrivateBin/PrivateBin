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
            'nearray string',
            'nearray nestring',
            function (ids, contents) {
                //console.log(ids, contents);
                var html = '',
                    result = true;
                ids.forEach(function(item, i) {
                    html += '<div id="' + item + '">' + (contents[i] || contents[0]) + '</div>';
                });
                var clean = jsdom(html);
                ids.forEach(function(item, i) {
                    $.PrivateBin.helper.selectText(item);
                    // TODO: As per https://github.com/tmpvar/jsdom/issues/321 there is no getSelection in jsdom, yet.
                    // Once there is one, uncomment the line below to actually check the result.
                    //result *= (contents[i] || contents[0]) === window.getSelection().toString();
                });
                clean();
                return result;
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
