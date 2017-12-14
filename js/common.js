'use strict';

// testing prerequisites
global.jsc = require('jsverify');
global.jsdom = require('jsdom-global');
global.cleanup = global.jsdom();
global.fs = require('fs');

// application libraries to test
global.$ = global.jQuery = require('./jquery-3.1.1');
global.sjcl = require('./sjcl-1.0.6');
global.Base64 = require('./base64-2.1.9').Base64;
global.RawDeflate = require('./rawdeflate-0.5').RawDeflate;
global.RawDeflate.inflate = require('./rawinflate-0.3').RawDeflate.inflate;
require('./prettify');
global.prettyPrint = window.PR.prettyPrint;
global.prettyPrintOne = window.PR.prettyPrintOne;
global.showdown = require('./showdown-1.6.1');
global.DOMPurify = require('./purify.min');
require('./bootstrap-3.3.7');
require('./privatebin');

// internal variables
var a2zString = ['a','b','c','d','e','f','g','h','i','j','k','l','m',
                     'n','o','p','q','r','s','t','u','v','w','x','y','z'],
    alnumString = a2zString.concat(['0','1','2','3','4','5','6','7','8','9']),
    queryString = alnumString.concat(['+','%','&','.','*','-','_']),
    base64String = alnumString.concat(['+','/','=']).concat(
        a2zString.map(function(c) {
            return c.toUpperCase();
        })
    ),
    schemas = ['ftp','gopher','http','https','ws','wss'],
    supportedLanguages = ['de', 'es', 'fr', 'it', 'no', 'pl', 'pt', 'oc', 'ru', 'sl', 'zh'],
    mimeTypes = ['image/png', 'application/octet-stream'],
    /**
     * character to HTML entity lookup table
     *
     * @see    {@link https://github.com/janl/mustache.js/blob/master/mustache.js#L60}
     */
    entityMap = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
        '/': '&#x2F;',
        '`': '&#x60;',
        '=': '&#x3D;'
    },
    logFile = fs.createWriteStream('test.log'),
    mimeFile = fs.createReadStream('/etc/mime.types'),
    mimeLine = '';

// redirect console messages to log file
console.info = console.warn = console.error = function () {
    logFile.write(Array.prototype.slice.call(arguments).join('') + '\n');
}

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

/**
 * convert all applicable characters to HTML entities
 *
 * @see    {@link https://www.owasp.org/index.php/XSS_(Cross_Site_Scripting)_Prevention_Cheat_Sheet#RULE_.231_-_HTML_Escape_Before_Inserting_Untrusted_Data_into_HTML_Element_Content}
 * @name   htmlEntities
 * @function
 * @param  {string} str
 * @return {string} escaped HTML
 */
exports.htmlEntities = function(str) {
    return String(str).replace(
        /[&<>"'`=\/]/g, function(s) {
            return entityMap[s];
        });
}

// provides random lowercase characters from a to z
exports.jscA2zString = function() {
    return jsc.elements(a2zString);
}

// provides random lowercase alpha numeric characters (a to z and 0 to 9)
exports.jscAlnumString = function() {
    return jsc.elements(alnumString);
}

// provides random characters allowed in GET queries
exports.jscQueryString = function() {
    return jsc.elements(queryString);
}

// provides a random URL schema supported by the whatwg-url library
exports.jscSchemas = function() {
    return jsc.elements(schemas);
}

// provides a random supported language string
exports.jscSupportedLanguages = function() {
    return jsc.elements(supportedLanguages);
}

