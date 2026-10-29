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
