'use strict';

// testing prerequisites
global.assert = require('assert');
global.jsc = require('jsverify');
global.jsdom = require('jsdom-global');
global.cleanup = global.jsdom();
global.URL = require('jsdom-url').URL;
global.fs = require('fs');
global.WebCrypto = require('@peculiar/webcrypto').Crypto;

// application libraries to test
global.$ = global.jQuery = require('./jquery-3.7.1');
global.RawDeflate = require('./rawinflate-0.3').RawDeflate;
global.zlib = require('./zlib-1.3.1').zlib;
require('./prettify');
global.prettyPrint = window.PR.prettyPrint;
global.prettyPrintOne = window.PR.prettyPrintOne;
global.showdown = require('./showdown-2.1.0');
global.DOMPurify = require('./purify-3.1.7');
global.baseX = require('./base-x-4.0.0').baseX;
global.Legacy = require('./legacy').Legacy;
require('./bootstrap-3.4.1');
require('./privatebin');

// internal variables
var a2zString    = ['a','b','c','d','e','f','g','h','i','j','k','l','m',
                    'n','o','p','q','r','s','t','u','v','w','x','y','z'],
    digitString  = ['0','1','2','3','4','5','6','7','8','9'],
    alnumString  = a2zString.concat(digitString),
    hexString    = digitString.concat(['a','b','c','d','e','f']),
    queryString  = alnumString.concat(['+','%','&','.','*','-','_']),
    hashString   = queryString.concat(['!']),
    base64String = alnumString.concat(['+','/','=']).concat(
        a2zString.map(function(c) {
            return c.toUpperCase();
        })
    ),
    schemas = ['ftp','http','https'],
    supportedLanguages = ['ar', 'bg', 'ca', 'co', 'cs', 'de', 'el', 'es', 'et', 'fi', 'fr', 'he', 'hu', 'id', 'it', 'ja', 'jbo', 'lt', 'no', 'nl', 'pl', 'pt', 'oc', 'ru', 'sk', 'sl', 'th', 'tr', 'uk', 'zh'],
    mimeTypes = ['image/png', 'application/octet-stream'],
    formats = ['plaintext', 'markdown', 'syntaxhighlighting'],
    mimeFile = fs.createReadStream('/etc/mime.types'),
    mimeLine = '';

// populate mime types from environment
mimeFile.on('data', function(data) {
    mimeLine += data;
    var index = mimeLine.indexOf('\n');
    while (index > -1) {
        var line = mimeLine.substring(0, index);
        mimeLine = mimeLine.substring(index + 1);
        parseMime(line);
        index = mimeLine.indexOf('\n');
    }
});

mimeFile.on('end', function() {
    if (mimeLine.length > 0) {
        parseMime(mimeLine);
    }
});

function parseMime(line) {
    // ignore comments
    var index = line.indexOf('#');
    if (index > -1) {
        line = line.substring(0, index);
    }

    // ignore bits after tabs
    index = line.indexOf('\t');
    if (index > -1) {
        line = line.substring(0, index);
    }
    if (line.length > 0) {
        mimeTypes.push(line);
    }
}

// common testing helper functions
exports.atob = atob;
exports.btoa = btoa;

// provides random lowercase characters from a to z
exports.jscA2zString = function() {
    return jsc.elements(a2zString);
};

// provides random lowercase alpha numeric characters (a to z and 0 to 9)
exports.jscAlnumString = function() {
    return jsc.elements(alnumString);
};

//provides random characters allowed in hexadecimal notation
exports.jscHexString = function() {
    return jsc.elements(hexString);
};

// provides random characters allowed in GET queries
exports.jscQueryString = function() {
    return jsc.elements(queryString);
};

// provides random characters allowed in hash queries
exports.jscHashString = function() {
    return jsc.elements(hashString);
};

// provides random characters allowed in base64 encoded strings
exports.jscBase64String = function() {
    return jsc.elements(base64String);
};

// provides a random URL schema supported by the whatwg-url library
exports.jscSchemas = function(withFtp = true) {
    return jsc.elements(withFtp ? schemas : schemas.slice(1));
};

// provides a random supported language string
exports.jscSupportedLanguages = function() {
    return jsc.elements(supportedLanguages);
};

// provides a random mime type
exports.jscMimeTypes = function() {
    return jsc.elements(mimeTypes);
};

// provides a random PrivateBin paste formatter
exports.jscFormats = function() {
    return jsc.elements(formats);
};

// provides random URLs
exports.jscUrl = function(withFragment = true, withQuery = true) {
    let url = {
        schema: exports.jscSchemas(),
        address: jsc.nearray(exports.jscA2zString()),
    };
    if (withFragment) {
        url.fragment = jsc.string;
    }
    if(withQuery) {
        url.query = jsc.array(exports.jscQueryString());
    }
    return jsc.record(url);
};

exports.urlToString = function (url) {
    return url.schema + '://' + url.address.join('') + '/' + (url.query ? '?' +
        encodeURI(url.query.join('').replace(/^&+|&+$/gm,'')) : '') +
        (url.fragment ? '#' + encodeURI(url.fragment) : '');
};
