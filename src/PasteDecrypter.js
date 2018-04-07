import $ from 'jquery'

/**
 * (controller) Responsible for decrypting cipherdata and passing data to view.
 *
 * Only decryption, no download.
 *
 * @name PasteDecrypter
 * @class
 */

/**
 * decrypt data or prompts for password in cvase of failure
 *
 * @name   PasteDecrypter.decryptOrPromptPassword
 * @private
 * @function
 * @param  {string} key
 * @param  {string} password - optional, may be an empty string
 * @param  {string} cipherdata
 * @throws {string}
 * @return {false|string} false, when unsuccessful or string (decrypted data)
 */
function decryptOrPromptPassword(key, password, cipherdata)
{
    // try decryption without password
    var plaindata = CryptTool.decipher(key, password, cipherdata);

    // if it fails, request password
    if (plaindata.length === 0 && password.length === 0) {
        // try to get cached password first
        password = Prompt.getPassword();

        // if password is there, re-try
        if (password.length === 0) {
            password = Prompt.requestPassword();
        }
        // recursive
        // note: an infinite loop is prevented as the previous if
        // clause checks whether a password is already set and ignores
        // errors when a password has been passed
        return decryptOrPromptPassword.apply(key, password, cipherdata);
    }

    // if all tries failed, we can only return an error
    if (plaindata.length === 0) {
        throw 'failed to decipher data';
    }

    return plaindata;
}

/**
 * decrypt the actual paste text
 *
 * @name   PasteDecrypter.decryptOrPromptPassword
 * @private
 * @function
 * @param  {object} paste - paste data in object form
 * @param  {string} key
 * @param  {string} password
 * @param  {bool} ignoreError - ignore decryption errors iof set to true
 * @return {bool} whether action was successful
 * @throws {string}
 */
function decryptPaste(paste, key, password, ignoreError)
{
    var plaintext;
    if (ignoreError === true) {
        plaintext = CryptTool.decipher(key, password, paste.data);
    } else {
        try {
            plaintext = decryptOrPromptPassword(key, password, paste.data);
        } catch (err) {
            throw 'failed to decipher paste text: ' + err;
        }
        if (plaintext === false) {
            return false;
        }
    }

    // on success show paste
    PasteViewer.setFormat(paste.meta.formatter);
    PasteViewer.setText(plaintext);
    // trigger to show the text (attachment loaded afterwards)
    PasteViewer.run();

    return true;
}

/**
 * decrypts any attachment
 *
 * @name   PasteDecrypter.decryptAttachment
 * @private
 * @function
 * @param  {object} paste - paste data in object form
 * @param  {string} key
 * @param  {string} password
 * @return {bool} whether action was successful
 * @throws {string}
 */
function decryptAttachment(paste, key, password)
{
    var attachment, attachmentName;

    // decrypt attachment
    try {
        attachment = decryptOrPromptPassword(key, password, paste.attachment);
    } catch (err) {
        throw 'failed to decipher attachment: ' + err;
    }
    if (attachment === false) {
        return false;
    }

    // decrypt attachment name
    if (paste.attachmentname) {
        try {
            attachmentName = decryptOrPromptPassword(key, password, paste.attachmentname);
        } catch (err) {
            throw 'failed to decipher attachment name: ' + err;
        }
        if (attachmentName === false) {
            return false;
        }
    }

    AttachmentViewer.setAttachment(attachment, attachmentName);
    AttachmentViewer.showAttachment();

    return true;
}

/**
 * decrypts all comments and shows them
 *
 * @name   PasteDecrypter.decryptComments
 * @private
 * @function
 * @param  {object} paste - paste data in object form
 * @param  {string} key
 * @param  {string} password
 * @return {bool} whether action was successful
 */
function decryptComments(paste, key, password)
{
    // remove potentially previous discussion
    DiscussionViewer.prepareNewDiscussion();

    // iterate over comments
    for (var i = 0; i < paste.comments.length; ++i) {
        var comment = paste.comments[i];

        DiscussionViewer.addComment(
            comment,
            CryptTool.decipher(key, password, comment.data),
            CryptTool.decipher(key, password, comment.meta.nickname)
        );
    }

    DiscussionViewer.finishDiscussion();
    return true;
}

/**
 * show decrypted text in the display area, including discussion (if open)
 *
 * @name   PasteDecrypter.run
 * @function
 * @param  {Object} [paste] - (optional) object including comments to display (items = array with keys ('data','meta'))
 */
export function run(paste)
{
    Alert.hideMessages();
    Alert.showLoading('Decrypting paste…', 'cloud-download');

    if (typeof paste === 'undefined') {
        paste = $.parseJSON(Model.getCipherData());
    }

    var key = Model.getPasteKey(),
        password = Prompt.getPassword();

    if (PasteViewer.isPrettyPrinted()) {
        // don't decrypt twice
        return;
    }

    // try to decrypt the paste
    try {
        // decrypt attachments
        if (paste.attachment) {
            // try to decrypt paste and if it fails (because the password is
            // missing) return to let JS continue and wait for user
            if (!decryptAttachment(paste, key, password)) {
                return;
            }
            // ignore empty paste, as this is allowed when pasting attachments
            decryptPaste(paste, key, password, true);
        } else {
            decryptPaste(paste, key, password);
        }


        // shows the remaining time (until) deletion
        PasteStatus.showRemainingTime(paste.meta);

        // if the discussion is opened on this paste, display it
        if (paste.meta.opendiscussion) {
            decryptComments(paste, key, password);
        }

        Alert.hideLoading();
        TopNav.showViewButtons();
    } catch(err) {
        Alert.hideLoading();

        // log and show error
        console.error(err);
        Alert.showError('Could not decrypt data (Wrong key?)');
    }
}

/**
 * initialize
 *
 * @name   PasteDecrypter.init
 * @function
 */
export function init()
{
    // nothing yet
}
