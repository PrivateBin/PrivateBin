'use strict';
require('../common');

describe('ServerInteraction', function () {
    describe('prepare', function () {
        afterEach(async function () {
            // pause to let async functions conclude
            await new Promise(resolve => setTimeout(resolve, 1900));
        });
        this.timeout(30000);
        it('can prepare an encrypted paste', function () {
            jsc.assert(jsc.forall(
                'string',
                'string',
                'string',
                async function (key, password, message) {
                    // pause to let async functions conclude
                    await new Promise(resolve => setTimeout(resolve, 300));
                    let clean = jsdom();
                    window.crypto = new WebCrypto();
                    message = message.trim();

                    $.PrivateBin.ServerInteraction.prepare();
                    $.PrivateBin.ServerInteraction.setCryptParameters(password, key);
                    $.PrivateBin.ServerInteraction.setUnencryptedData('adata', [
                        // encryption parameters defined by CryptTool, format, discussion, burn after reading
                        null, 'plaintext', 0, 0
                    ]);
                    $.PrivateBin.ServerInteraction.setUnencryptedData('meta', {'expire': '5min'});
                    await $.PrivateBin.ServerInteraction.setCipherMessage({'paste': message});
                    //console.log($.PrivateBin.ServerInteraction.getData());
                    clean();
                    // TODO currently not testing anything and just used to generate v2 pastes for starting development of server side v2 implementation
                    return true;
                }
            ),
            {tests: 3});
        });
    });
});
