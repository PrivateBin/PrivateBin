/**
 * (controller) main PrivateBin logic
 *
 * @name   Controller
 * @param  {object} window
 * @param  {object} document
 * @class
 */

/**
 * hides all status messages no matter which module showed them
 *
 * @name   Controller.hideStatusMessages
 * @function
 */
export function hideStatusMessages()
{
    PasteStatus.hideMessages();
    Alert.hideMessages();
}

/**
 * creates a new paste
 *
 * @name   Controller.newPaste
 * @function
 */
export function newPaste()
{
    // Important: This *must not* run Alert.hideMessages() as previous
    // errors from viewing a paste should be shown.
    TopNav.hideAllButtons();
    Alert.showLoading('Preparing new paste…', 'time');

    PasteStatus.hideMessages();
    PasteViewer.hide();
    Editor.resetInput();
    Editor.show();
    Editor.focusInput();

    TopNav.showCreateButtons();
    Alert.hideLoading();
}

/**
 * shows the loaded paste
 *
 * @name   Controller.showPaste
 * @function
 */
export function showPaste()
{
    try {
        Model.getPasteId();
        Model.getPasteKey();
    } catch (err) {
        console.error(err);

        // missing decryption key (or paste ID) in URL?
        if (window.location.hash.length === 0) {
            Alert.showError('Cannot decrypt paste: Decryption key missing in URL (Did you use a redirector or an URL shortener which strips part of the URL?)');
            return;
        }
    }

    // show proper elements on screen
    PasteDecrypter.run();
}

/**
 * refreshes the loaded paste to show potential new data
 *
 * @name   Controller.refreshPaste
 * @function
 * @param  {function} callback
 */
export function refreshPaste(callback)
{
    // save window position to restore it later
    var orgPosition = $(window).scrollTop();

    Uploader.prepare();
    Uploader.setUrl(Helper.baseUri() + '?' + Model.getPasteId());

    Uploader.setFailure(function (status, data) {
        // revert loading status…
        Alert.hideLoading();
        TopNav.showViewButtons();

        // show error message
        Alert.showError(
            Uploader.parseUploadError(status, data, 'refresh display')
        );
    });
    Uploader.setSuccess(function (status, data) {
        PasteDecrypter.run(data);

        // restore position
        window.scrollTo(0, orgPosition);

        callback();
    });
    Uploader.run();
}

/**
 * clone the current paste
 *
 * @name   Controller.clonePaste
 * @function
 */
export function clonePaste()
{
    TopNav.collapseBar();
    TopNav.hideAllButtons();
    Alert.showLoading('Cloning paste…', 'transfer');

    // hide messages from previous paste
    hideStatusMessages();

    // erase the id and the key in url
    history.pushState({type: 'clone'}, document.title, Helper.baseUri());

    if (AttachmentViewer.hasAttachment()) {
        AttachmentViewer.moveAttachmentTo(
            TopNav.getCustomAttachment(),
            'Cloned: \'%s\''
        );
        TopNav.hideFileSelector();
        AttachmentViewer.hideAttachment();
        // NOTE: it also looks nice without removing the attachment
        // but for a consistent display we remove it…
        AttachmentViewer.hideAttachmentPreview();
        TopNav.showCustomAttachment();

        // show another status message to make the user aware that the
        // file was cloned too!
        Alert.showStatus(
            [
                'The cloned file \'%s\' was attached to this paste.',
                AttachmentViewer.getAttachment()[1]
            ],
            'copy'
        );
    }

    Editor.setText(PasteViewer.getText());
    PasteViewer.hide();
    Editor.show();

    Alert.hideLoading();
    TopNav.showCreateButtons();
}

/**
 * removes a saved paste
 *
 * @name   Controller.removePaste
 * @function
 * @param  {string} pasteId
 * @param  {string} deleteToken
 */
export function removePaste(pasteId, deleteToken) {
    // unfortunately many web servers don't support DELETE (and PUT) out of the box
    // so we use a POST request
    Uploader.prepare();
    Uploader.setUrl(Helper.baseUri() + '?' + pasteId);
    Uploader.setUnencryptedData('deletetoken', deleteToken);

    Uploader.setFailure(function () {
        Alert.showError(
            I18n._('Could not delete the paste, it was not stored in burn after reading mode.')
        );
    });
    Uploader.run();
}

/**
 * application start
 *
 * @name   Controller.init
 * @function
 */
export function init()
{
    // first load translations
    I18n.loadTranslations();

    import('dompurify').then(function (module) {
        var DOMPurify = module.default;

        DOMPurify.setConfig({SAFE_FOR_JQUERY: true});
    });

    // initialize other modules/"classes"
    Alert.init();
    Model.init();
    AttachmentViewer.init();
    DiscussionViewer.init();
    Editor.init();
    PasteDecrypter.init();
    PasteEncrypter.init();
    PasteStatus.init();
    PasteViewer.init();
    Prompt.init();
    TopNav.init();
    UiHelper.init();
    Uploader.init();

    // display an existing paste
    if (Model.hasCipherData()) {
        return showPaste();
    }

    // otherwise create a new paste
    newPaste();
}
