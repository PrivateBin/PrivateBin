'use strict';
const common = require('../common');
const fc = require('fast-check');
const fs = require('fs');

describe('CryptTool', function () {
    describe('cipher & decipher', function () {
        afterEach(async function () {
            // pause to let async functions conclude
            await new Promise(resolve => setTimeout(resolve, 1900));
        });

        this.timeout(30000);
        it('can en- and decrypt any message', async function () {
            await fc.assert(fc.asyncProperty(
                fc.string(),
                fc.string(),
                fc.string(),
                async function (key, password, message) {
                    const clean = globalThis.cleanup();
                    // ensure zlib is getting loaded
                    PrivateBin.Controller.initZlib();
                    Object.defineProperty(window, 'crypto', {
                        value: new WebCrypto(),
                        configurable: true,
                        enumerable: true,
                        writable: false
                    });
                    global.atob = common.atob;
                    global.btoa = common.btoa;
                    message = message.trim();
                    const cipherMessage = await PrivateBin.CryptTool.cipher(
                            key, password, message, []
                        ),
                        plaintext = await PrivateBin.CryptTool.decipher(
                            key, password, cipherMessage
                        );
                    clean();
                    const result = (message === plaintext);
                    if (!result) console.log(plaintext, cipherMessage);
                    return result;
                }
            ),
            {numRuns: 3});
        });

        // The below static unit tests are included to ensure deciphering of
        // existing pastes still works, even when the format or encoding of
        // messages and passwords changes in the future
        // Of course you can easily decipher the following texts, if you like.
        // Bonus points for finding their sources and hidden meanings.
        it(
            'supports PrivateBin v2 ciphertext with password',
            async function () {
                const clean = globalThis.cleanup();
                // ensure zlib is getting loaded
                PrivateBin.Controller.initZlib();
                Object.defineProperty(window, 'crypto', {
                    value: new WebCrypto(),
                    configurable: true,
                    enumerable: true,
                    writable: false
                });
                global.atob = common.atob;
                global.btoa = common.btoa;

                const paste = await PrivateBin.CryptTool.decipher(
                    atob('GXPVE/FVL2HbR0eT7R4D8syaBDYwENIgU9N//0d+j74='),
                    // -- "That's amazing. I've got the same combination on my luggage."
                    Array.apply(0, Array(6)).map((_,b) => b + 1).join(''),
                    [
                        'W9QvTb1da+IXB4Xmayv73zjK816I1egk4adfhL9DNks8ZSbyMV5SOUCj' +
                        'EPv+Ccy+1nhWFVa9VT4oHi0qZqAECmKoko6oLVdaWFwL1hWMvUdBJfIK' +
                        '13LImrI0l3VGQzXsTKxXgRVHMOn1VqYmE0puo671eUL1g1dx5H1lm2uc' +
                        'jxrOuLWcHMEF+ZqbAx5xwae1aZ8QptBEDL9MiYov7PQpZduxE/jR+mHA' +
                        'RzVJV1ZyGYIIiRBa1VpJvFN+0HSkdgYSU3TnzXw+sO+xvNaNv/dqVls1' +
                        'Lk6mMPninJGxN5ZORw9+eU3sNTzBk0+wrX2lONxUIH3sm5mVZJmdlQ5j' +
                        'sDC+Pe4lAlhbXfeTeNV6WwvQafmaY+6vrPZWzn5ty3/vrJtCcGYOFxN4' +
                        'Ra6GwxO3/+JqKppYJPtPvnVYriObPlcEe+6nlXXDS9EM8W2xIdw2f54I' +
                        'VIA7WHXPj46L6Y/8KpCmlQ5YS7fioWEm3zIbGtzfG8Feu8pT3NVD1wPk' +
                        'blJcb9eCFEIzzWi9G2/e4HdzRyH4fCV7b1L0Bg50/BpCuaC5+v1eZZ8e' +
                        'Ku286YB42sKovgKzio9P2WDAxtbq/35nRFbGhC7cvQOzRdibZqFGFIaq' +
                        'Df0Niz5bvzxKW6eZloBBfm5cxfWdCAX7pTxg/z9Wy/fq3coApwkOS4Ya' +
                        'NoAOvVZo9g==',
                        [['DLAezyPai/y3Gzo8LmUe5A==', 'G550BaMPE+k=', 100000, 256, 128, 'aes', 'gcm', 'zlib'], 'plaintext', 0, 0]
                    ]
                );
                clean();
                const result = typeof paste === 'string' && paste.includes('securely packed in iron');
                if (!result) console.log(paste);
                assert.ok(result);
            }
        );

        it(
            'supports PrivateBin v2 ciphertext no password',
            async function () {
                const clean = globalThis.cleanup();
                // ensure zlib is getting loaded
                PrivateBin.Controller.initZlib();
                Object.defineProperty(window, 'crypto', {
                    value: new WebCrypto(),
                    configurable: true,
                    enumerable: true,
                    writable: false
                });
                global.atob = common.atob;
                global.btoa = common.btoa;

                const paste = await PrivateBin.CryptTool.decipher(
                    atob('RHK8Y2QURMzoDQrbJ5NaAGr3KhIxjepX3fXwXyXQWY0='),
                    '', // no password
                    [
                        'IB87fuvRS6rBY0RqLxQjG5/Ng1cU1K/2IXQejhIpUWgFI6UWldM/7Vdk' +
                        'ZxqlmUHIRWIpCrIToODG+5iVHm0RqeW0P98CRHG82Z+d8AAznp4pOyAt' +
                        'KEa4ifwdoWiSBEbcOBOgggWVUw1nBZt9MHG8iyve+OOH1KPoIv+xVuJV' +
                        'nBW/+nxQ2EbDcnSY4yixXeoEB1bPjSXLHrd+ifst9SbcsTuHCNng2K/t' +
                        'mp3CdocycLttfcfPkgTYvYNC2Gd2oCEZP0+0a2dGfT7nzuGRCFbAx+b9' +
                        'yFNswZHrvlU8CUdrawRSsNN06KZCHkEPUkunjBrdyALLqtn8w2TKA9nf' +
                        '2n+27R5De7dcjOJZbN80PDsAVNOBDCdTuQF+vkUE+254SqQXp2zTAVX5' +
                        '1MjpGgTI/xdSfVdg5vOoqymySPmCV4ZO1sFXPs6AXRg2jhzxhihjxlv8' +
                        '55WY7mSsiU1G3/TBY0Ra+1QzcQ5bSy/ycWEtT72IicM8Hj2w3aF2QwFV' +
                        'sz5tAyFTZD3swxR5bbxQZJ+LEQ==',
                        ['RSSzQHsYsczisktZn6eesg==', 'Dow4nIXOFgI=', 100000, 256, 128, 'aes', 'gcm', 'zlib']
                    ]
                );
                clean();
                const result = typeof paste === 'string' && paste.includes('Stable, opaque, immortal \u2013 all by dint');
                if (!result) console.log(paste);
                assert.ok(result);
            }
        );

        it(
            'supports PrivateBin v2 ciphertext with non-ASCII password and no compression',
            async function () {
                const clean = globalThis.cleanup();
                // ensure zlib is getting loaded
                PrivateBin.Controller.initZlib();
                Object.defineProperty(window, 'crypto', {
                    value: new WebCrypto(),
                    configurable: true,
                    enumerable: true,
                    writable: false
                });
                global.atob = common.atob;
                global.btoa = common.btoa;

                const paste = await PrivateBin.CryptTool.decipher(
                    atob('BgdCS8FLXvEFnQPwkAE93b0urmkaTShdw1Tr5c350Uo='),
                    // key derivation of passwords containing characters above U+007F
                    'Schl\u00fcssel \u94a5\u5319',
                    [
                        'T0qlQhwqt1KtFY6r8iqg5gmP3O8yudwx0YPQk7xU4hyIq2SlsXmGGonq' +
                        '6T4sDIJM6i7caSEZYsE/0KeCq0rCw3CZNaUoL9E54sx4ei17m5X9ikUe' +
                        'feRX3n8M89jbE6vd1qJ13QvbG0mGYhxllfWDnETol0ws2nsfcqVMQJrX' +
                        'nwlm+3PHH9/sqGChLrbTYMHOcUS1ec4/l5qIFfUBXqbO+U5V5nneZRz8' +
                        '+JuPDk5oSiHTBHV2Jyd0Q3fX37O6bpqEz0Qxl54rLEkfUSRfM7RXCJE2' +
                        'wcAaxL9vqGcit/QdBnrJrPyTL8Vdoz289dmx+3EJZEtoW1bSEYQ1mov2' +
                        'c4bv8pEutAEnHf9nbBYZJhWdKlVZkqX5cjM11cPLCuebpQbmw9otVCWX' +
                        'GAqU8safz+Nu4CkUXoAerX8lrKffJEOLOoGvlJjU2qZexfOjrbxP7XJ3' +
                        'lbpddDA4OFVDTG9u99m14T4opmYK/iPwbq6PcI1H9fxzLRwyZjNOoaip' +
                        'b7z6vvOyyl1gwnXN98ZRBys6SChO6tqWATenQgs20UUv6oi+vyBoYhLP' +
                        'DWFfU4ndf4p2d0BRQldYoXEMl+FgkNMcnS2UR6PhBax/BGDgZlMUljil' +
                        'rOxcCM5vd5jSZIWs/IV6pMxOAozymCfjMDfgjE2J6ub00V+92uwu6vsV' +
                        'VlbN1ARFCEp6VX7tN1RNOBTYqlXU6vGbSgXLh7Qng50Pin+/6GKpuHzb' +
                        'spNmEekIqgzlsNc+/V0BrPe6xvhveVyEANNBgqT5NoRju9ioval08lW0' +
                        'OfrFZJ5LEk4jZ9TfMEMej1K5efZMUVP94q+D6fRMFwM=',
                        [['RekNgsDiTSbYsXae8EnjTQ==', 'wsZdI484+Cs=', 100000, 256, 128, 'aes', 'gcm', 'none'], 'plaintext', 0, 0]
                    ]
                );
                clean();
                const result = typeof paste === 'string' && paste.includes('Stable, opaque, immortal \u2013 all by dint');
                if (!result) console.log(paste);
                assert.ok(result);
            }
        );

        it(
            'supports PrivateBin v2 ciphertext with UTF-8 encoded non-ASCII password',
            async function () {
                const clean = globalThis.cleanup();
                // ensure zlib is getting loaded
                PrivateBin.Controller.initZlib();
                Object.defineProperty(window, 'crypto', {
                    value: new WebCrypto(),
                    configurable: true,
                    enumerable: true,
                    writable: false
                });
                global.atob = common.atob;
                global.btoa = common.btoa;

                const paste = await PrivateBin.CryptTool.decipher(
                    atob('58THB9UKSts178TIRMfG1l+SikJC92wqO+zu6RSbzOk='),
                    // key derivation of passwords using their UTF-8 encoding
                    'Schl\u00fcssel \u94a5\u5319',
                    [
                        '2LitLhswv6zFfUg4AzEjxd6OHWq8PJRh0ImRpzMUyVz67kuZbTYXSzZt' +
                        '3DLHg/cvT6GpixTZdtHt7z2JK9TXYkoYDcUTuH/xAkgy/k/6w0COOwMp' +
                        'mUhtYRRTyrhnPEa82QwpVzxZ7zIHRDNLT2J49yLQ44hp564L6zPWQz6R' +
                        'HGuAf1g3Is/dVRNhRaRPyuOrj/PXeA2/KVkpBbWTY2nPCct6pueLiEA/' +
                        'pbtI/oOBOo/DQRgLh9MSpZ4a68H6DtWj9/Y6RINm6zn8cit5bOlUYeC5' +
                        'tpZD2eqpiRZIcwekXcw64Y/dNNQZZ/MD+0ZmWFicBmWlLbuS3WlxeHAH' +
                        'VLGIoZTydTKopZ6ubmQUbI1Fiz3GUDF3q5e9c7vGUO4TVFIKsCyza/8o' +
                        '2SBfKt66DxTyWTetwcW5ql9L+wfxVf6WFLVkbax/HKvHgozPh6YiGHFJ' +
                        'DXeNYenlr0R6zQGjHEi+72+gEgDpHtaZSPYVob+ioNO3EsV9WuxqysK+' +
                        'YEmuw+0cFeF2l9ki69AjbtzNjrFgAsX1QR9an+BtUXFeMekwgkx0NkUd' +
                        'Sla2eOcoIQSR8HFkOF4tCQPxp4Pgd+UFAMYDkv55sCzPxHm3JXbVCzhw' +
                        '7N5zmeEI3wJiqHzLzcRK/JqnaPUinqwcv+skickiAAGUKRZwUmHirCyc' +
                        'WbIqDxlddQ==',
                        ['R9xhVxI3CtY6hp45e1ad0w==', 'LqC0jpwAdps=', 100000, 256, 128, 'aes', 'gcm', 'zlib']
                    ]
                );
                clean();
                const result = typeof paste === 'string' && paste.includes('securely packed in iron');
                if (!result) console.log(paste);
                assert.ok(result);
            }
        );

        it('does not truncate messages', async function () {
            const message = fs.readFileSync('test/compression-sample.txt', 'ascii').trim(),
                clean = globalThis.cleanup();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                configurable: true,
                enumerable: true,
                writable: false
            });
            // ensure zlib is getting loaded
            PrivateBin.Controller.initZlib();
            global.atob = common.atob;
            global.btoa = common.btoa;
            const cipherMessage = await PrivateBin.CryptTool.cipher(
                    'foo', 'bar', message, []
                ),
                plaintext = await PrivateBin.CryptTool.decipher(
                    'foo', 'bar', cipherMessage
                );
            clean();
            if (message !== plaintext) {
                console.log(plaintext, cipherMessage);
            }
            assert.strictEqual(message, plaintext);
        });

        it('authenticates the document and parent IDs of comments', async function () {
            const clean = globalThis.cleanup();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                configurable: true,
                enumerable: true,
                writable: false
            });
            // ensure zlib is getting loaded
            PrivateBin.Controller.initZlib();
            global.atob = common.atob;
            global.btoa = common.btoa;
            const cipherMessage = await PrivateBin.CryptTool.cipher(
                    'foo', 'bar', 'baz', ['5b65a01b43987bc2', '33a5671d11b29d01']
                ),
                plaintext = await PrivateBin.CryptTool.decipher(
                    'foo', 'bar', cipherMessage
                );
            cipherMessage[1][9] = '1111111111111111';
            const tampered = await PrivateBin.CryptTool.decipher(
                'foo', 'bar', cipherMessage
            );
            clean();
            assert.strictEqual(cipherMessage[1].length, 10);
            assert.strictEqual(cipherMessage[1][8], '5b65a01b43987bc2');
            assert.strictEqual(plaintext, 'baz');
            assert.strictEqual(tampered, '');
        });

        it('can en- and decrypt a particular message (#260)', async function () {
            await fc.assert(fc.asyncProperty(
                fc.string(),
                fc.string(),
                async function (key, password) {
                    const message = `
1 subgoal

inv : Assert
expr : Expr
sBody : Instr
deduction : (|- [|inv /\ assertOfExpr expr|] sBody [|inv|])%assert
IHdeduction : (|= [|inv /\ assertOfExpr expr |] sBody [|inv|])%assert
mem : Mem
preInMem : inv mem
m : Mem
n : nat
interpRel : interp (nth_iterate sBody n) (MemElem mem) = CpoElem Mem m
lastIter : interp (nth_iterate sBody n) (MemElem mem) |=e expr_neg expr
notLastIter : forall p : nat,
              p < n -> interp (nth_iterate sBody p) (MemElem mem) |=e expr
isWhile : interp (while expr sBody) (MemElem mem) =
          interp (nth_iterate sBody n) (MemElem mem)

======================== ( 1 / 1 )
conseq_or_bottom inv (interp (nth_iterate sBody n) (MemElem mem))
`;
                    const clean = globalThis.cleanup();
                    // ensure zlib is getting loaded
                    PrivateBin.Controller.initZlib();
                    Object.defineProperty(window, 'crypto', {
                        value: new WebCrypto(),
                        configurable: true,
                        enumerable: true,
                        writable: false
                    });
                    global.atob = common.atob;
                    global.btoa = common.btoa;
                    const cipherMessage = await PrivateBin.CryptTool.cipher(
                            key, password, message, []
                        ),
                        plaintext = await PrivateBin.CryptTool.decipher(
                                key, password, cipherMessage
                        );
                    clean();
                    const result = (message === plaintext);
                    if (!result) console.log(plaintext, cipherMessage);
                    return result;
                }
            ),
            {numRuns: 3});
        });

        it('can en- and decrypt any unicode message and password', async function () {
            await fc.assert(fc.asyncProperty(
                fc.string({unit: 'binary', minLength: 1}),
                fc.string({unit: 'binary'}),
                async function (message, password) {
                    const clean = globalThis.cleanup();
                    // ensure zlib is getting loaded
                    PrivateBin.Controller.initZlib();
                    Object.defineProperty(window, 'crypto', {
                        value: new WebCrypto(),
                        configurable: true,
                        enumerable: true,
                        writable: false
                    });
                    global.atob = common.atob;
                    global.btoa = common.btoa;
                    const key = PrivateBin.CryptTool.getSymmetricKey(),
                        cipherMessage = await PrivateBin.CryptTool.cipher(
                            key, password, message, []
                        ),
                        plaintext = await PrivateBin.CryptTool.decipher(
                            key, password, cipherMessage
                        );
                    clean();
                    const result = (message === plaintext);
                    if (!result) console.log(plaintext, cipherMessage);
                    return result;
                }
            ),
            {numRuns: 3});
        });

        it('does not accept passwords that only share the low byte of each character', async function () {
            const passwordPairs = [
                ['\u013c', '<'],             // U+013C vs <
                ['\u4e2d\u6587', '-\u0087'], // U+4E2D U+6587 vs 0x2D 0x87
                ['\u20ac', '\u00ac']         // U+20AC vs 0xAC
            ];
            for (const [password, lowBytePassword] of passwordPairs) {
                const clean = globalThis.cleanup();
                // ensure zlib is getting loaded
                PrivateBin.Controller.initZlib();
                Object.defineProperty(window, 'crypto', {
                    value: new WebCrypto(),
                    configurable: true,
                    enumerable: true,
                    writable: false
                });
                global.atob = common.atob;
                global.btoa = common.btoa;
                const key = PrivateBin.CryptTool.getSymmetricKey(),
                    cipherMessage = await PrivateBin.CryptTool.cipher(
                        key, password, 'secret', []
                    ),
                    plaintext = await PrivateBin.CryptTool.decipher(
                        key, lowBytePassword, cipherMessage
                    );
                clean();
                assert.strictEqual(
                    plaintext, '',
                    'password ' + JSON.stringify(lowBytePassword) +
                    ' must not decrypt a document encrypted with ' +
                    JSON.stringify(password)
                );
            }
        });
    });

    describe('getSymmetricKey', function () {
        this.timeout(10000);
        let keys = [];

        // the parameter is used to ensure the test is run more then one time
        it('returns random, non-empty keys', () => {
            fc.assert(fc.property(
                fc.integer(),
                function() {
                    const clean = globalThis.cleanup();
                    Object.defineProperty(window, 'crypto', {
                        value: new WebCrypto(),
                        configurable: true,
                        enumerable: true,
                        writable: false
                    });
                    const key = PrivateBin.CryptTool.getSymmetricKey(),
                        result = (key !== '' && keys.indexOf(key) === -1);
                    keys.push(key);
                    clean();
                    return result;
                }
            ),
            {numRuns: 10});
        });
    });
});
