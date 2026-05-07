'use strict';
const common = require('../common');
const fc = require('fast-check');

describe('Model', function () {
    describe('getExpirationDefault', function () {
        before(function () {
            PrivateBin.Model.reset();
            globalThis.cleanup();
        });

        it('returns the contents of the element with id "pasteExpiration"', () => {
            fc.assert(fc.property(
                fc.array(fc.asciiString({minLength: 1}), {minLength: 1}),
                fc.string(),
                fc.nat(),
                function (keys, value, key) {
                    keys = keys.map(PrivateBin.Helper.htmlEntities);
                    value = PrivateBin.Helper.htmlEntities(value);
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
                    document.body.innerHTML = contents;
                    var result = PrivateBin.Helper.htmlEntities(
                        PrivateBin.Model.getExpirationDefault()
                    );
                    PrivateBin.Model.reset();
                    return content === result;
                }
            ));
        });
    });

    describe('getFormatDefault', function () {
        before(function () {
            PrivateBin.Model.reset();
        });
        after(function () {
            globalThis.cleanup();
        });

        it('returns the contents of the element with id "pasteFormatter"', () => {
            fc.assert(fc.property(
                fc.array(fc.asciiString({minLength: 1}), {minLength: 1}),
                fc.string(),
                fc.nat(),
                function (keys, value, key) {
                    keys = keys.map(PrivateBin.Helper.htmlEntities);
                    value = PrivateBin.Helper.htmlEntities(value);
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
                    document.body.innerHTML = contents;
                    var result = PrivateBin.Helper.htmlEntities(
                        PrivateBin.Model.getFormatDefault()
                    );
                    PrivateBin.Model.reset();
                    return content === result;
                }
            ));
        });
    });

    describe('getPasteId', function () {
        this.timeout(30000);
        beforeEach(function () {
            PrivateBin.Model.reset();
        });

        it('returns the query string without separator, if any', () => {
            fc.assert(fc.property(
                common.fcUrl(true, false),
                fc.array(common.fcHexString(), {minLength: 16, maxLength: 16}),
                fc.array(common.fcQueryString()),
                fc.array(common.fcQueryString()),
                function (url, pasteId, queryStart, queryEnd) {
                    if (queryStart.length > 0) {
                        queryStart.push('&');
                    }
                    if (queryEnd.length > 0) {
                        queryEnd.unshift('&');
                    }
                    url.query = queryStart.concat(pasteId, queryEnd);
                    const pasteIdString = pasteId.join(''),
                        clean           = globalThis.cleanup('', {url: common.urlToString(url)});
                    const result = PrivateBin.Model.getPasteId();
                    PrivateBin.Model.reset();
                    clean();
                    return pasteIdString === result;
                }
            ));
        });
        it('throws exception on empty query string', () => {
            fc.assert(fc.property(
                common.fcUrl(true, false),
                function (url) {
                    const clean = globalThis.cleanup('', {url: common.urlToString(url)});
                    let result = false;
                    try {
                        PrivateBin.Model.getPasteId();
                    }
                    catch(err) {
                        result = true;
                    }
                    PrivateBin.Model.reset();
                    clean();
                    return result;
                }
            ));
        });
    });

    describe('getPasteKey', function () {
        this.timeout(30000);
        beforeEach(function () {
            PrivateBin.Model.reset();
        });

        it('throws exception on v1 URLs', () => {
            fc.assert(fc.property(
                common.fcUrl(),
                function (url) {
                    url.fragment = '0OIl'; // any non-base58 string
                    const clean = globalThis.cleanup('', {url: common.urlToString(url)});
                    let result = false;
                    try {
                        PrivateBin.Model.getPasteId();
                    }
                    catch(err) {
                        result = true;
                    }
                    PrivateBin.Model.reset();
                    clean();
                    return result;
                }
            ));
        });
        it('returns the fragment stripped of trailing query parts', () => {
            fc.assert(fc.property(
                common.fcUrl(),
                fc.array(common.fcHashString()),
                function (url, trail) {
                    const fragment = url.fragment.padStart(32, '\u0000');
                    url.fragment = PrivateBin.CryptTool.base58encode(fragment) + '&' + trail.join('');
                    const clean = globalThis.cleanup('', {url: common.urlToString(url)}),
                        result = PrivateBin.Model.getPasteKey();
                    PrivateBin.Model.reset();
                    clean();
                    return fragment === result;
                }
            ));
        });
        it('returns the fragment of a v2 URL', () => {
            fc.assert(fc.property(
                common.fcUrl(),
                function (url) {
                    // base58 strips leading NULL bytes, so the string is padded with these if not found
                    const fragment = url.fragment.padStart(32, '\u0000');
                    url.fragment = PrivateBin.CryptTool.base58encode(fragment);
                    const clean = globalThis.cleanup('', {url: common.urlToString(url)}),
                        result = PrivateBin.Model.getPasteKey();
                    PrivateBin.Model.reset();
                    clean();
                    return fragment === result;
                }
            ));
        });
        it('returns the v2 fragment stripped of trailing query parts', () => {
            fc.assert(fc.property(
                common.fcUrl(),
                fc.array(common.fcHashString()),
                function (url, trail) {
                    // base58 strips leading NULL bytes, so the string is padded with these if not found
                    const fragment = url.fragment.padStart(32, '\u0000');
                    url.fragment = PrivateBin.CryptTool.base58encode(fragment) + '&' + trail.join('');
                    const clean = globalThis.cleanup('', {url: common.urlToString(url)}),
                        result = PrivateBin.Model.getPasteKey();
                    PrivateBin.Model.reset();
                    clean();
                    return fragment === result;
                }
            ));
        });
        it('throws exception on empty fragment of the URL', () => {
            fc.assert(fc.property(
                common.fcUrl(false),
                function (url) {
                    let clean = globalThis.cleanup('', {url: common.urlToString(url)}),
                        result = false;
                    try {
                        PrivateBin.Model.getPasteKey();
                    }
                    catch(err) {
                        result = true;
                    }
                    PrivateBin.Model.reset();
                    clean();
                    return result;
                }
            ));
        });
    });

    describe('getTemplate', function () {
        beforeEach(function () {
            PrivateBin.Model.reset();
        });

        it('returns the contents of the element with id "[name]template"', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString(), {minLength: 1}),
                fc.array(common.fcA2zString(), {minLength: 1}),
                fc.array(common.fcAlnumString(), {minLength: 1}),
                function (id, element, value) {
                    id = id.join('');
                    element = element.join('');
                    value = value.join('').trim();

                    // <br>, <hr>, <img> and <wbr> tags can't contain strings,
                    // table tags can't be alone, so test with a <p> instead
                    if (['br', 'col', 'hr', 'img', 'tr', 'td', 'th', 'wbr'].indexOf(element) >= 0) {
                        element = 'p';
                    }

                    document.body.innerHTML = (
                        '<div id="templates"><' + element + ' id="' + id +
                        'template">' + value + '</' + element + '></div>'
                    );
                    PrivateBin.Model.init();
                    var template = '<' + element + ' id="' + id + '">' + value +
                        '</' + element + '>',
                        templateEl = PrivateBin.Model.getTemplate(id),
                        wrapper = document.createElement('p');
                    wrapper.appendChild(templateEl.cloneNode(true));
                    var result = wrapper.innerHTML;
                    PrivateBin.Model.reset();
                    return template === result;
                }
            ));
        });
    });
});

