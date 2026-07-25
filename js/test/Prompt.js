'use strict';
require('../common');
const fc = require('fast-check');

describe('Prompt', function () {
    describe('fallback modals without Bootstrap JavaScript', function () {
        it('shows and hides the password prompt', function () {
            const clean = globalThis.cleanup();
            let decryptRuns = 0;
            document.body.innerHTML =
                '<div id="passwordmodal" class="modal fade" role="dialog">' +
                    '<form id="passwordform">' +
                        '<input id="passworddecrypt" type="password">' +
                    '</form>' +
                '</div>';
            PrivateBin.PasteDecrypter.run = function () { ++decryptRuns; };

            PrivateBin.Prompt.init();
            PrivateBin.Prompt.requestPassword();

            const modal = document.getElementById('passwordmodal');
            assert.strictEqual(modal.style.display, 'block');
            assert.ok(modal.classList.contains('show'));

            document.getElementById('passwordform').dispatchEvent(
                new Event('submit', {bubbles: true, cancelable: true})
            );
            assert.strictEqual(modal.style.display, 'none');
            assert.ok(!modal.classList.contains('show'));
            assert.strictEqual(decryptRuns, 1);
            clean();
        });

        it('shows and dismisses the load confirmation prompt', function () {
            const clean = globalThis.cleanup();
            let decryptRuns = 0,
                newPasteRuns = 0;
            document.body.innerHTML =
                '<div id="loadconfirmmodal" class="modal fade">' +
                    '<button id="loadconfirm-open-now"></button>' +
                    '<button class="btn-close"></button>' +
                '</div>';
            PrivateBin.PasteDecrypter.run = function () { ++decryptRuns; };
            PrivateBin.Controller.newPaste = function () { ++newPasteRuns; };

            PrivateBin.Prompt.requestLoadConfirmation();
            PrivateBin.Prompt.requestLoadConfirmation();

            const modal = document.getElementById('loadconfirmmodal');
            assert.strictEqual(modal.style.display, 'block');
            assert.ok(modal.classList.contains('show'));

            document.getElementById('loadconfirm-open-now').click();
            assert.strictEqual(modal.style.display, 'none');
            assert.ok(!modal.classList.contains('show'));
            assert.strictEqual(decryptRuns, 1);

            PrivateBin.Prompt.requestLoadConfirmation();
            document.querySelector('#loadconfirmmodal .btn-close').click();
            assert.strictEqual(modal.style.display, 'none');
            assert.strictEqual(newPasteRuns, 1);
            clean();
        });
    });

    describe('requestPassword & getPassword', function () {
        this.timeout(30000);

        it('returns the password fed into the dialog', () => {
            fc.assert(fc.property(
                fc.string(),
                function (password) {
                    password = password.replace(/\r+|\n+/g, '');
                    const clean = globalThis.cleanup('', {url: 'ftp://example.com/?0000000000000000'});
                    PrivateBin.PasteDecrypter.run = function () {};
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
