'use strict';
var common = require('../common');

describe('Model', function () {
    describe('getExpirationDefault', function () {
        before(function () {
            $.PrivateBin.Model.reset();
            cleanup();
        });

        jsc.property(
            'returns the contents of the element with id "pasteExpiration"',
            'array asciinestring',
            'string',
            'small nat',
            function (keys, value, key) {
                keys = keys.map(common.htmlEntities);
                value = common.htmlEntities(value);
                var content = keys.length > key ? keys[key] : (keys.length > 0 ? keys[0] : 'null'),
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
                var result = common.htmlEntities(
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
            cleanup();
        });

        jsc.property(
            'returns the contents of the element with id "pasteFormatter"',
            'array asciinestring',
            'string',
            'small nat',
            function (keys, value, key) {
                keys = keys.map(common.htmlEntities);
                value = common.htmlEntities(value);
                var content = keys.length > key ? keys[key] : (keys.length > 0 ? keys[0] : 'null'),
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
                var result = common.htmlEntities(
                    $.PrivateBin.Model.getFormatDefault()
                );
                $.PrivateBin.Model.reset();
                return content === result;
            }
        );
    });

    describe('getPasteId', function () {
        this.timeout(30000);
        before(function () {
            $.PrivateBin.Model.reset();
            cleanup();
        });

        jsc.property(
            'returns the query string without separator, if any',
            jsc.nearray(common.jscA2zString()),
            jsc.nearray(common.jscA2zString()),
            jsc.nearray(common.jscHashString()),
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
            jsc.nearray(common.jscA2zString()),
            jsc.nearray(common.jscA2zString()),
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
        this.timeout(30000);
        jsc.property(
            'returns the fragment of the URL',
            jsc.nearray(common.jscA2zString()),
            jsc.nearray(common.jscA2zString()),
            jsc.array(common.jscQueryString()),
            jsc.nearray(common.jscBase64String()),
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
            jsc.nearray(common.jscA2zString()),
            jsc.nearray(common.jscA2zString()),
            jsc.array(common.jscQueryString()),
            jsc.nearray(common.jscBase64String()),
            jsc.array(common.jscHashString()),
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
            jsc.nearray(common.jscA2zString()),
            jsc.nearray(common.jscA2zString()),
            jsc.array(common.jscQueryString()),
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

    describe('getTemplate', function () {
        before(function () {
            $.PrivateBin.Model.reset();
            cleanup();
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

