import $ from 'jquery'

/**
 * password prompt
 *
 * @name Prompt
 * @class
 */

var $passwordDecrypt,
    $passwordForm,
    $passwordModal;

var password = '';

/**
 * submit a password in the modal dialog
 *
 * @name Prompt.submitPasswordModal
 * @private
 * @function
 * @param  {Event} event
 */
function submitPasswordModal(event)
{
    event.preventDefault();

    // get input
    password = $passwordDecrypt.val();

    // hide modal
    $passwordModal.modal('hide');

    PasteDecrypter.run();
}

/**
 * ask the user for the password and set it
 *
 * @name Prompt.requestPassword
 * @function
 */
export function requestPassword()
{
    // show new bootstrap method (if available)
    if ($passwordModal.length !== 0) {
        $passwordModal.modal({
            backdrop: 'static',
            keyboard: false
        });
        return;
    }

    // fallback to old method for page template
    var newPassword = prompt(I18n._('Please enter the password for this paste:'), '');
    if (newPassword === null) {
        throw 'password prompt canceled';
    }
    if (password.length === 0) {
        // recurse…
        return requestPassword();
    }

    password = newPassword;
}

/**
 * get the cached password
 *
 * If you do not get a password with this function
 * (returns an empty string), use requestPassword.
 *
 * @name   Prompt.getPassword
 * @function
 * @return {string}
 */
export function getPassword()
{
    return password;
}

/**
 * init status manager
 *
 * preloads jQuery elements
 *
 * @name   Prompt.init
 * @function
 */
export function init()
{
    $passwordDecrypt = $('#passworddecrypt');
    $passwordForm = $('#passwordform');
    $passwordModal = $('#passwordmodal');

    // bind events

    // focus password input when it is shown
    $passwordModal.on('shown.bs.Model', function () {
        $passwordDecrypt.focus();
    });
    // handle Model password submission
    $passwordForm.submit(submitPasswordModal);
}
