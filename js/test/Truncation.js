'use strict';
var common = require('../common');

describe('Compression', function () {
    it('does not truncate messages', function () {
        var message = fs.readFileSync('test/compression-sample.txt', 'utf8');
        assert.strictEqual(
            message,
            Base64.btou( RawDeflate.inflate(
                RawDeflate.deflate( Base64.utob(message) )
            ) )
        );
    });
});

