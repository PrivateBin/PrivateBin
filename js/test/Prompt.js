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
                        <nav class="navbar navbar-inverse navbar-static-top">
                            <div id="navbar" class="navbar-collapse collapse">
                            <ul class="nav navbar-nav"><li><button id="newbutton" type="button" class="hidden btn btn-warning navbar-btn">
                            <span class="glyphicon glyphicon-file" aria-hidden="true">
                                </span> New</button><button id="clonebutton" type="button" class="hidden btn btn-warning navbar-btn">
                            <span class="glyphicon glyphicon-duplicate" aria-hidden="true"></span> Clone</button>
                            <button id="rawtextbutton" type="button" class="hidden btn btn-warning navbar-btn">
                            <span class="glyphicon glyphicon-text-background" aria-hidden="true"></span> Raw text</button>
                            <button id="downloadtextbutton" type="button" class="hidden btn btn-default navbar-btn"></button>
                            <button id="qrcodelink" type="button" data-toggle="modal" data-target="#qrcodemodal" class="hidden btn btn-warning navbar-btn"/>
                            <span class="glyphicon glyphicon-qrcode" aria-hidden="true"></span> QR code</button></li></ul></div>
                        </nav>
                        <div id="errormessage"></div>
                        <div id="loadingindicator"></div>
                        <div id="status"></div>
                    `;

                    // Initialize the Prompt module to set up event listeners
                    PrivateBin.Alert.init();
                    PrivateBin.TopNav.init();
                    PrivateBin.Prompt.init();
                    PrivateBin.Prompt.requestPassword();

                    // mock request response
                    const originalFetch = globalThis.fetch;
                    globalThis.fetch = function (url) {
                        assert.strictEqual(url, 'ftp://example.com/?pasteid=0000000000000000');
                        return Promise.resolve({
                            ok: true,
                            status: 200,
                            statusText: 'OK',
                            json: function () {
                                return Promise.resolve({});
                            }
                        });
                    };

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
                    globalThis.fetch = originalFetch;
                    clean();
                    return result === password;
                }
            ));
        });
    });
});
