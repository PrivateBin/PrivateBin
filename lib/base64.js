/*
 * $Id: base64.js,v 1.2 2011/12/27 14:34:49 dankogai Exp dankogai $
 *
 *  Licensed under the MIT license.
 *  http://www.opensource.org/licenses/mit-license.php
 *
 */

(function(global){

if (global.Base64) return;

var b64chars 
    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
var b64tab = function(bin){
    var t = {};
    for (var i = 0, l = bin.length; i < l; i++) t[bin.charAt(i)] = i;
    return t;
}(b64chars);

var sub_toBase64 = function(m){
    var n = (m.charCodeAt(0) << 16)
          | (m.charCodeAt(1) <<  8)
          | (m.charCodeAt(2)      );
    return b64chars.charAt( n >>> 18)
         + b64chars.charAt((n >>> 12) & 63)
         + b64chars.charAt((n >>>  6) & 63)
         + b64chars.charAt( n         & 63);
};

var toBase64 = function(bin){
    if (bin.match(/[^\x00-\xFF]/)) throw 'unsupported character found' ;
    var padlen = 0;
    while(bin.length % 3) {
        bin += '\0';
        padlen++;
    };
    var b64 = bin.replace(/[\x00-\xFF]{3}/g, sub_toBase64);
    if (!padlen) return b64;
    b64 = b64.substr(0, b64.length - padlen);
    while(padlen--) b64 += '=';
    return b64;
};

var btoa = global.btoa || toBase64;

var sub_fromBase64 = function(m){
        var n = (b64tab[ m.charAt(0) ] << 18)
            |   (b64tab[ m.charAt(1) ] << 12)
            |   (b64tab[ m.charAt(2) ] <<  6)
            |   (b64tab[ m.charAt(3) ]);
    return String.fromCharCode(  n >> 16 )
        +  String.fromCharCode( (n >>  8) & 0xff )
        +  String.fromCharCode(  n        & 0xff );
};

var fromBase64 = function(b64){
    b64 = b64.replace(/[^A-Za-z0-9\+\/]/g, '');
    var padlen = 0;
    while(b64.length % 4){
        b64 += 'A';
        padlen++;
    }
    var bin = b64.replace(/[A-Za-z0-9\+\/]{4}/g, sub_fromBase64);
    if (padlen >= 2)
        bin = bin.substring(0, bin.length - [0,0,2,1][padlen]);
    return bin;
};

var atob = global.atob || fromBase64;

var re_char_nonascii = /[^\x00-\x7F]/g;

var sub_char_nonascii = function(m){
    var n = m.charCodeAt(0);
    return n < 0x800 ? String.fromCharCode(0xc0 | (n >>>  6))
                     + String.fromCharCode(0x80 | (n & 0x3f))
        :              String.fromCharCode(0xe0 | ((n >>> 12) & 0x0f))
                     + String.fromCharCode(0x80 | ((n >>>  6) & 0x3f))
                     + String.fromCharCode(0x80 |  (n         & 0x3f))
        ;
};

var utob = function(uni){
    return uni.replace(re_char_nonascii, sub_char_nonascii);
};

var re_bytes_nonascii
    = /[\xC0-\xDF][\x80-\xBF]|[\xE0-\xEF][\x80-\xBF]{2}|[\xF0-\xF7][\x80-\xBF]{3}/g;

var sub_bytes_nonascii = function(m){
    var c0 = m.charCodeAt(0);
    var c1 = m.charCodeAt(1);
    if(c0 < 0xe0){
        return String.fromCharCode(((c0 & 0x1f) << 6) | (c1 & 0x3f));
    }else{
        var c2 = m.charCodeAt(2);
        return String.fromCharCode(
            ((c0 & 0x0f) << 12) | ((c1 & 0x3f) <<  6) | (c2 & 0x3f)
        );
    }
};
    
var btou = function(bin){
    return bin.replace(re_bytes_nonascii, sub_bytes_nonascii);
};

global.Base64 = {
    fromBase64:fromBase64,
    toBase64:toBase64,
    atob:atob,
    btoa:btoa,
    utob:utob,
    btou:btou,
    encode:function(u){ return btoa(utob(u)) },
    encodeURI:function(u){
        return btoa(utob(u)).replace(/[+\/]/g, function(m0){
            return m0 == '+' ? '-' : '_';
        }).replace(/=+$/, '');
    },
    decode:function(a){ 
        return btou(atob(a.replace(/[-_]/g, function(m0){
            return m0 == '-' ? '+' : '/';
        })));
    }
};

})(this);
