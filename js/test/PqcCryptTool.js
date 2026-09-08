'use strict';
const common = require('../common');

describe('PqcCryptTool', function () {
    this.timeout(30000);

    beforeEach(function () {
        Object.defineProperty(window, 'crypto', {
            value: new WebCrypto(),
            configurable: true
        });
        global.atob = common.atob;
        global.btoa = common.btoa;
    });

    it('wraps and unwraps a 256-bit content key for an ML-KEM-768 recipient', async function () {
        const pair = await PrivateBin.PqcCryptTool.generateKeyPair(),
            contentKey = PrivateBin.CryptTool.getSymmetricKey(),
            envelope = await PrivateBin.PqcCryptTool.wrapKey(contentKey, pair.publicKey),
            recovered = await PrivateBin.PqcCryptTool.unwrapKey(envelope, JSON.stringify(pair));

        assert.strictEqual(pair.algorithm, 'ML-KEM-768');
        assert.strictEqual(Buffer.from(pair.publicKey, 'base64').length, 1184);
        assert.strictEqual(Buffer.from(pair.secretKey, 'base64').length, 2400);
        assert.strictEqual(Buffer.from(envelope.kemct, 'base64').length, 1088);
        assert.strictEqual(Buffer.from(envelope.wrappedkey, 'base64').length, 48);
        assert.strictEqual(recovered, contentKey);
    });

    it('rejects a different recipient secret key', async function () {
        const recipient = await PrivateBin.PqcCryptTool.generateKeyPair(),
            otherRecipient = await PrivateBin.PqcCryptTool.generateKeyPair(),
            envelope = await PrivateBin.PqcCryptTool.wrapKey(
                PrivateBin.CryptTool.getSymmetricKey(),
                recipient.publicKey
            );

        await assert.rejects(
            PrivateBin.PqcCryptTool.unwrapKey(envelope, otherRecipient.secretKey),
            /Unable to decrypt the content key/
        );
    });

    it('authenticates the recipient envelope', async function () {
        const pair = await PrivateBin.PqcCryptTool.generateKeyPair(),
            envelope = await PrivateBin.PqcCryptTool.wrapKey(
                PrivateBin.CryptTool.getSymmetricKey(),
                pair.publicKey
            );
        envelope.kid = 'AAAAAAAAAAAAAAAA';

        await assert.rejects(
            PrivateBin.PqcCryptTool.unwrapKey(envelope, pair.secretKey),
            /Unable to decrypt the content key/
        );
    });

    it('rejects malformed keys before invoking ML-KEM', async function () {
        assert.throws(
            () => PrivateBin.PqcCryptTool.parsePublicKey('not base64'),
            /Invalid ML-KEM public key/
        );
        assert.throws(
            () => PrivateBin.PqcCryptTool.parseSecretKey('{}'),
            /Unsupported ML-KEM key file/
        );
        assert.throws(
            () => PrivateBin.PqcCryptTool.parsePublicKey(null),
            /Invalid ML-KEM key/
        );
        const pair = await PrivateBin.PqcCryptTool.generateKeyPair();
        await assert.rejects(
            PrivateBin.PqcCryptTool.wrapKey('too short', pair.publicKey),
            /Invalid content key length/
        );
    });

    it('creates a decryptable v3 paste envelope through ServerInteraction', async function () {
        const pair = await PrivateBin.PqcCryptTool.generateKeyPair(),
            contentKey = PrivateBin.CryptTool.getSymmetricKey();
        document.body.dataset.compression = 'none';

        PrivateBin.ServerInteraction.prepare();
        PrivateBin.ServerInteraction.setCryptParameters('', contentKey);
        PrivateBin.ServerInteraction.setRecipientPublicKey(pair.publicKey);
        PrivateBin.ServerInteraction.setUnencryptedData('adata', [null, 'plaintext', 0, 0]);
        PrivateBin.ServerInteraction.setUnencryptedData('meta', {'expire': '5min'});
        await PrivateBin.ServerInteraction.setCipherMessage({'paste': 'post-quantum test'});

        const data = PrivateBin.ServerInteraction.getData(),
            recoveredKey = await PrivateBin.PqcCryptTool.unwrapKey(data.adata[4], pair.secretKey),
            plaintext = await PrivateBin.CryptTool.decipher(recoveredKey, '', [data.ct, data.adata]);
        assert.strictEqual(data.v, 3);
        assert.deepStrictEqual(JSON.parse(plaintext), {'paste': 'post-quantum test'});
    });
});
