import $ from 'jquery'

/**
 * Responsible for AJAX requests, transparently handles encryption…
 *
 * @name   Uploader
 * @class
 */

var successFunc = null,
    failureFunc = null,
    url,
    data,
    symmetricKey,
    password;

/**
 * public variable ('constant') for errors to prevent magic numbers
 *
 * @name   Uploader.error
 * @readonly
 * @enum   {Object}
 */
var error = {
    okay: 0,
    custom: 1,
    unknown: 2,
    serverError: 3
}

/**
 * ajaxHeaders to send in AJAX requests
 *
 * @name   Uploader.ajaxHeaders
 * @private
 * @readonly
 * @enum   {Object}
 */
var ajaxHeaders = {'X-Requested-With': 'JSONHttpRequest'};

/**
 * called after successful upload
 *
 * @name   Uploader.checkCryptParameters
 * @private
 * @function
 * @throws {string}
 */
function checkCryptParameters()
{
    // workaround for this nasty 'bug' in ECMAScript
    // see https://stackoverflow.com/questions/18808226/why-is-typeof-null-object
    var typeOfKey = typeof symmetricKey;
    if (symmetricKey === null) {
        typeOfKey = 'null';
    }

    // in case of missing preparation, throw error
    switch (typeOfKey) {
        case 'string':
            // already set, all right
            return;
        case 'null':
            // needs to be generated auto-generate
            symmetricKey = CryptTool.getSymmetricKey();
            break;
        default:
            console.error('current invalid symmetricKey:', symmetricKey);
            throw 'symmetricKey is invalid, probably the module was not prepared';
    }
    // password is optional
}

/**
 * called after successful upload
 *
 * @name   Uploader.success
 * @private
 * @function
 * @param {int} status
 * @param {int} result - optional
 */
function success(status, result)
{
    // add useful data to result
    result.encryptionKey = symmetricKey;
    result.requestData = data;

    if (successFunc !== null) {
        successFunc(status, result);
    }
}

/**
 * called after a upload failure
 *
 * @name   Uploader.fail
 * @private
 * @function
 * @param {int} status - internal code
 * @param {int} result - original error code
 */
function fail(status, result)
{
    if (failureFunc !== null) {
        failureFunc(status, result);
    }
}

/**
 * actually uploads the data
 *
 * @name   Uploader.run
 * @function
 */
export function run()
{
    $.ajax({
        type: 'POST',
        url: url,
        data: data,
        dataType: 'json',
        headers: ajaxHeaders,
        success: function(result) {
            if (result.status === 0) {
                success(0, result);
            } else if (result.status === 1) {
                fail(1, result);
            } else {
                fail(2, result);
            }
        }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
        console.error(textStatus, errorThrown);
        fail(3, jqXHR);
    });
}

/**
 * set success function
 *
 * @name   Uploader.setUrl
 * @function
 * @param {function} newUrl
 */
export function setUrl(newUrl)
{
    url = newUrl;
}

/**
 * sets the password to use (first value) and optionally also the
 * encryption key (not recommend, it is automatically generated).
 *
 * Note: Call this after prepare() as prepare() resets these values.
 *
 * @name   Uploader.setCryptValues
 * @function
 * @param {string} newPassword
 * @param {string} newKey       - optional
 */
export function setCryptParameters(newPassword, newKey)
{
    password = newPassword;

    if (typeof newKey !== 'undefined') {
        symmetricKey = newKey;
    }
}

/**
 * set success function
 *
 * @name   Uploader.setSuccess
 * @function
 * @param {function} func
 */
export function setSuccess(func)
{
    successFunc = func;
}

/**
 * set failure function
 *
 * @name   Uploader.setFailure
 * @function
 * @param {function} func
 */
export function setFailure(func)
{
    failureFunc = func;
}

/**
 * prepares a new upload
 *
 * Call this when doing a new upload to reset any data from potential
 * previous uploads. Must be called before any other method of this
 * module.
 *
 * @name   Uploader.prepare
 * @function
 * @return {object}
 */
export function prepare()
{
    // entropy should already be checked!

    // reset password
    password = '';

    // reset key, so it a new one is generated when it is used
    symmetricKey = null;

    // reset data
    successFunc = null;
    failureFunc = null;
    url = Helper.baseUri();
    data = {};
}

/**
 * encrypts and sets the data
 *
 * @name   Uploader.setData
 * @function
 * @param {string} index
 * @param {mixed} element
 */
export function setData(index, element)
{
    checkCryptParameters();
    data[index] = CryptTool.cipher(symmetricKey, password, element);
}

/**
 * set the additional metadata to send unencrypted
 *
 * @name   Uploader.setUnencryptedData
 * @function
 * @param {string} index
 * @param {mixed} element
 */
export function setUnencryptedData(index, element)
{
    data[index] = element;
}

/**
 * set the additional metadata to send unencrypted passed at once
 *
 * @name   Uploader.setUnencryptedData
 * @function
 * @param {object} newData
 */
export function setUnencryptedBulkData(newData)
{
    $.extend(data, newData);
}

/**
 * Helper, which parses shows a general error message based on the result of the Uploader
 *
 * @name    Uploader.parseUploadError
 * @function
 * @param {int} status
 * @param {object} data
 * @param {string} doThisThing - a human description of the action, which was tried
 * @return {array}
 */
export function parseUploadError(status, data, doThisThing) {
    var errorArray;

    switch (status) {
        case error.custom:
            errorArray = ['Could not ' + doThisThing + ': %s', data.message];
            break;
        case error.unknown:
            errorArray = ['Could not ' + doThisThing + ': %s', I18n._('unknown status')];
            break;
        case error.serverError:
            errorArray = ['Could not ' + doThisThing + ': %s', I18n._('server error or not responding')];
            break;
        default:
            errorArray = ['Could not ' + doThisThing + ': %s', I18n._('unknown error')];
            break;
    }

    return errorArray;
}

/**
 * init Uploader
 *
 * @name   Uploader.init
 * @function
 */
export function init()
{
    // nothing yet
}
