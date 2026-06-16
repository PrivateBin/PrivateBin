'use strict';
require('../common');
const fc = require('fast-check');

describe('Prompt', function () {
    describe('requestPassword & getPassword', function () {
        this.timeout(30000);

        it('returns the password fed into the dialog', () => {
            fc.assert(fc.property(
                fc.string(),
                function (password) {
                    password = password.replace(/\r+|\n+/g, '');
                    const clean = globalThis.cleanup('', {url: 'ftp://example.com/?0000000000000000'});
                    document.body.innerHTML = `
                        <div id="passwordmodal" class="modal fade" role="dialog">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-body">
                                        <form id="passwordform" role="form">
                                            <div class="form-group">
                                                <input id="passworddecrypt" type="password" 
                                                    class="form-control" placeholder="Enter password">
                                            </div>
                                            <button type="submit">Decrypt</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    // Initialize the Prompt module to set up event listeners
                    PrivateBin.Prompt.init();
                    PrivateBin.Prompt.requestPassword();

                    // Simulate user input
                    const passwordInput = document.getElementById('passworddecrypt');
                    passwordInput.value = password;

                    // Simulate form submission to trigger password capture
                    const passwordForm = document.getElementById('passwordform');
                    /** {@type SubmitEvent} */
                    const submitEvent = new Event('submit', { bubbles: true, cancelable: true });
                    passwordForm.dispatchEvent(submitEvent);

                    // Verify that getPassword returns the submitted password
                    const result = PrivateBin.Prompt.getPassword();
                    clean();
                    return result === password;
                }
            ));
        });
    });
});
