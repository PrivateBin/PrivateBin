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
    ),
    // schemas supported by the whatwg-url library
    schemas = ['ftp','gopher','http','https','ws','wss'];

global.$ = global.jQuery = require('./jquery-3.1.1');
global.sjcl = require('./sjcl-1.0.6');
global.Base64 = require('./base64-2.1.9');
global.RawDeflate = require('./rawdeflate-0.5');
require('./rawinflate-0.3');
require('./privatebin');

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

    describe('baseUri', function () {
        before(function () {
            $.PrivateBin.Helper.reset();
        });

        jsc.property(
            'returns the URL without query & fragment',
            jsc.elements(schemas),
            jsc.nearray(jsc.elements(a2zString)),
            jsc.array(jsc.elements(queryString)),
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
                var result = $.PrivateBin.Helper.htmlEntities(string);
                return !(/[<>"'`=\/]/.test(result)) && !(string.indexOf('&') > -1 && !(/&amp;/.test(result)));
            }
        );
    });
});

describe('Model', function () {
    describe('getPasteId', function () {
        before(function () {
            $.PrivateBin.Model.reset();
        });

        jsc.property(
            'returns the query string without separator, if any',
            jsc.nearray(jsc.elements(a2zString)),
            jsc.nearray(jsc.elements(a2zString)),
            jsc.nearray(jsc.elements(queryString)),
            'string',
            function (schema, address, query, fragment) {
                var queryString = query.join(''),
                    clean = jsdom('', {
                        url: schema.join('') + '://' + address.join('') +
                             '/?' + queryString + '#' + fragment
                    }),
                    result = $.PrivateBin.Model.getPasteId();
                $.PrivateBin.Model.reset();
                clean();
                return queryString === result;
            }
        );
    });

    describe('getPasteKey', function () {
        jsc.property(
            'returns the fragment of the URL',
            jsc.nearray(jsc.elements(a2zString)),
            jsc.nearray(jsc.elements(a2zString)),
            jsc.array(jsc.elements(queryString)),
            jsc.nearray(jsc.elements(base64String)),
            function (schema, address, query, fragment) {
                var fragmentString = fragment.join(''),
                    clean = jsdom('', {
                        url: schema.join('') + '://' + address.join('') +
                             '/?' + query.join('') + '#' + fragmentString
                    }),
                    result = $.PrivateBin.Model.getPasteKey();
                $.PrivateBin.Model.reset();
                clean();
                return fragmentString === result;
            }
        );
        jsc.property(
            'returns the fragment stripped of trailing query parts',
            jsc.nearray(jsc.elements(a2zString)),
            jsc.nearray(jsc.elements(a2zString)),
            jsc.array(jsc.elements(queryString)),
            jsc.nearray(jsc.elements(base64String)),
            jsc.array(jsc.elements(queryString)),
            function (schema, address, query, fragment, trail) {
                var fragmentString = fragment.join(''),
                    clean = jsdom('', {
                        url: schema.join('') + '://' + address.join('') + '/?' +
                             query.join('') + '#' + fragmentString + '&' + trail.join('')
                    }),
                    result = $.PrivateBin.Model.getPasteKey();
                $.PrivateBin.Model.reset();
                clean();
                return fragmentString === result;
            }
        );
    });
});
