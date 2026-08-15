'use strict';
require('../common');

describe('PasteDecrypter', function () {
    it('stops loading after a decryption failure', async function () {
        const clean = globalThis.cleanup();
        const expectedError = new Error('decryption failed');
        let loadingHidden = false,
            shownError;

        PrivateBin.Alert.hideMessages = function () {};
        PrivateBin.Alert.setCustomHandler = function () {};
        PrivateBin.Alert.showLoading = function () {};
        PrivateBin.Alert.hideLoading = function () {
            loadingHidden = true;
        };
        PrivateBin.Alert.showError = function (error) {
            shownError = error;
        };
        PrivateBin.Model.getPasteKey = function () {
            return 'key';
        };
        PrivateBin.Prompt.getPassword = function () {
            return '';
        };
        PrivateBin.TopNav.setRetryCallback = function () {};
        PrivateBin.AttachmentViewer.removeAttachment = function () {};
        PrivateBin.PasteStatus.showRemainingTime = function () {};
        PrivateBin.CopyToClipboard.showKeyboardShortcutHint = function () {};
        PrivateBin.CryptTool.decipher = async function () {
            throw expectedError;
        };

        try {
            PrivateBin.PasteDecrypter.run({
                getCipherData: function () {
                    return [];
                },
                isDiscussionEnabled: function () {
                    return false;
                }
            });
            await new Promise(resolve => setTimeout(resolve, 0));

            assert.strictEqual(shownError, expectedError);
            assert.strictEqual(loadingHidden, true);
        } finally {
            clean();
        }
    });
});
