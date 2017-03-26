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
                    html += '<div id="' + item.join('') + '">' + $.PrivateBin.Helper.htmlEntities(contents[i] || contents[0]) + '</div>';
                });
                var clean = jsdom(html);
                ids.forEach(function(item, i) {
                    $.PrivateBin.Helper.selectText(item.join(''));
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
        after(function () {
            cleanup();
        });

        jsc.property(
            'replaces the content of an element',
            jsc.nearray(jsc.nearray(jsc.elements(alnumString))),
            'nearray string',
            'string',
            function (ids, contents, replacingContent) {
                var html = '',
                    result = true;
                ids.forEach(function(item, i) {
                    html += '<div id="' + item.join('') + '">' + $.PrivateBin.Helper.htmlEntities(contents[i] || contents[0]) + '</div>';
                });
                var elements = $('<body />').html(html);
                ids.forEach(function(item, i) {
                    var id = item.join(''),
                        element = elements.find('#' + id).first();
                    $.PrivateBin.Helper.setElementText(element, replacingContent);
                    result *= replacingContent === element.text();
                });
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
                var element = $('<div>' + content + '</div>'),
                    before = element.html();
                $.PrivateBin.Helper.urls2links(element);
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
                    prefix = $.PrivateBin.Helper.htmlEntities(prefix),
                    postfix = ' ' + $.PrivateBin.Helper.htmlEntities(postfix),
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

                $.PrivateBin.Helper.urls2links(element);
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
                    prefix = $.PrivateBin.Helper.htmlEntities(prefix),
                    postfix = $.PrivateBin.Helper.htmlEntities(postfix),
                    element = $('<div>' + prefix + url + ' ' + postfix + '</div>');
                $.PrivateBin.Helper.urls2links(element);
                return element.html() === $('<div>' + prefix + '<a href="' + url + '" rel="nofollow">' + url + '</a> ' + postfix + '</div>').html();
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
                return result === $.PrivateBin.Helper.sprintf.apply(this, params)
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
        jsc.property(
            'returns the requested cookie',
            'nearray asciinestring',
            'nearray asciistring',
            function (labels, values) {
                var selectedKey = '', selectedValue = '',
                    cookieArray = [],
                    count = 0;
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

describe('I18n', function () {
    describe('translate', function () {
        before(function () {
            $.PrivateBin.I18n.reset();
        });

        jsc.property(
            'returns message ID unchanged if no translation found',
            'string',
            function (messageId) {
                messageId = messageId.replace(/%(s|d)/g, '%%');
                var result = $.PrivateBin.I18n.translate(messageId);
                $.PrivateBin.I18n.reset();
                var alias = $.PrivateBin.I18n._(messageId);
                $.PrivateBin.I18n.reset();
                return messageId === result && messageId === alias;
            }
        );
        jsc.property(
            'replaces %s in strings with first given parameter',
            'string',
            '(small nearray) string',
            'string',
            function (prefix, params, postfix) {
                prefix    =    prefix.replace(/%(s|d)/g, '%%');
                params[0] = params[0].replace(/%(s|d)/g, '%%');
                postfix   =   postfix.replace(/%(s|d)/g, '%%');
                var translation = prefix + params[0] + postfix;
                params.unshift(prefix + '%s' + postfix);
                var result = $.PrivateBin.I18n.translate.apply(this, params);
                $.PrivateBin.I18n.reset();
                var alias = $.PrivateBin.I18n._.apply(this, params);
                $.PrivateBin.I18n.reset();
                return translation === result && translation === alias;
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
        jsc.property(
            'throws exception on empty query string',
            jsc.nearray(jsc.elements(a2zString)),
            jsc.nearray(jsc.elements(a2zString)),
            'string',
            function (schema, address, fragment) {
                var clean = jsdom('', {
                        url: schema.join('') + '://' + address.join('') +
                             '/#' + fragment
                    }),
                    result = false;
                try {
                    $.PrivateBin.Model.getPasteId();
                }
                catch(err) {
                    result = true;
                }
                $.PrivateBin.Model.reset();
                clean();
                return result;
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
        jsc.property(
            'throws exception on empty fragment of the URL',
            jsc.nearray(jsc.elements(a2zString)),
            jsc.nearray(jsc.elements(a2zString)),
            jsc.array(jsc.elements(queryString)),
            function (schema, address, query) {
                var clean = jsdom('', {
                        url: schema.join('') + '://' + address.join('') +
                             '/?' + query.join('')
                    }),
                    result = false;
                try {
                    $.PrivateBin.Model.getPasteKey();
                }
                catch(err) {
                    result = true;
                }
                $.PrivateBin.Model.reset();
                clean();
                return result;
            }
        );
    });
});
