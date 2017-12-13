'use strict';

exports.a2zString = ['a','b','c','d','e','f','g','h','i','j','k','l','m',
                     'n','o','p','q','r','s','t','u','v','w','x','y','z'];
exports.alnumString = exports.a2zString.concat(['0','1','2','3','4','5','6','7','8','9']);
exports.queryString = exports.alnumString.concat(['+','%','&','.','*','-','_']);
exports.base64String = exports.alnumString.concat(['+','/','=']).concat(
    exports.a2zString.map(function(c) {
        return c.toUpperCase();
    })
);
// schemas supported by the whatwg-url library
exports.schemas = ['ftp','gopher','http','https','ws','wss'];
exports.supportedLanguages = ['de', 'es', 'fr', 'it', 'no', 'pl', 'pt', 'oc', 'ru', 'sl', 'zh'];
exports.mimeTypes = ['image/png', 'application/octet-stream'];

global.jsc = require('jsverify');
global.jsdom = require('jsdom-global');
global.cleanup = global.jsdom();
global.fs = require('fs');

/**
 * character to HTML entity lookup table
 *
 * @see    {@link https://github.com/janl/mustache.js/blob/master/mustache.js#L60}
 */
var entityMap = {
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
        exports.mimeTypes.push(line);
    }
}

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

