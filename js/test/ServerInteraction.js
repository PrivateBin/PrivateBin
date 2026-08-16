'use strict';
const common = require('../common');
const fc = require('fast-check');

describe('ServerInteraction', function () {
    describe('run callbacks', function () {
        let clean,
            originalFetch,
            originalConsoleError;

        beforeEach(function () {
            clean = globalThis.cleanup();
            originalFetch = global.fetch;
            originalConsoleError = console.error;
            console.error = function () {};
        });

        afterEach(function () {
            global.fetch = originalFetch;
            console.error = originalConsoleError;
            clean();
        });

        it('does not reclassify success callback errors as server failures', async function () {
            let successCalls = 0,
                failureCalls = 0;
            global.fetch = async function () {
                return {
                    ok: true,
                    json: async function () {
                        return {status: 0};
                    }
                };
            };
            PrivateBin.ServerInteraction.prepare();
            PrivateBin.ServerInteraction.setSuccess(function () {
                ++successCalls;
                throw new Error('render failed');
            });
            PrivateBin.ServerInteraction.setFailure(function () {
                ++failureCalls;
            });

            PrivateBin.ServerInteraction.run();
            await new Promise(resolve => setImmediate(resolve));

            assert.strictEqual(successCalls, 1);
            assert.strictEqual(failureCalls, 0);
        });

        it('does not invoke a throwing failure callback twice', async function () {
            let failureCalls = 0;
            global.fetch = async function () {
                return {
                    ok: true,
                    json: async function () {
                        return {status: 1, message: 'rejected'};
                    }
                };
            };
            PrivateBin.ServerInteraction.prepare();
            PrivateBin.ServerInteraction.setFailure(function () {
                ++failureCalls;
                if (failureCalls === 1) {
                    throw new Error('error rendering failure');
                }
            });

            PrivateBin.ServerInteraction.run();
            await new Promise(resolve => setImmediate(resolve));

            assert.strictEqual(failureCalls, 1);
        });
    });

    describe('prepare', function () {
        afterEach(async function () {
            // pause to let async functions conclude
            await new Promise(resolve => setTimeout(resolve, 1900));
        });
        this.timeout(30000);
        it('can prepare an encrypted document', async function () {
            await fc.assert(fc.asyncProperty(
                fc.string(),
                fc.string(),
                fc.string(),
                async function (key, password, message) {
                    // pause to let async functions conclude
                    await new Promise(resolve => setTimeout(resolve, 300));
                    let clean = globalThis.cleanup();
                    Object.defineProperty(window, 'crypto', {
                        value: new WebCrypto(),
                        configurable: true,
                        enumerable: true,
                        writable: false
                    });
                    global.atob = common.atob;
                    global.btoa = common.btoa;
                    message = message.trim();

                    PrivateBin.ServerInteraction.prepare();
                    PrivateBin.ServerInteraction.setCryptParameters(password, key);
                    PrivateBin.ServerInteraction.setUnencryptedData('adata', [
                        // encryption parameters defined by CryptTool, format, discussion, burn after reading
                        null, 'plaintext', 0, 0
                    ]);
                    PrivateBin.ServerInteraction.setUnencryptedData('meta', {'expire': '5min'});
                    await PrivateBin.ServerInteraction.setCipherMessage({'paste': message});
                    //console.log(PrivateBin.ServerInteraction.getData());
                    clean();
                    // TODO currently not testing anything and just used to generate v2 pastes for starting development of server side v2 implementation
                    return true;
                }
            ),
            {numRuns: 3});
        });
    });
});
