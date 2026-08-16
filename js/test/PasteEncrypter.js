'use strict';
require('../common');

describe('PasteEncrypter', function () {
    afterEach(function () {
        globalThis.cleanup();
    });

    it('waits for selected attachments before encrypting the paste', async function () {
        document.body.innerHTML = (
            '<div id="attachmentPreview"></div>' +
            '<div id="attachment"></div>' +
            '<input id="file" type="file">' +
            '<div id="dragAndDropFileName"></div>' +
            '<div id="dropzone"></div>'
        );

        const pendingReaders = [];
        class DelayedFileReader {
            readAsDataURL() {
                pendingReaders.push(this);
            }
        }
        global.FileReader = DelayedFileReader;
        window.FileReader = DelayedFileReader;

        PrivateBin.TopNav.isAttachmentReadonly = function () { return false; };
        PrivateBin.TopNav.highlightFileupload = function () {};
        PrivateBin.AttachmentViewer.init();

        const file = {name: 'slow.txt'};
        const dropEvent = new window.Event('drop', {
            bubbles: true,
            cancelable: true
        });
        Object.defineProperty(dropEvent, 'dataTransfer', {
            value: {files: [file]}
        });
        document.dispatchEvent(dropEvent);
        assert.strictEqual(pendingReaders.length, 1);

        let encryptedMessage;
        let uploadCount = 0;
        PrivateBin.Controller.hideStatusMessages = function () {};
        PrivateBin.Alert.showLoading = function () {};
        PrivateBin.Alert.hideLoading = function () {};
        PrivateBin.Alert.showError = function () {};
        PrivateBin.TopNav.hideAllButtons = function () {};
        PrivateBin.TopNav.showCreateButtons = function () {};
        PrivateBin.TopNav.collapseBar = function () {};
        PrivateBin.TopNav.getFileList = function () { return null; };
        PrivateBin.TopNav.getPassword = function () { return ''; };
        PrivateBin.TopNav.getOpenDiscussion = function () { return false; };
        PrivateBin.TopNav.getBurnAfterReading = function () { return false; };
        PrivateBin.TopNav.getExpiration = function () { return '1day'; };
        PrivateBin.Editor.getText = function () { return 'paste'; };
        PrivateBin.PasteViewer.getFormat = function () { return 'plaintext'; };
        PrivateBin.PasteViewer.setText = function () {};
        PrivateBin.PasteViewer.setFormat = function () {};
        PrivateBin.ServerInteraction.prepare = function () {};
        PrivateBin.ServerInteraction.setCryptParameters = function () {};
        PrivateBin.ServerInteraction.setSuccess = function () {};
        PrivateBin.ServerInteraction.setFailure = function () {};
        PrivateBin.ServerInteraction.setUnencryptedData = function () {};
        PrivateBin.ServerInteraction.setCipherMessage = async function (message) {
            encryptedMessage = message;
        };
        PrivateBin.ServerInteraction.run = function () {
            ++uploadCount;
        };

        const sending = PrivateBin.PasteEncrypter.sendPaste();
        await new Promise(resolve => setImmediate(resolve));
        assert.strictEqual(uploadCount, 0);

        pendingReaders[0].onload({
            target: {
                result: 'data:text/plain;base64,cGFzdGU='
            }
        });
        await sending;

        assert.strictEqual(uploadCount, 1);
        assert.deepStrictEqual(encryptedMessage.attachment, [
            'data:text/plain;base64,cGFzdGU='
        ]);
        assert.deepStrictEqual(encryptedMessage.attachment_name, ['slow.txt']);
    });
});
