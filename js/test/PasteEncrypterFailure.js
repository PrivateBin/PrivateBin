'use strict';
require('../common');

describe('PasteEncrypter encryption failures', function () {
    afterEach(function () {
        globalThis.cleanup();
    });

    function stubServerFailure(error) {
        let uploadCount = 0;
        PrivateBin.ServerInteraction.prepare = function () {};
        PrivateBin.ServerInteraction.setCryptParameters = function () {};
        PrivateBin.ServerInteraction.setSuccess = function () {};
        PrivateBin.ServerInteraction.setFailure = function () {};
        PrivateBin.ServerInteraction.setUnencryptedData = function () {};
        PrivateBin.ServerInteraction.setCipherMessage = async function () {
            throw error;
        };
        PrivateBin.ServerInteraction.run = function () {
            ++uploadCount;
        };
        return function () {
            return uploadCount;
        };
    }

    it('does not upload a paste when encryption fails', async function () {
        const error = new Error('encryption failed');
        const getUploadCount = stubServerFailure(error);
        let loadingHidden = 0;
        let createButtonsShown = 0;
        let shownError;

        PrivateBin.Controller.hideStatusMessages = function () {};
        PrivateBin.Alert.showLoading = function () {};
        PrivateBin.Alert.hideLoading = function () { ++loadingHidden; };
        PrivateBin.Alert.showError = function (value) { shownError = value; };
        PrivateBin.TopNav.hideAllButtons = function () {};
        PrivateBin.TopNav.showCreateButtons = function () { ++createButtonsShown; };
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
        PrivateBin.AttachmentViewer.getAttachmentsData = function () { return []; };
        PrivateBin.AttachmentViewer.getFiles = function () { return []; };
        PrivateBin.AttachmentViewer.hasAttachment = function () { return false; };

        await PrivateBin.PasteEncrypter.sendPaste();

        assert.strictEqual(getUploadCount(), 0);
        assert.strictEqual(loadingHidden, 1);
        assert.strictEqual(createButtonsShown, 1);
        assert.strictEqual(shownError, error);
    });

    it('does not upload a comment when encryption fails', async function () {
        const error = new Error('encryption failed');
        const getUploadCount = stubServerFailure(error);
        let loadingHidden = 0;
        let viewButtonsShown = 0;
        let shownError;
        let customHandler = null;

        PrivateBin.DiscussionViewer.getReplyMessage = function () { return 'comment'; };
        PrivateBin.DiscussionViewer.getReplyNickname = function () { return ''; };
        PrivateBin.DiscussionViewer.getReplyCommentId = function () { return undefined; };
        PrivateBin.Alert.hideMessages = function () {};
        PrivateBin.Alert.showLoading = function () {};
        PrivateBin.Alert.hideLoading = function () { ++loadingHidden; };
        PrivateBin.Alert.showError = function (value) { shownError = value; };
        PrivateBin.Alert.setCustomHandler = function (handler) { customHandler = handler; };
        PrivateBin.TopNav.hideAllButtons = function () {};
        PrivateBin.TopNav.showViewButtons = function () { ++viewButtonsShown; };
        PrivateBin.Prompt.getPassword = function () { return ''; };
        PrivateBin.Model.getPasteKey = function () { return 'key'; };
        PrivateBin.Model.getPasteId = function () { return '0123456789abcdef'; };

        await PrivateBin.PasteEncrypter.sendComment();

        assert.strictEqual(getUploadCount(), 0);
        assert.strictEqual(loadingHidden, 1);
        assert.strictEqual(viewButtonsShown, 1);
        assert.strictEqual(shownError, error);
        assert.strictEqual(customHandler, null);
    });
});
