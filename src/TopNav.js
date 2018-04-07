import $ from 'jquery'

/**
 * Manage top (navigation) bar
 *
 * @name   TopNav
 * @param  {object} window
 * @param  {object} document
 * @class
 */

var createButtonsDisplayed = false;
var viewButtonsDisplayed = false;

var $attach,
    $burnAfterReading,
    $burnAfterReadingOption,
    $cloneButton,
    $customAttachment,
    $expiration,
    $fileRemoveButton,
    $fileWrap,
    $formatter,
    $newButton,
    $openDiscussion,
    $openDiscussionOption,
    $password,
    $passwordInput,
    $rawTextButton,
    $qrCodeLink,
    $sendButton;

var pasteExpiration = '1week';

/**
 * set the expiration on bootstrap templates in dropdown
 *
 * @name   TopNav.updateExpiration
 * @private
 * @function
 * @param  {Event} event
 */
function updateExpiration(event)
{
    // get selected option
    var target = $(event.target);

    // update dropdown display and save new expiration time
    $('#pasteExpirationDisplay').text(target.text());
    pasteExpiration = target.data('expiration');

    event.preventDefault();
}

/**
 * set the format on bootstrap templates in dropdown
 *
 * @name   TopNav.updateFormat
 * @private
 * @function
 * @param  {Event} event
 */
function updateFormat(event)
{
    // get selected option
    var $target = $(event.target);

    // update dropdown display and save new format
    var newFormat = $target.data('format');
    $('#pasteFormatterDisplay').text($target.text());
    PasteViewer.setFormat(newFormat);

    // update preview
    if (Editor.isPreview()) {
        PasteViewer.run();
    }

    event.preventDefault();
}

/**
 * when "burn after reading" is checked, disable discussion
 *
 * @name   TopNav.changeBurnAfterReading
 * @private
 * @function
 */
function changeBurnAfterReading()
{
    if ($burnAfterReading.is(':checked')) {
        $openDiscussionOption.addClass('buttondisabled');
        $openDiscussion.prop('checked', false);

        // if button is actually disabled, force-enable it and uncheck other button
        $burnAfterReadingOption.removeClass('buttondisabled');
    } else {
        $openDiscussionOption.removeClass('buttondisabled');
    }
}

/**
 * when discussion is checked, disable "burn after reading"
 *
 * @name   TopNav.changeOpenDiscussion
 * @private
 * @function
 */
function changeOpenDiscussion()
{
    if ($openDiscussion.is(':checked')) {
        $burnAfterReadingOption.addClass('buttondisabled');
        $burnAfterReading.prop('checked', false);

        // if button is actually disabled, force-enable it and uncheck other button
        $openDiscussionOption.removeClass('buttondisabled');
    } else {
        $burnAfterReadingOption.removeClass('buttondisabled');
    }
}

/**
 * return raw text
 *
 * @name   TopNav.rawText
 * @private
 * @function
 */
function rawText()
{
    TopNav.hideAllButtons();
    Alert.showLoading('Showing raw text…', 'time');
    var paste = PasteViewer.getText();

    // push a new state to allow back navigation with browser back button
    history.pushState(
        {type: 'raw'},
        document.title,
        // recreate paste URL
        Helper.baseUri() + '?' + Model.getPasteId() + '#' +
        Model.getPasteKey()
    );

    // we use text/html instead of text/plain to avoid a bug when
    // reloading the raw text view (it reverts to type text/html)
    import('dompurify').then(function (module) {
        var DOMPurify = module.default;

        var $head = $('head').children().not('noscript, script, link[type="text/css"]');
        var newDoc = document.open('text/html', 'replace');
        newDoc.write('<!DOCTYPE html><html><head>');
        for (var i = 0; i < $head.length; i++) {
            newDoc.write($head[i].outerHTML);
        }
        newDoc.write('</head><body><pre>' + DOMPurify.sanitize(paste) + '</pre></body></html>');
        newDoc.close();
    });
}

/**
 * saves the language in a cookie and reloads the page
 *
 * @name   TopNav.setLanguage
 * @private
 * @function
 * @param  {Event} event
 */
function setLanguage(event)
{
    document.cookie = 'lang=' + $(event.target).data('lang');
    UiHelper.reloadHome();
}

/**
 * hides all messages and creates a new paste
 *
 * @name   TopNav.clickNewPaste
 * @private
 * @function
 */
function clickNewPaste()
{
    Controller.hideStatusMessages();
    Controller.newPaste();
}

/**
 * removes the existing attachment
 *
 * @name   TopNav.removeAttachment
 * @private
 * @function
 * @param  {Event} event
 */
function removeAttachment(event)
{
    // if custom attachment is used, remove it first
    if (!$customAttachment.hasClass('hidden')) {
        AttachmentViewer.removeAttachment();
        $customAttachment.addClass('hidden');
        $fileWrap.removeClass('hidden');
    }

    // our up-to-date jQuery can handle it :)
    $fileWrap.find('input').val('');

    // pevent '#' from appearing in the URL
    event.preventDefault();
}

/**
 * Shows the QR code of the current paste (URL).
 *
 * @name   TopNav.displayQrCode
 * @function
 */
function displayQrCode()
{
    import('kjua').then(function (module) {
        var kjua = module.default;

        var qrCanvas = kjua({
            render: 'canvas',
            text: window.location.href
        });
        $('#qrcode-display').html(qrCanvas);
    });
}

/**
 * Shows all elements belonging to viwing an existing pastes
 *
 * @name   TopNav.showViewButtons
 * @function
 */
export function showViewButtons()
{
    if (viewButtonsDisplayed) {
        console.warn('showViewButtons: view buttons are already displayed');
        return;
    }

    $newButton.removeClass('hidden');
    $cloneButton.removeClass('hidden');
    $rawTextButton.removeClass('hidden');
    $qrCodeLink.removeClass('hidden');

    viewButtonsDisplayed = true;
}

/**
 * Hides all elements belonging to existing pastes
 *
 * @name   TopNav.hideViewButtons
 * @function
 */
export function hideViewButtons()
{
    if (!viewButtonsDisplayed) {
        console.warn('hideViewButtons: view buttons are already hidden');
        return;
    }

    $newButton.addClass('hidden');
    $cloneButton.addClass('hidden');
    $rawTextButton.addClass('hidden');
    $qrCodeLink.addClass('hidden');

    viewButtonsDisplayed = false;
}

/**
 * Hides all elements belonging to existing pastes
 *
 * @name   TopNav.hideAllButtons
 * @function
 */
export function hideAllButtons()
{
    hideViewButtons();
    hideCreateButtons();
}

/**
 * shows all elements needed when creating a new paste
 *
 * @name   TopNav.showCreateButtons
 * @function
 */
export function showCreateButtons()
{
    if (createButtonsDisplayed) {
        console.warn('showCreateButtons: create buttons are already displayed');
        return;
    }

    $sendButton.removeClass('hidden');
    $expiration.removeClass('hidden');
    $formatter.removeClass('hidden');
    $burnAfterReadingOption.removeClass('hidden');
    $openDiscussionOption.removeClass('hidden');
    $newButton.removeClass('hidden');
    $password.removeClass('hidden');
    $attach.removeClass('hidden');

    createButtonsDisplayed = true;
}

/**
 * shows all elements needed when creating a new paste
 *
 * @name   TopNav.hideCreateButtons
 * @function
 */
export function hideCreateButtons()
{
    if (!createButtonsDisplayed) {
        console.warn('hideCreateButtons: create buttons are already hidden');
        return;
    }

    $newButton.addClass('hidden');
    $sendButton.addClass('hidden');
    $expiration.addClass('hidden');
    $formatter.addClass('hidden');
    $burnAfterReadingOption.addClass('hidden');
    $openDiscussionOption.addClass('hidden');
    $password.addClass('hidden');
    $attach.addClass('hidden');

    createButtonsDisplayed = false;
}

/**
 * only shows the "new paste" button
 *
 * @name   TopNav.showNewPasteButton
 * @function
 */
export function showNewPasteButton()
{
    $newButton.removeClass('hidden');
}

/**
 * only hides the clone button
 *
 * @name   TopNav.hideCloneButton
 * @function
 */
export function hideCloneButton()
{
    $cloneButton.addClass('hidden');
}

/**
 * only hides the raw text button
 *
 * @name   TopNav.hideRawButton
 * @function
 */
export function hideRawButton()
{
    $rawTextButton.addClass('hidden');
}

/**
 * hides the file selector in attachment
 *
 * @name   TopNav.hideFileSelector
 * @function
 */
export function hideFileSelector()
{
    $fileWrap.addClass('hidden');
}


/**
 * shows the custom attachment
 *
 * @name   TopNav.showCustomAttachment
 * @function
 */
export function showCustomAttachment()
{
    $customAttachment.removeClass('hidden');
}

/**
 * collapses the navigation bar if nedded
 *
 * @name   TopNav.collapseBar
 * @function
 */
export function collapseBar()
{
    var $bar = $('.navbar-toggle');

    // check if bar is expanded
    if ($bar.hasClass('collapse in')) {
        // if so, toggle it
        $bar.click();
    }
}

/**
 * returns the currently set expiration time
 *
 * @name   TopNav.getExpiration
 * @function
 * @return {int}
 */
export function getExpiration()
{
    return pasteExpiration;
}

/**
 * returns the currently selected file(s)
 *
 * @name   TopNav.getFileList
 * @function
 * @return {FileList|null}
 */
export function getFileList()
{
    var $file = $('#file');

    // if no file given, return null
    if (!$file.length || !$file[0].files.length) {
        return null;
    }

    // ensure the selected file is still accessible
    if (!($file[0].files && $file[0].files[0])) {
        return null;
    }

    return $file[0].files;
}

/**
 * returns the state of the burn after reading checkbox
 *
 * @name   TopNav.getExpiration
 * @function
 * @return {bool}
 */
export function getBurnAfterReading()
{
    return $burnAfterReading.is(':checked');
}

/**
 * returns the state of the discussion checkbox
 *
 * @name   TopNav.getOpenDiscussion
 * @function
 * @return {bool}
 */
export function getOpenDiscussion()
{
    return $openDiscussion.is(':checked');
}

/**
 * returns the entered password
 *
 * @name   TopNav.getPassword
 * @function
 * @return {string}
 */
export function getPassword()
{
    return $passwordInput.val();
}

/**
 * returns the element where custom attachments can be placed
 *
 * Used by AttachmentViewer when an attachment is cloned here.
 *
 * @name   TopNav.getCustomAttachment
 * @function
 * @return {jQuery}
 */
export function getCustomAttachment()
{
    return $customAttachment;
}

/**
 * init navigation manager
 *
 * preloads jQuery elements
 *
 * @name   TopNav.init
 * @function
 */
export function init()
{
    $attach = $('#attach');
    $burnAfterReading = $('#burnafterreading');
    $burnAfterReadingOption = $('#burnafterreadingoption');
    $cloneButton = $('#clonebutton');
    $customAttachment = $('#customattachment');
    $expiration = $('#expiration');
    $fileRemoveButton = $('#fileremovebutton');
    $fileWrap = $('#filewrap');
    $formatter = $('#formatter');
    $newButton = $('#newbutton');
    $openDiscussion = $('#opendiscussion');
    $openDiscussionOption = $('#opendiscussionoption');
    $password = $('#password');
    $passwordInput = $('#passwordinput');
    $rawTextButton = $('#rawtextbutton');
    $sendButton = $('#sendbutton');
    $qrCodeLink = $('#qrcodelink');

    // bootstrap template drop down
    $('#language ul.dropdown-menu li a').click(setLanguage);
    // page template drop down
    $('#language select option').click(setLanguage);

    // bind events
    $burnAfterReading.change(changeBurnAfterReading);
    $openDiscussionOption.change(changeOpenDiscussion);
    $newButton.click(clickNewPaste);
    $sendButton.click(PasteEncrypter.sendPaste);
    $cloneButton.click(Controller.clonePaste);
    $rawTextButton.click(rawText);
    $fileRemoveButton.click(removeAttachment);
    $qrCodeLink.click(displayQrCode);

    // bootstrap template drop downs
    $('ul.dropdown-menu li a', $('#expiration').parent()).click(updateExpiration);
    $('ul.dropdown-menu li a', $('#formatter').parent()).click(updateFormat);

    // initiate default state of checkboxes
    changeBurnAfterReading();
    changeOpenDiscussion();

    // get default value from template or fall back to set value
    pasteExpiration = Model.getExpirationDefault() || pasteExpiration;
}
