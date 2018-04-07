import $ from 'jquery'

/**
 * Alert/error manager
 *
 * @name   Alert
 * @class
 */

var $errorMessage,
    $loadingIndicator,
    $statusMessage,
    $remainingTime;

var currentIcon;

var alertType = [
    'loading', // not in bootstrap, but using a good value here
    'info', // status icon
    'warning', // not used yet
    'danger' // error icon
];

var customHandler;

/**
 * forwards a request to the i18n module and shows the element
 *
 * @name   Alert.handleNotification
 * @private
 * @function
 * @param  {int} id - id of notification
 * @param  {jQuery} $element - jQuery object
 * @param  {string|array} args
 * @param  {string|null} icon - optional, icon
 */
function handleNotification(id, $element, args, icon)
{
    // basic parsing/conversion of parameters
    if (typeof icon === 'undefined') {
        icon = null;
    }
    if (typeof args === 'undefined') {
        args = null;
    } else if (typeof args === 'string') {
        // convert string to array if needed
        args = [args];
    }

    // pass to custom handler if defined
    if (typeof customHandler === 'function') {
        var handlerResult = customHandler(alertType[id], $element, args, icon);
        if (handlerResult === true) {
            // if it returns true, skip own handler
            return;
        }
        if (handlerResult instanceof jQuery) {
            // continue processing with new element
            $element = handlerResult;
            icon = null; // icons not supported in this case
        }
    }

    // handle icon
    if (icon !== null && // icon was passed
        icon !== currentIcon[id] // and it differs from current icon
    ) {
        var $glyphIcon = $element.find(':first');

        // remove (previous) icon
        $glyphIcon.removeClass(currentIcon[id]);

        // any other thing as a string (e.g. 'null') (only) removes the icon
        if (typeof icon === 'string') {
            // set new icon
            currentIcon[id] = 'glyphicon-' + icon;
            $glyphIcon.addClass(currentIcon[id]);
        }
    }

    // show text
    if (args !== null) {
        // add jQuery object to it as first parameter
        args.unshift($element);
        // pass it to I18n
        I18n._.apply(this, args);
    }

    // show notification
    $element.removeClass('hidden');
}

/**
 * display a status message
 *
 * This automatically passes the text to I18n for translation.
 *
 * @name   Alert.showStatus
 * @function
 * @param  {string|array} message     string, use an array for %s/%d options
 * @param  {string|null}  icon        optional, the icon to show,
 *                                    default: leave previous icon
 */
export function showStatus(message, icon)
{
    console.info('status shown: ', message);
    handleNotification(1, $statusMessage, message, icon);
}

/**
 * display an error message
 *
 * This automatically passes the text to I18n for translation.
 *
 * @name   Alert.showError
 * @function
 * @param  {string|array} message     string, use an array for %s/%d options
 * @param  {string|null}  icon        optional, the icon to show, default:
 *                                    leave previous icon
 */
export function showError(message, icon)
{
    console.error('error message shown: ', message);
    handleNotification(3, $errorMessage, message, icon);
}

/**
 * display remaining message
 *
 * This automatically passes the text to I18n for translation.
 *
 * @name   Alert.showRemaining
 * @function
 * @param  {string|array} message     string, use an array for %s/%d options
 */
export function showRemaining(message)
{
    console.info('remaining message shown: ', message);
    handleNotification(1, $remainingTime, message);
}

/**
 * shows a loading message, optionally with a percentage
 *
 * This automatically passes all texts to the i10s module.
 *
 * @name   Alert.showLoading
 * @function
 * @param  {string|array|null} message      optional, use an array for %s/%d options, default: 'Loading…'
 * @param  {string|null}       icon         optional, the icon to show, default: leave previous icon
 */
export function showLoading(message, icon)
{
    if (typeof message !== 'undefined' && message !== null) {
        console.info('status changed: ', message);
    }

    // default message text
    if (typeof message === 'undefined') {
        message = 'Loading…';
    }

    handleNotification(0, $loadingIndicator, message, icon);

    // show loading status (cursor)
    $('body').addClass('loading');
}

/**
 * hides the loading message
 *
 * @name   Alert.hideLoading
 * @function
 */
export function hideLoading()
{
    $loadingIndicator.addClass('hidden');

    // hide loading cursor
    $('body').removeClass('loading');
}

/**
 * hides any status/error messages
 *
 * This does not include the loading message.
 *
 * @name   Alert.hideMessages
 * @function
 */
export function hideMessages()
{
    // also possible: $('.statusmessage').addClass('hidden');
    $statusMessage.addClass('hidden');
    $errorMessage.addClass('hidden');
}

/**
 * set a custom handler, which gets all notifications.
 *
 * This handler gets the following arguments:
 * alertType (see array), $element, args, icon
 * If it returns true, the own processing will be stopped so the message
 * will not be displayed. Otherwise it will continue.
 * As an aditional feature it can return q jQuery element, which will
 * then be used to add the message there. Icons are not supported in
 * that case and will be ignored.
 * Pass 'null' to reset/delete the custom handler.
 * Note that there is no notification when a message is supposed to get
 * hidden.
 *
 * @name   Alert.setCustomHandler
 * @function
 * @param {function|null} newHandler
 */
export function setCustomHandler(newHandler)
{
    customHandler = newHandler;
}

/**
 * init status manager
 *
 * preloads jQuery elements
 *
 * @name   Alert.init
 * @function
 */
export function init()
{
    // hide "no javascript" error message
    $('#noscript').hide();

    // not a reset, but first set of the elements
    $errorMessage = $('#errormessage');
    $loadingIndicator = $('#loadingindicator');
    $statusMessage = $('#status');
    $remainingTime = $('#remainingtime');

    currentIcon = [
        'glyphicon-time', // loading icon
        'glyphicon-info-sign', // status icon
        '', // reserved for warning, not used yet
        'glyphicon-alert' // error icon
    ];
}
