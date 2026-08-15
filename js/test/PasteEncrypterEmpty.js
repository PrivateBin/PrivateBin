'use strict';
require('../common');

describe('PasteEncrypter empty pastes', function () {
    afterEach(function () {
        globalThis.cleanup();
    });

    it('does not upload without text or attachments', async function () {
        let prepares = 0,
            uploads = 0,
            loadingHides = 0,
            createButtonShows = 0;

        PrivateBin.Controller.hideStatusMessages = function () {};
        PrivateBin.TopNav.hideAllButtons = function () {};
        PrivateBin.TopNav.collapseBar = function () {};
        PrivateBin.TopNav.showCreateButtons = function () { ++createButtonShows; };
        PrivateBin.TopNav.getFileList = function () { return null; };
        PrivateBin.TopNav.getPassword = function () { return ''; };
        PrivateBin.TopNav.getOpenDiscussion = function () { return false; };
        PrivateBin.TopNav.getBurnAfterReading = function () { return false; };
        PrivateBin.TopNav.getExpiration = function () { return '1day'; };
        PrivateBin.Alert.showLoading = function () {};
        PrivateBin.Alert.hideLoading = function () { ++loadingHides; };
        PrivateBin.Editor.getText = function () { return ''; };
        PrivateBin.PasteViewer.getFormat = function () { return 'plaintext'; };
        PrivateBin.PasteViewer.setText = function () {};
        PrivateBin.PasteViewer.setFormat = function () {};
        PrivateBin.AttachmentViewer.getFiles = function () { return []; };
        PrivateBin.AttachmentViewer.hasAttachmentData = function () { return false; };
        PrivateBin.AttachmentViewer.hasAttachment = function () { return false; };
        PrivateBin.AttachmentViewer.getAttachmentsData = function () { return []; };
        PrivateBin.ServerInteraction.prepare = function () { ++prepares; };
        PrivateBin.ServerInteraction.setCryptParameters = function () {};
        PrivateBin.ServerInteraction.setSuccess = function () {};
        PrivateBin.ServerInteraction.setFailure = function () {};
        PrivateBin.ServerInteraction.setUnencryptedData = function () {};
        PrivateBin.ServerInteraction.setCipherMessage = async function () {};
        PrivateBin.ServerInteraction.run = function () { ++uploads; };

        await PrivateBin.PasteEncrypter.sendPaste();

        assert.strictEqual(prepares, 0);
        assert.strictEqual(uploads, 0);
        assert.strictEqual(loadingHides, 1);
        assert.strictEqual(createButtonShows, 1);
    });
});
