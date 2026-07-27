'use strict';
const common = require('../common');
const fc = require('fast-check');

describe('ServerInteraction', function () {
    describe('run', function () {
        let clean,
            originalFetch,
            requests;

        beforeEach(function () {
            clean = globalThis.cleanup();
            originalFetch = global.fetch;
            requests = [];
            global.fetch = function (url) {
                return new Promise(resolve => {
                    requests.push({url, resolve});
                });
            };
        });

        afterEach(function () {
            global.fetch = originalFetch;
            clean();
        });

        it('keeps success callbacks and keys with their dispatched request', async function () {
            const results = [];

            PrivateBin.ServerInteraction.prepare();
            PrivateBin.ServerInteraction.setUrl('https://example.com/first');
            PrivateBin.ServerInteraction.setCryptParameters('', 'first-key');
            PrivateBin.ServerInteraction.setSuccess(function (status, data) {
                results.push(['first', status, data.id, data.encryptionKey]);
            });
            PrivateBin.ServerInteraction.run();

            PrivateBin.ServerInteraction.prepare();
            PrivateBin.ServerInteraction.setUrl('https://example.com/second');
            PrivateBin.ServerInteraction.setCryptParameters('', 'second-key');
            PrivateBin.ServerInteraction.setSuccess(function (status, data) {
                results.push(['second', status, data.id, data.encryptionKey]);
            });
            PrivateBin.ServerInteraction.run();

            requests[1].resolve({
                ok: true,
                json: async function () { return {status: 0, id: 'second-id'}; }
            });
            requests[0].resolve({
                ok: true,
                json: async function () { return {status: 0, id: 'first-id'}; }
            });
            await new Promise(resolve => setImmediate(resolve));

            assert.deepStrictEqual(results, [
                ['second', 0, 'second-id', 'second-key'],
                ['first', 0, 'first-id', 'first-key']
            ]);
        });

        it('keeps failure callbacks with their dispatched request', async function () {
            const failures = [];

            PrivateBin.ServerInteraction.prepare();
            PrivateBin.ServerInteraction.setUrl('https://example.com/first');
            PrivateBin.ServerInteraction.setFailure(function (status, data) {
                failures.push(['first', status, data.message]);
            });
            PrivateBin.ServerInteraction.run();

            PrivateBin.ServerInteraction.prepare();
            PrivateBin.ServerInteraction.setUrl('https://example.com/second');
            PrivateBin.ServerInteraction.setFailure(function (status, data) {
                failures.push(['second', status, data.message]);
            });
            PrivateBin.ServerInteraction.run();

            requests[0].resolve({
                ok: true,
                json: async function () { return {status: 1, message: 'first-error'}; }
            });
            requests[1].resolve({
                ok: true,
                json: async function () { return {status: 1, message: 'second-error'}; }
            });
            await new Promise(resolve => setImmediate(resolve));

            assert.deepStrictEqual(failures, [
                ['first', 1, 'first-error'],
                ['second', 1, 'second-error']
            ]);
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
