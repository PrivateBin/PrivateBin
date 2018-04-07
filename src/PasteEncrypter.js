/**
 * (controller) Responsible for encrypting paste and sending it to server.
 *
 * Does upload, encryption is done transparently by Uploader.
 *
 * @name PasteEncrypter
 * @class
 */

var requirementsChecked = false;

/**
 * checks whether there is a suitable amount of entrophy
 *
 * @name PasteEncrypter.checkRequirements
 * @private
 * @function
 * @param {function} retryCallback - the callback to execute to retry the upload
 * @return {bool}
 */
function checkRequirements(retryCallback) {
    // skip double requirement checks
    if (requirementsChecked === true) {
        return true;
    }

    if (!CryptTool.isEntropyReady()) {
        // display a message and wait
        Alert.showStatus('Please move your mouse for more entropy…');

        CryptTool.addEntropySeedListener(retryCallback);
        return false;
    }

    requirementsChecked = true;

    return true;
}

/**
 * called after successful paste upload
 *
 * @name PasteEncrypter.showCreatedPaste
 * @private
 * @function
 * @param {int} status
 * @param {object} data
 */
function showCreatedPaste(status, data) {
    Alert.hideLoading();

    var url = Helper.baseUri() + '?' + data.id + '#' + data.encryptionKey,
        deleteUrl = Helper.baseUri() + '?pasteid=' + data.id + '&deletetoken=' + data.deletetoken;

    Alert.hideMessages();

    // show notification
    PasteStatus.createPasteNotification(url, deleteUrl);

    // show new URL in browser bar
    history.pushState({type: 'newpaste'}, document.title, url);

    TopNav.showViewButtons();
    TopNav.hideRawButton();
    Editor.hide();

    // parse and show text
    // (preparation already done in sendPaste())
    PasteViewer.run();
}

/**
 * called after successful comment upload
 *
 * @name PasteEncrypter.showUploadedComment
 * @private
 * @function
 * @param {int} status
 * @param {object} data
 */
function showUploadedComment(status, data) {
    // show success message
    Alert.showStatus('Comment posted.');

    // reload paste
    Controller.refreshPaste(function () {
        // highlight sent comment
        DiscussionViewer.highlightComment(data.id, true);
        // reset error handler
        Alert.setCustomHandler(null);
    });
}

/**
 * adds attachments to the Uploader
 *
 * @name PasteEncrypter.encryptAttachments
 * @private
 * @function
 * @param {File|null|undefined} file - optional, falls back to cloned attachment
 * @param {function} callback - excuted when action is successful
 */
function encryptAttachments(file, callback) {
    if (typeof file !== 'undefined' && file !== null) {
        // check file reader requirements for upload
        if (typeof FileReader === 'undefined') {
            Alert.showError('Your browser does not support uploading encrypted files. Please use a newer browser.');
            // cancels process as it does not execute callback
            return;
        }

        var reader = new FileReader();

        // closure to capture the file information
        reader.onload = function(event) {
            Uploader.setData('attachment', event.target.result);
            Uploader.setData('attachmentname', file.name);

            // run callback
            return callback();
        };

        // actually read first file
        reader.readAsDataURL(file);
    } else if (AttachmentViewer.hasAttachment()) {
        // fall back to cloned part
        var attachment = AttachmentViewer.getAttachment();

        Uploader.setData('attachment', attachment[0]);
        Uploader.setData('attachmentname', attachment[1]);
        return callback();
    } else {
        // if there are no attachments, this is of course still successful
        return callback();
    }
}

/**
 * send a reply in a discussion
 *
 * @name   PasteEncrypter.sendComment
 * @function
 */
export function sendComment()
{
    Alert.hideMessages();
    Alert.setCustomHandler(DiscussionViewer.handleNotification);

    // UI loading state
    TopNav.hideAllButtons();
    Alert.showLoading('Sending comment…', 'cloud-upload');

    // get data
    var plainText = DiscussionViewer.getReplyMessage(),
        nickname = DiscussionViewer.getReplyNickname(),
        parentid = DiscussionViewer.getReplyCommentId();

    // do not send if there is no data
    if (plainText.length === 0) {
        // revert loading status…
        Alert.hideLoading();
        Alert.setCustomHandler(null);
        TopNav.showViewButtons();
        return;
    }

    // check entropy
    if (!checkRequirements(function () {
        sendComment();
    })) {
        return; // to prevent multiple executions
    }

    // prepare Uploader
    Uploader.prepare();
    Uploader.setCryptParameters(Prompt.getPassword(), Model.getPasteKey());

    // set success/fail functions
    Uploader.setSuccess(showUploadedComment);
    Uploader.setFailure(function (status, data) {
        // revert loading status…
        Alert.hideLoading();
        TopNav.showViewButtons();

        // show error message
        Alert.showError(
            Uploader.parseUploadError(status, data, 'post comment')
        );

        // reset error handler
        Alert.setCustomHandler(null);
    });

    // fill it with unencrypted params
    Uploader.setUnencryptedData('pasteid', Model.getPasteId());
    if (typeof parentid === 'undefined') {
        // if parent id is not set, this is the top-most comment, so use
        // paste id as parent, as the root element of the discussion tree
        Uploader.setUnencryptedData('parentid', Model.getPasteId());
    } else {
        Uploader.setUnencryptedData('parentid', parentid);
    }

    // encrypt data
    Uploader.setData('data', plainText);

    if (nickname.length > 0) {
        Uploader.setData('nickname', nickname);
    }

    Uploader.run();
}

/**
 * sends a new paste to server
 *
 * @name   PasteEncrypter.sendPaste
 * @function
 */
export function sendPaste()
{
    // hide previous (error) messages
    Controller.hideStatusMessages();

    // UI loading state
    TopNav.hideAllButtons();
    Alert.showLoading('Sending paste…', 'cloud-upload');
    TopNav.collapseBar();

    // get data
    var plainText = Editor.getText(),
        format = PasteViewer.getFormat(),
        files = TopNav.getFileList();

    // do not send if there is no data
    if (plainText.length === 0 && files === null) {
        // revert loading status…
        Alert.hideLoading();
        TopNav.showCreateButtons();
        return;
    }

    // check entropy
    if (!checkRequirements(function () {
        sendPaste();
    })) {
        return; // to prevent multiple executions
    }

    // prepare Uploader
    Uploader.prepare();
    Uploader.setCryptParameters(TopNav.getPassword());

    // set success/fail functions
    Uploader.setSuccess(showCreatedPaste);
    Uploader.setFailure(function (status, data) {
        // revert loading status…
        Alert.hideLoading();
        TopNav.showCreateButtons();

        // show error message
        Alert.showError(
            Uploader.parseUploadError(status, data, 'create paste')
        );
    });

    // fill it with unencrypted submitted options
    Uploader.setUnencryptedBulkData({
        expire:           TopNav.getExpiration(),
        formatter:        format,
        burnafterreading: TopNav.getBurnAfterReading() ? 1 : 0,
        opendiscussion:   TopNav.getOpenDiscussion() ? 1 : 0
    });

    // prepare PasteViewer for later preview
    PasteViewer.setText(plainText);
    PasteViewer.setFormat(format);

    // encrypt cipher data
    Uploader.setData('data', plainText);

    // encrypt attachments
    encryptAttachments(
        files === null ? null : files[0],
        function () {
            // send data
            Uploader.run();
        }
    );
}

/**
 * initialize
 *
 * @name   PasteEncrypter.init
 * @function
 */
export function init()
{
    // nothing yet
}
