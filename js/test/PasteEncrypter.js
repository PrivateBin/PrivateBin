'use strict';
require('../common');

describe('PasteEncrypter', function () {
    describe('sendPaste', function () {
        let restores,
            cipherMessage,
            serverRuns,
            loadingHides,
            createButtonShows;

        const stub = function (object, method, replacement) {
            restores.push([object, method, object[method]]);
            object[method] = replacement;
        };

        beforeEach(function () {
            restores = [];
            cipherMessage = undefined;
            serverRuns = 0;
            loadingHides = 0;
            createButtonShows = 0;

            stub(PrivateBin.Controller, 'hideStatusMessages', function () {});
            stub(PrivateBin.TopNav, 'hideAllButtons', function () {});
            stub(PrivateBin.TopNav, 'collapseBar', function () {});
            stub(PrivateBin.TopNav, 'showCreateButtons', function () { ++createButtonShows; });
            stub(PrivateBin.TopNav, 'getFileList', function () { return null; });
            stub(PrivateBin.TopNav, 'getPassword', function () { return ''; });
            stub(PrivateBin.TopNav, 'getOpenDiscussion', function () { return false; });
            stub(PrivateBin.TopNav, 'getBurnAfterReading', function () { return false; });
            stub(PrivateBin.TopNav, 'getExpiration', function () { return '5min'; });
            stub(PrivateBin.Alert, 'showLoading', function () {});
            stub(PrivateBin.Alert, 'hideLoading', function () { ++loadingHides; });
            stub(PrivateBin.Alert, 'showStatus', function () {});
            stub(PrivateBin.Alert, 'showError', function () {});
            stub(PrivateBin.Editor, 'getText', function () { return ''; });
            stub(PrivateBin.PasteViewer, 'getFormat', function () { return 'plaintext'; });
            stub(PrivateBin.PasteViewer, 'setText', function () {});
            stub(PrivateBin.PasteViewer, 'setFormat', function () {});
            stub(PrivateBin.AttachmentViewer, 'getFiles', function () { return []; });
            stub(PrivateBin.AttachmentViewer, 'hasAttachmentData', function () { return false; });
            stub(PrivateBin.AttachmentViewer, 'getAttachmentsData', function () { return []; });
            stub(PrivateBin.AttachmentViewer, 'hasAttachment', function () { return true; });
            stub(PrivateBin.ServerInteraction, 'prepare', function () {});
            stub(PrivateBin.ServerInteraction, 'setCryptParameters', function () {});
            stub(PrivateBin.ServerInteraction, 'setSuccess', function () {});
            stub(PrivateBin.ServerInteraction, 'setFailure', function () {});
            stub(PrivateBin.ServerInteraction, 'setUnencryptedData', function () {});
            stub(PrivateBin.ServerInteraction, 'setCipherMessage', function (message) {
                cipherMessage = message;
                return Promise.resolve();
            });
            stub(PrivateBin.ServerInteraction, 'run', function () { ++serverRuns; });
        });

        afterEach(function () {
            restores.reverse().forEach(function (restore) {
                restore[0][restore[1]] = restore[2];
            });
        });

        it('preserves cloned attachments that are already data URLs', async () => {
            const dataUrl = 'data:text/plain;base64,SGVsbG8=';
            stub(PrivateBin.AttachmentViewer, 'getAttachments', function () {
                return [[dataUrl, 'hello.txt']];
            });

            await PrivateBin.PasteEncrypter.sendPaste();

            assert.deepStrictEqual(cipherMessage.attachment, [dataUrl]);
            assert.deepStrictEqual(cipherMessage.attachment_name, ['hello.txt']);
            assert.strictEqual(serverRuns, 1);
        });

        it('restores the editor when a cloned blob cannot be retrieved', async () => {
            stub(PrivateBin.AttachmentViewer, 'getAttachments', function () {
                return [['blob:https://example.com/missing', 'missing.txt']];
            });
            stub(globalThis, 'fetch', function () {
                return Promise.resolve({ok: false, status: 404});
            });

            await PrivateBin.PasteEncrypter.sendPaste();

            assert.strictEqual(cipherMessage, undefined);
            assert.strictEqual(serverRuns, 0);
            assert.strictEqual(loadingHides, 1);
            assert.strictEqual(createButtonShows, 1);
        });
    });
});
