'use strict';
const common = require('../common');
const fc = require('fast-check');

describe('ServerInteraction', function () {
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
