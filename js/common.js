'use strict';

// testing prerequisites
global.assert = require('assert');
const fc = require('fast-check');
global.jsdom = require('jsdom-global');
// initial DOM environment created by jsdom-global
let currentCleanup = global.jsdom();

// Recreates the jsdom environment and reloads privatebin.js into it.
// The reload is necessary because modules close over DOM element references
// in their init() methods, and pending async callbacks (e.g. setTimeout in
// CopyToClipboard) would otherwise fire against elements from a dead window.
global.cleanup = function (...args) {
    if (typeof currentCleanup === 'function') {
        currentCleanup();
    }
    currentCleanup = global.jsdom(...args);
    delete require.cache[require.resolve('./privatebin')];
    delete require.cache[require.resolve('./legacy')];
    require('./privatebin');
    global.PrivateBin = window.PrivateBin;
    return global.cleanup;
};
global.fs = require('fs');
global.WebCrypto = require('@peculiar/webcrypto').Crypto;

// application libraries to test
global.$ = global.jQuery = require('./jquery-3.7.1');
global.zlib = require('./zlib').zlib;
require('./prettify');
global.prettyPrint = window.PR ? window.PR.prettyPrint : function() {};
global.prettyPrintOne = window.PR ? window.PR.prettyPrintOne : function() {};
global.showdown = require('./showdown-2.1.0');
global.DOMPurify = require('./purify-3.4.1');
global.baseX = require('./base-x-5.0.1').baseX;
global.Legacy = require('./legacy').Legacy;
require('./privatebin');
global.PrivateBin = window.PrivateBin;

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
    mimeTypes = ['image/png', 'application/octet-stream'].concat(Object.keys(require('mime-db'))),
    formats = ['plaintext', 'markdown', 'syntaxhighlighting'];

// common testing helper functions
// as of jsDOM 22 the base64 functions provided in the DOM are more restrictive
// than browser implementation and throw when being passed invalid unicode
// codepoints - as we use these in the encryption with binary data, we need
// these to be character encoding agnostic
exports.atob = function(encoded) {
    return Buffer.from(encoded, 'base64').toString('binary');
};
exports.btoa = function(text) {
    return Buffer.from(text, 'binary').toString('base64');
};

// provides random lowercase characters from a to z
exports.fcA2zString = function() {
    return fc.constantFrom(...a2zString);
};

// provides random lowercase alpha numeric characters (a to z and 0 to 9)
exports.fcAlnumString = function() {
    return fc.constantFrom(...alnumString);
};

//provides random characters allowed in hexadecimal notation
exports.fcHexString = function() {
    return fc.constantFrom(...hexString);
};

// provides random characters allowed in GET queries
exports.fcQueryString = function() {
    return fc.constantFrom(...queryString);
};

// provides random characters allowed in hash queries
exports.fcHashString = function() {
    return fc.constantFrom(...hashString);
};

// provides random characters allowed in base64 encoded strings
exports.fcBase64String = function() {
    return fc.constantFrom(...base64String);
};

// provides a random URL schema supported by the whatwg-url library
exports.fcSchemas = function(withFtp = true) {
    return fc.constantFrom(...(withFtp ? schemas : schemas.slice(1)));
};

// provides a random supported language string
exports.fcSupportedLanguages = function() {
    return fc.constantFrom(...supportedLanguages);
};

// provides a random mime type
exports.fcMimeTypes = function() {
    return fc.constantFrom(...mimeTypes);
};

// provides a random PrivateBin document formatter
exports.fcFormats = function() {
    return fc.constantFrom(...formats);
};

// provides random URLs
exports.fcUrl = function(withFragment = true, withQuery = true) {
    let url = {
        schema: exports.fcSchemas(),
        address: fc.array(exports.fcA2zString(), {minLength: 1}),
    };
    if (withFragment) {
        url.fragment = fc.string();
    }
    if(withQuery) {
        url.query = fc.array(exports.fcQueryString());
    }
    return fc.record(url);
};

exports.urlToString = function (url) {
    return url.schema + '://' + url.address.join('') + '/' + (url.query ? '?' +
        encodeURI(url.query.join('').replace(/^&+|&+$/gm,'')) : '') +
        (url.fragment ? '#' + encodeURI(url.fragment) : '');
};

exports.enableClipboard = function () {
    // @ts-ignore
    navigator.clipboard = (function () {
        let savedText = "";

        async function writeText(text) {
            savedText = text;
        };

        async function readText() {
            return savedText;
        };

        return {
            writeText,
            readText,
        };
    })();
};
