import $ from 'jquery'

/**
 * handles paste status/result
 *
 * @name   PasteStatus
 * @class
 */

var $pasteSuccess,
    $pasteUrl,
    $remainingTime,
    $shortenButton;

/**
 * forward to URL shortener
 *
 * @name   PasteStatus.sendToShortener
 * @private
 * @function
 */
function sendToShortener()
{
    window.location.href = $shortenButton.data('shortener') +
                           encodeURIComponent($pasteUrl.attr('href'));
}

/**
 * Forces opening the paste if the link does not do this automatically.
 *
 * This is necessary as browsers will not reload the page when it is
 * already loaded (which is fake as it is set via history.pushState()).
 *
 * @name   PasteStatus.pasteLinkClick
 * @function
 */
function pasteLinkClick()
{
    // check if location is (already) shown in URL bar
    if (window.location.href === $pasteUrl.attr('href')) {
        // if so we need to load link by reloading the current site
        window.location.reload(true);
    }
}

/**
 * creates a notification after a successfull paste upload
 *
 * @name   PasteStatus.createPasteNotification
 * @function
 * @param  {string} url
 * @param  {string} deleteUrl
 */
export function createPasteNotification(url, deleteUrl)
{
    $('#pastelink').html(
        I18n._(
            'Your paste is <a id="pasteurl" href="%s">%s</a> <span id="copyhint">(Hit [Ctrl]+[c] to copy)</span>',
            url, url
        )
    );
    // save newly created element
    $pasteUrl = $('#pasteurl');
    // and add click event
    $pasteUrl.click(pasteLinkClick);

    // shorten button
    $('#deletelink').html('<a href="' + deleteUrl + '">' + I18n._('Delete data') + '</a>');

    // show result
    $pasteSuccess.removeClass('hidden');
    // we pre-select the link so that the user only has to [Ctrl]+[c] the link
    Helper.selectText($pasteUrl[0]);
}

/**
 * shows the remaining time
 *
 * @name PasteStatus.showRemainingTime
 * @function
 * @param {object} pasteMetaData
 */
export function showRemainingTime(pasteMetaData)
{
    if (pasteMetaData.burnafterreading) {
        // display paste "for your eyes only" if it is deleted

        // actually remove paste, before we claim it is deleted
        Controller.removePaste(Model.getPasteId(), 'burnafterreading');

        Alert.showRemaining('FOR YOUR EYES ONLY. Don\'t close this window, this message can\'t be displayed again.');
        $remainingTime.addClass('foryoureyesonly');

        // discourage cloning (it cannot really be prevented)
        TopNav.hideCloneButton();

    } else if (pasteMetaData.expire_date) {
        // display paste expiration
        var expiration = Helper.secondsToHuman(pasteMetaData.remaining_time),
            expirationLabel = [
                'This document will expire in %d ' + expiration[1] + '.',
                'This document will expire in %d ' + expiration[1] + 's.'
            ];

        Alert.showRemaining([expirationLabel, expiration[0]]);
        $remainingTime.removeClass('foryoureyesonly');
    } else {
        // never expires
        return;
    }

    // in the end, display notification
    $remainingTime.removeClass('hidden');
}

/**
 * hides the remaining time and successful upload notification
 *
 * @name PasteStatus.hideRemainingTime
 * @function
 */
export function hideMessages()
{
    $remainingTime.addClass('hidden');
    $pasteSuccess.addClass('hidden');
}

/**
 * init status manager
 *
 * preloads jQuery elements
 *
 * @name   PasteStatus.init
 * @function
 */
export function init()
{
    $pasteSuccess = $('#pastesuccess');
    // $pasteUrl is saved in createPasteNotification() after creation
    $remainingTime = $('#remainingtime');
    $shortenButton = $('#shortenbutton');

    // bind elements
    $shortenButton.click(sendToShortener);
}
