'use strict';
require('../common');

describe('PasteDecrypter paste validation', function () {
    async function decrypt(message) {
        const clean = globalThis.cleanup('', {
            url: 'https://example.com/?0123456789abcdef#key'
        });
        let rendered = false,
            shownError;

        PrivateBin.CryptTool.decipher = async function () {
            return message;
        };
        PrivateBin.Alert.hideMessages = function () {};
        PrivateBin.Alert.setCustomHandler = function () {};
        PrivateBin.Alert.showLoading = function () {};
        PrivateBin.Alert.hideLoading = function () {};
        PrivateBin.Alert.showError = function (error) { shownError = error; };
        PrivateBin.Model.getPasteKey = function () { return 'key'; };
        PrivateBin.Prompt.getPassword = function () { return ''; };
        PrivateBin.TopNav.setRetryCallback = function () {};
        PrivateBin.TopNav.showViewButtons = function () {};
        PrivateBin.TopNav.showEmailButton = function () {};
        PrivateBin.AttachmentViewer.removeAttachment = function () {};
        PrivateBin.PasteStatus.showRemainingTime = function () {};
        PrivateBin.CopyToClipboard.showKeyboardShortcutHint = function () {};
        PrivateBin.PasteViewer.setFormat = function () {};
        PrivateBin.PasteViewer.setText = function () { rendered = true; };
        PrivateBin.PasteViewer.run = function () {};

        const paste = {
            getCipherData: function () { return []; },
            getFormat: function () { return 'plaintext'; },
            getTimeToLive: function () { return 0; },
            isBurnAfterReadingEnabled: function () { return false; },
            isDiscussionEnabled: function () { return false; }
        };

        PrivateBin.PasteDecrypter.run(paste);
        await new Promise(resolve => setTimeout(resolve, 0));

        clean();
        return {rendered, shownError};
    }

    it('rejects a non-string paste body before rendering it', async function () {
        const result = await decrypt('{"paste":{}}');

        assert.strictEqual(result.rendered, false);
        assert(result.shownError instanceof TypeError);
        assert.strictEqual(result.shownError.message, 'Invalid decrypted paste.');
    });

    it('accepts paste objects that shadow hasOwnProperty', async function () {
        const result = await decrypt('{"paste":"text","hasOwnProperty":null}');

        assert.strictEqual(result.rendered, true);
        assert.strictEqual(result.shownError, undefined);
    });
});
