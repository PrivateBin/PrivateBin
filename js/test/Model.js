'use strict';
var common = require('../common');

describe('Model', function () {
    describe('getExpirationDefault', function () {
        before(function () {
            $.PrivateBin.Model.reset();
            cleanup = jsdom();
        });

        jsc.property(
            'returns the contents of the element with id "pasteExpiration"',
            'nearray asciinestring',
            'string',
            'small nat',
            function (keys, value, key) {
                keys = keys.map($.PrivateBin.Helper.htmlEntities);
                value = $.PrivateBin.Helper.htmlEntities(value);
                var content = keys.length > key ? keys[key] : keys[0],
                    contents = '<select id="pasteExpiration" name="pasteExpiration">';
                keys.forEach(function(item) {
                    contents += '<option value="' + item + '"';
                    if (item === content) {
                        contents += ' selected="selected"';
                    }
                    contents += '>' + value + '</option>';
                });
                contents += '</select>';
                $('body').html(contents);
                var result = $.PrivateBin.Helper.htmlEntities(
                    $.PrivateBin.Model.getExpirationDefault()
                );
                $.PrivateBin.Model.reset();
                return content === result;
            }
        );
    });

    describe('getFormatDefault', function () {
        before(function () {
            $.PrivateBin.Model.reset();
        });
        after(function () {
            cleanup();
        });

        jsc.property(
            'returns the contents of the element with id "pasteFormatter"',
            'nearray asciinestring',
            'string',
            'small nat',
            function (keys, value, key) {
                keys = keys.map($.PrivateBin.Helper.htmlEntities);
                value = $.PrivateBin.Helper.htmlEntities(value);
                var content = keys.length > key ? keys[key] : keys[0],
                    contents = '<select id="pasteFormatter" name="pasteFormatter">';
                keys.forEach(function(item) {
                    contents += '<option value="' + item + '"';
                    if (item === content) {
                        contents += ' selected="selected"';
                    }
                    contents += '>' + value + '</option>';
                });
                contents += '</select>';
                $('body').html(contents);
                var result = $.PrivateBin.Helper.htmlEntities(
                    $.PrivateBin.Model.getFormatDefault()
                );
                $.PrivateBin.Model.reset();
                return content === result;
            }
        );
    });

    describe('getPasteId', function () {
        this.timeout(30000);
        beforeEach(function () {
            $.PrivateBin.Model.reset();
        });

        jsc.property(
            'returns the query string without separator, if any',
            common.jscUrl(true, false),
            jsc.tuple(new Array(16).fill(common.jscHexString)),
            jsc.array(common.jscQueryString()),
            jsc.array(common.jscQueryString()),
            function (url, pasteId, queryStart, queryEnd) {
                if (queryStart.length > 0) {
                    queryStart.push('&');
                }
                if (queryEnd.length > 0) {
                    queryEnd.unshift('&');
                }
                url.query = queryStart.concat(pasteId, queryEnd);
                const pasteIdString = pasteId.join(''),
                    clean           = jsdom('', {url: common.urlToString(url)});
                global.URL = require('jsdom-url').URL;
                const result = $.PrivateBin.Model.getPasteId();
                $.PrivateBin.Model.reset();
                clean();
                return pasteIdString === result;
            }
        );
        jsc.property(
            'throws exception on empty query string',
            common.jscUrl(true, false),
            function (url) {
                let clean = jsdom('', {url: common.urlToString(url)}),
                    result = false;
                global.URL = require('jsdom-url').URL;
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
        this.timeout(30000);
        beforeEach(function () {
            $.PrivateBin.Model.reset();
        });

        jsc.property(
            'returns the fragment of a v1 URL',
            common.jscUrl(),
            function (url) {
                url.fragment = common.btoa(url.fragment.padStart(32, '\u0000'));
                const clean = jsdom('', {url: common.urlToString(url)}),
                    result = $.PrivateBin.Model.getPasteKey();
                $.PrivateBin.Model.reset();
                clean();
                return url.fragment === result;
            }
        );
        jsc.property(
            'returns the v1 fragment stripped of trailing query parts',
            common.jscUrl(),
            jsc.array(common.jscHashString()),
            function (url, trail) {
                const fragmentString = common.btoa(url.fragment.padStart(32, '\u0000'));
                url.fragment = fragmentString + '&' + trail.join('');
                const clean = jsdom('', {url: common.urlToString(url)}),
                    result = $.PrivateBin.Model.getPasteKey();
                $.PrivateBin.Model.reset();
                clean();
                return fragmentString === result;
            }
        );
        jsc.property(
            'returns the fragment of a v2 URL',
            common.jscUrl(),
            function (url) {
                // base58 strips leading NULL bytes, so the string is padded with these if not found
                const fragment = url.fragment.padStart(32, '\u0000');
                url.fragment = $.PrivateBin.CryptTool.base58encode(fragment);
                const clean = jsdom('', {url: common.urlToString(url)}),
                    result = $.PrivateBin.Model.getPasteKey();
                $.PrivateBin.Model.reset();
                clean();
                return fragment === result;
            }
        );
        jsc.property(
            'returns the v2 fragment stripped of trailing query parts',
            common.jscUrl(),
            jsc.array(common.jscHashString()),
            function (url, trail) {
                // base58 strips leading NULL bytes, so the string is padded with these if not found
                const fragment = url.fragment.padStart(32, '\u0000');
                url.fragment = $.PrivateBin.CryptTool.base58encode(fragment) + '&' + trail.join('');
                const clean = jsdom('', {url: common.urlToString(url)}),
                    result = $.PrivateBin.Model.getPasteKey();
                $.PrivateBin.Model.reset();
                clean();
                return fragment === result;
            }
        );
        jsc.property(
            'throws exception on empty fragment of the URL',
            common.jscUrl(false),
            function (url) {
                let clean = jsdom('', {url: common.urlToString(url)}),
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

    describe('getTemplate', function () {
        beforeEach(function () {
            $.PrivateBin.Model.reset();
        });

        jsc.property(
            'returns the contents of the element with id "[name]template"',
            jsc.nearray(common.jscAlnumString()),
            jsc.nearray(common.jscA2zString()),
            jsc.nearray(common.jscAlnumString()),
            function (id, element, value) {
                id = id.join('');
                element = element.join('');
                value = value.join('').trim();

                // <br>, <hr>, <img> and <wbr> tags can't contain strings,
                // table tags can't be alone, so test with a <p> instead
                if (['br', 'col', 'hr', 'img', 'tr', 'td', 'th', 'wbr'].indexOf(element) >= 0) {
                    element = 'p';
                }

                $('body').html(
                    '<div id="templates"><' + element + ' id="' + id +
                    'template">' + value + '</' + element + '></div>'
                );
                $.PrivateBin.Model.init();
                var template = '<' + element + ' id="' + id + '">' + value +
                    '</' + element + '>',
                    result = $.PrivateBin.Model.getTemplate(id).wrap('<p/>').parent().html();
                $.PrivateBin.Model.reset();
                return template === result;
            }
        );
    });
});

