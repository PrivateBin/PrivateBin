'use strict';
const common = require('../common');

describe('CryptTool', function () {
    describe('cipher & decipher', function () {
        afterEach(async function () {
            // pause to let async functions conclude
            await new Promise(resolve => setTimeout(resolve, 1900));
        });

        this.timeout(30000);
        it('can en- and decrypt any message', function () {
            jsc.assert(jsc.forall(
                'string',
                'string',
                'string',
                async function (key, password, message) {
                    const clean = jsdom();
                    // ensure zlib is getting loaded
                    $.PrivateBin.Controller.initZ();
                    Object.defineProperty(window, 'crypto', {
                        value: new WebCrypto(),
                        writeable: false
                    });
                    global.atob = common.atob;
                    global.btoa = common.btoa;
                    message = message.trim();
                    const cipherMessage = await $.PrivateBin.CryptTool.cipher(
                            key, password, message, []
                        ),
                        plaintext = await $.PrivateBin.CryptTool.decipher(
                            key, password, cipherMessage
                        );
                    clean();
                    const result = (message === plaintext);
                    if (!result) console.log(plaintext, cipherMessage);
                    return result;
                }
            ),
            {tests: 3});
        });

        it('does not truncate messages', async function () {
            const message = fs.readFileSync('test/compression-sample.txt', 'ascii').trim(),
                clean = jsdom();
            Object.defineProperty(window, 'crypto', {
                value: new WebCrypto(),
                writeable: false
            });
            // ensure zlib is getting loaded
            $.PrivateBin.Controller.initZ();
            global.atob = common.atob;
            global.btoa = common.btoa;
            const cipherMessage = await $.PrivateBin.CryptTool.cipher(
                    'foo', 'bar', message, []
                ),
                plaintext = await $.PrivateBin.CryptTool.decipher(
                    'foo', 'bar', cipherMessage
                );
            clean();
            if (message !== plaintext) {
                console.log(plaintext, cipherMessage);
            }
            assert.strictEqual(message, plaintext);
        });

        it('can en- and decrypt a particular message (#260)', function () {
            jsc.assert(jsc.forall(
                'string',
                'string',
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
                    const clean = jsdom();
                    // ensure zlib is getting loaded
                    $.PrivateBin.Controller.initZ();
                    Object.defineProperty(window, 'crypto', {
                        value: new WebCrypto(),
                        writeable: false
                    });
                    const cipherMessage = await $.PrivateBin.CryptTool.cipher(
                            key, password, message, []
                        ),
                        plaintext = await $.PrivateBin.CryptTool.decipher(
                                key, password, cipherMessage
                        );
                    clean();
                    const result = (message === plaintext);
                    if (!result) console.log(plaintext, cipherMessage);
                    return result;
                }
            ),
            {tests: 3});
        });
    });

    describe('getSymmetricKey', function () {
        this.timeout(10000);
        let keys = [];

        // the parameter is used to ensure the test is run more then one time
        it('returns random, non-empty keys', function () {
            jsc.assert(jsc.forall(
                'integer',
                function(counter) {
                    const clean = jsdom();
                    Object.defineProperty(window, 'crypto', {
                        value: new WebCrypto(),
                        writeable: false
                    });
                    const key = $.PrivateBin.CryptTool.getSymmetricKey(),
                        result = (key !== '' && keys.indexOf(key) === -1);
                    keys.push(key);
                    clean();
                    return result;
                }
            ),
            {tests: 10});
        });
    });
});
