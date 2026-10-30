'use strict';
require('../common');
const fc = require('fast-check');

function query(selector) {
    if (selector.startsWith('#')) {
        return document.getElementById(selector.slice(1));
    }
    return document.querySelector(selector);
}

describe('TopNav', function () {
    describe('showEmailButton', function () {
        afterEach(function () {
            cleanup();
        });

        it('does nothing when the email button is omitted from the page', function () {
            document.documentElement.innerHTML =
                '<nav><div id="navbar"><ul><li><button id="newbutton" ' +
                'type="button">New</button></li></ul></div></nav>';
            PrivateBin.TopNav.init();

            const originalShowError = PrivateBin.Alert.showError;
            let errorShown = false;
            PrivateBin.Alert.showError = function () {
                errorShown = true;
            };

            try {
                assert.doesNotThrow(() => PrivateBin.TopNav.showEmailButton());
                assert.strictEqual(errorShown, false);
            } finally {
                PrivateBin.Alert.showError = originalShowError;
            }
        });
    });

    describe('hideBurnAfterReadingButtons', function () {
        it(
            'hides buttons when the QR code button is disabled',
            function () {
                document.documentElement.innerHTML =
                    '<nav><button id="clonebutton"></button></nav>';
                PrivateBin.TopNav.init();

                assert.doesNotThrow(function () {
                    PrivateBin.TopNav.hideBurnAfterReadingButtons();
                });
                assert.ok(query('#clonebutton').classList.contains('hidden'));
                cleanup();
            }
        );
    });

    describe('showViewButtons & hideViewButtons', function () {
        before(function () {
            cleanup();
        });

        it(
            'shows and hides navigation elements when the QR code button is disabled',
            function () {
                document.documentElement.innerHTML =
                    '<nav><button id="newbutton" class="hidden"></button>' +
                    '<button id="clonebutton" class="hidden"></button>' +
                    '<button id="rawtextbutton" class="hidden"></button>' +
                    '<button id="downloadtextbutton" class="hidden"></button></nav>';
                PrivateBin.TopNav.init();

                assert.doesNotThrow(function () {
                    PrivateBin.TopNav.showViewButtons();
                });
                assert.ok(!query('#newbutton').classList.contains('hidden'));
                assert.ok(!query('#clonebutton').classList.contains('hidden'));
                assert.ok(!query('#rawtextbutton').classList.contains('hidden'));
                assert.ok(!query('#downloadtextbutton').classList.contains('hidden'));

                assert.doesNotThrow(function () {
                    PrivateBin.TopNav.hideViewButtons();
                });
                assert.ok(query('#newbutton').classList.contains('hidden'));
                assert.ok(query('#clonebutton').classList.contains('hidden'));
                assert.ok(query('#rawtextbutton').classList.contains('hidden'));
                assert.ok(query('#downloadtextbutton').classList.contains('hidden'));
                cleanup();
            }
        );

        it(
            'displays & hides navigation elements for viewing an existing document',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    `<nav class="navbar navbar-inverse navbar-static-top">
                    <div id="navbar" class="navbar-collapse collapse">
                    <ul class="nav navbar-nav"><li><button id="newbutton" type="button" class="hidden btn btn-warning navbar-btn">
                    <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="img/bootstrap-icons.svg#file-earmark"></use></svg> New</button><button id="clonebutton" type="button" class="hidden btn btn-warning navbar-btn">
                    <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="img/bootstrap-icons.svg#copy"></use></svg> Clone</button>
                    <button id="rawtextbutton" type="button" class="hidden btn btn-warning navbar-btn">
                    <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="img/bootstrap-icons.svg#filetype-txt"></use></svg> Raw text</button>
                    <button id="downloadtextbutton" type="button" class="hidden btn btn-default navbar-btn"></button>
                    <button id="qrcodelink" type="button" data-toggle="modal" data-target="#qrcodemodal" class="hidden btn btn-warning navbar-btn"/>
                    <svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="img/bootstrap-icons.svg#qr-code"></use></svg> QR code</button></li></ul></div>
                    </nav>`;
                PrivateBin.TopNav.init();
                results.push(
                    query('#newbutton').classList.contains('hidden') &&
                    query('#clonebutton').classList.contains('hidden') &&
                    query('#rawtextbutton').classList.contains('hidden') &&
                    query('#qrcodelink').classList.contains('hidden')
                );
                PrivateBin.TopNav.showViewButtons();
                results.push(
                    !query('#newbutton').classList.contains('hidden') &&
                    !query('#clonebutton').classList.contains('hidden') &&
                    !query('#rawtextbutton').classList.contains('hidden') &&
                    !query('#qrcodelink').classList.contains('hidden')
                );
                PrivateBin.TopNav.hideViewButtons();
                results.push(
                    query('#newbutton').classList.contains('hidden') &&
                    query('#clonebutton').classList.contains('hidden') &&
                    query('#rawtextbutton').classList.contains('hidden') &&
                    query('#qrcodelink').classList.contains('hidden')
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );
    });

    describe('showCreateButtons & hideCreateButtons', function () {
        before(function () {
            cleanup();
        });

        it(
            'displays & hides navigation elements for creating a document',
            function () { // eslint-disable-line complexity
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li><button id="newbutton" ' +
                    'type="button" class="hidden">New</button></li><li><a ' +
                    'id="expiration" href="#" class="hidden">Expiration</a>' +
                    '</li><li><div id="burnafterreadingoption" class="hidden">' +
                    'Burn after reading</div></li><li><div id="opendiscussion' +
                    'option" class="hidden">Open discussion</div></li><li>' +
                    '<div id="password" class="hidden">Password</div></li>' +
                    '<li id="attach" class="hidden">Attach a file</li><li>' +
                    '<a id="formatter" href="#" class="hidden">Format</a>' +
                    '</li><li><button id="sendbutton" type="button" ' +
                    'class="hidden">Create</button></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    query('#sendbutton').classList.contains('hidden') &&
                    query('#expiration').classList.contains('hidden') &&
                    query('#formatter').classList.contains('hidden') &&
                    query('#burnafterreadingoption').classList.contains('hidden') &&
                    query('#opendiscussionoption').classList.contains('hidden') &&
                    query('#newbutton').classList.contains('hidden') &&
                    query('#password').classList.contains('hidden') &&
                    query('#attach').classList.contains('hidden')
                );
                PrivateBin.TopNav.showCreateButtons();
                results.push(
                    !query('#sendbutton').classList.contains('hidden') &&
                    !query('#expiration').classList.contains('hidden') &&
                    !query('#formatter').classList.contains('hidden') &&
                    !query('#burnafterreadingoption').classList.contains('hidden') &&
                    !query('#opendiscussionoption').classList.contains('hidden') &&
                    !query('#newbutton').classList.contains('hidden') &&
                    !query('#password').classList.contains('hidden') &&
                    !query('#attach').classList.contains('hidden')
                );
                PrivateBin.TopNav.hideCreateButtons();
                results.push(
                    query('#sendbutton').classList.contains('hidden') &&
                    query('#expiration').classList.contains('hidden') &&
                    query('#formatter').classList.contains('hidden') &&
                    query('#burnafterreadingoption').classList.contains('hidden') &&
                    query('#opendiscussionoption').classList.contains('hidden') &&
                    query('#newbutton').classList.contains('hidden') &&
                    query('#password').classList.contains('hidden') &&
                    query('#attach').classList.contains('hidden')
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );

        it(
            'tolerates missing optional elements (discussion & password disabled)',
            function () {
                // with discussion = false and password = false in the
                // configuration, the template omits these elements entirely
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li><button id="newbutton" ' +
                    'type="button" class="hidden">New</button></li><li><a ' +
                    'id="expiration" href="#" class="hidden">Expiration</a>' +
                    '</li><li><div id="burnafterreadingoption" class="hidden">' +
                    'Burn after reading</div></li><li>' +
                    '<a id="formatter" href="#" class="hidden">Format</a>' +
                    '</li><li><button id="sendbutton" type="button" ' +
                    'class="hidden">Create</button></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                assert.doesNotThrow(() => PrivateBin.TopNav.showCreateButtons());
                assert.ok(!query('#sendbutton').classList.contains('hidden'));
                assert.ok(!query('#burnafterreadingoption').classList.contains('hidden'));
                assert.doesNotThrow(() => PrivateBin.TopNav.hideCreateButtons());
                assert.ok(query('#sendbutton').classList.contains('hidden'));
                assert.ok(query('#burnafterreadingoption').classList.contains('hidden'));
                cleanup();
            }
        );
    });

    describe('send shortcut', function () {
        let sendCount;

        // builds the create-document navbar and stubs the actual send, so no
        // server interaction happens
        function setUpCreateMode() {
            cleanup();
            sendCount = 0;
            document.documentElement.innerHTML =
                '<nav><div id="navbar"><ul><li><button id="newbutton" ' +
                'type="button" class="hidden">New</button></li><li><a ' +
                'id="expiration" href="#" class="hidden">Expiration</a>' +
                '</li><li><div id="burnafterreadingoption" class="hidden">' +
                'Burn after reading</div></li><li><div id="opendiscussion' +
                'option" class="hidden">Open discussion</div></li><li>' +
                '<div id="password" class="hidden">Password</div></li>' +
                '<li id="attach" class="hidden">Attach a file</li><li>' +
                '<a id="formatter" href="#" class="hidden">Format</a>' +
                '</li><li><button id="sendbutton" type="button" ' +
                'class="hidden">Create</button></li></ul></div></nav>';
            PrivateBin.PasteEncrypter.sendPaste = function () {
                sendCount++;
            };
            PrivateBin.TopNav.init();
            PrivateBin.TopNav.showCreateButtons();
        }

        // dispatches a keydown on the document, returns whether the default was prevented
        function pressKey(options) {
            const event = new window.KeyboardEvent('keydown', Object.assign(
                {bubbles: true, cancelable: true}, options
            ));
            document.dispatchEvent(event);
            return event.defaultPrevented;
        }

        afterEach(function () {
            cleanup();
        });

        it('sends the document on Ctrl+S and suppresses the browser save dialog', function () {
            setUpCreateMode();
            const prevented = pressKey({key: 's', code: 'KeyS', ctrlKey: true});
            assert.strictEqual(sendCount, 1);
            assert.ok(prevented);
        });

        it('sends the document on Cmd+S (macOS)', function () {
            setUpCreateMode();
            const prevented = pressKey({key: 's', code: 'KeyS', metaKey: true});
            assert.strictEqual(sendCount, 1);
            assert.ok(prevented);
        });

        it('sends on the physical S key of non-Latin keyboard layouts', function () {
            setUpCreateMode();
            // on a Russian layout the S key produces "ы"
            pressKey({key: 'ы', code: 'KeyS', ctrlKey: true});
            assert.strictEqual(sendCount, 1);
        });

        it('leaves Ctrl+S to the browser when viewing a document', function () {
            setUpCreateMode();
            PrivateBin.TopNav.hideCreateButtons();
            const prevented = pressKey({key: 's', code: 'KeyS', ctrlKey: true});
            assert.strictEqual(sendCount, 0);
            assert.ok(!prevented);
        });

        it('ignores S without Ctrl/Cmd, with AltGr (Ctrl+Alt), with Shift, or other keys', function () {
            setUpCreateMode();
            const prevented = [
                pressKey({key: 's', code: 'KeyS'}),
                pressKey({key: 's', code: 'KeyS', ctrlKey: true, altKey: true}),
                pressKey({key: 'S', code: 'KeyS', ctrlKey: true, shiftKey: true}),
                pressKey({key: 'd', code: 'KeyD', ctrlKey: true})
            ];
            assert.strictEqual(sendCount, 0);
            assert.deepStrictEqual(prevented, [false, false, false, false]);
        });
    });

    describe('showNewPasteButton', function () {
        before(function () {
            cleanup();
        });

        it(
            'displays the button for creating a document',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li><button id="newbutton" type=' +
                    '"button" class="hidden">New</button></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    query('#newbutton').classList.contains('hidden')
                );
                PrivateBin.TopNav.showNewPasteButton();
                results.push(
                    !query('#newbutton').classList.contains('hidden')
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );
    });

    describe('hideCloneButton', function () {
        before(function () {
            cleanup();
        });

        it(
            'hides the button for cloning a document',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li><button id="clonebutton" ' +
                    'type="button" class="btn btn-warning navbar-btn">' +
                    '<svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="img/bootstrap-icons.svg#copy"></use></svg> Clone</button></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    !query('#clonebutton').classList.contains('hidden')
                );
                PrivateBin.TopNav.hideCloneButton();
                results.push(
                    query('#clonebutton').classList.contains('hidden')
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );
    });

    describe('hideRawButton', function () {
        before(function () {
            cleanup();
        });

        it(
            'hides the raw text button',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li><button ' +
                    'id="rawtextbutton" type="button" class="btn ' +
                    'btn-warning navbar-btn"><svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="img/bootstrap-icons.svg#filetype-txt"></use></svg> ' +
                    'Raw text</button></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    !query('#rawtextbutton').classList.contains('hidden')
                );
                PrivateBin.TopNav.hideRawButton();
                results.push(
                    query('#rawtextbutton').classList.contains('hidden')
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );
    });

    describe('hideFileSelector', function () {
        before(function () {
            cleanup();
        });

        it(
            'hides the file attachment selection button',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li id="attach" class="hidden ' +
                    'dropdown"><a href="#" class="nav-link dropdown-toggle" ' +
                    'data-bs-toggle="dropdown" role="button" ' +
                    'aria-expanded="false">Attach a file</a>' +
                    '<ul class="dropdown-menu px-2"><li id="filewrap">' +
                    '<div><input type="file" id="file" name="file" /></div>' +
                    '</li><li id="customattachment" class="hidden"></li><li>' +
                    '<a id="fileremovebutton"  href="#">Remove attachment</a>' +
                    '</li></ul></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    !query('#filewrap').classList.contains('hidden')
                );
                PrivateBin.TopNav.hideFileSelector();
                results.push(
                    query('#filewrap').classList.contains('hidden')
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );
    });

    describe('highlightFileupload', function () {
        afterEach(function () {
            cleanup();
        });

        it(
            'opens the attachment dropdown and highlights the file selection',
            function () {
                cleanup();
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li id="attach" class="nav-item ' +
                    'dropdown"><a href="#" class="nav-link dropdown-toggle" ' +
                    'data-bs-toggle="dropdown" role="button" ' +
                    'aria-expanded="false">Attach a file</a>' +
                    '<ul class="dropdown-menu px-2"><li id="filewrap">' +
                    '<div><input type="file" id="file" name="file" /></div>' +
                    '</li></ul></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                const errors = [];
                const consoleError = console.error;
                console.error = function (...args) {
                    errors.push(args.join(' '));
                };
                try {
                    PrivateBin.TopNav.highlightFileupload();
                    assert.strictEqual(query('.dropdown-toggle').getAttribute('aria-expanded'), 'true');
                    assert.ok(query('.dropdown-menu').classList.contains('show'), 'dropdown is open');
                    assert.ok(query('#filewrap').classList.contains('highlight'), 'file selection is highlighted');

                    // a second upload, while the dropdown is still open
                    PrivateBin.TopNav.highlightFileupload();
                    assert.ok(query('.dropdown-menu').classList.contains('show'), 'dropdown stays open');

                    // a third upload, after the user closed the dropdown
                    query('.dropdown-toggle').click();
                    assert.strictEqual(query('.dropdown-toggle').getAttribute('aria-expanded'), 'false');
                    PrivateBin.TopNav.highlightFileupload();
                    assert.ok(query('.dropdown-menu').classList.contains('show'), 'dropdown is open again');
                } finally {
                    console.error = consoleError;
                }
                assert.deepStrictEqual(errors, []);
            }
        );
    });

    describe('showCustomAttachment', function () {
        before(function () {
            cleanup();
        });

        it(
            'display the custom file attachment',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li id="attach" class="hidden ' +
                    'dropdown"><a href="#" class="nav-link dropdown-toggle" ' +
                    'data-bs-toggle="dropdown" role="button" ' +
                    'aria-expanded="false">Attach a file</a>' +
                    '<ul class="dropdown-menu px-2"><li id="filewrap">' +
                    '<div><input type="file" id="file" name="file" /></div>' +
                    '</li><li id="customattachment" class="hidden"></li><li>' +
                    '<a id="fileremovebutton"  href="#">Remove attachment</a>' +
                    '</li></ul></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    query('#customattachment').classList.contains('hidden')
                );
                PrivateBin.TopNav.showCustomAttachment();
                results.push(
                    !query('#customattachment').classList.contains('hidden')
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );
    });

    describe('collapseBar', function () {
        before(function () {
            cleanup();
        });

        /**
         * navigation bar markup of the bootstrap5 template, with the
         * collapsible navigation either expanded or collapsed
         *
         * @param {boolean} isExpanded
         * @returns {string}
         */
        function navbar(isExpanded) {
            return '<nav class="navbar navbar-expand-lg"><div class="container-fluid">' +
                '<button class="navbar-toggler' + (isExpanded ? '' : ' collapsed') +
                '" type="button" data-bs-toggle="collapse" data-bs-target="#navbar" ' +
                'aria-controls="navbar" aria-expanded="' + isExpanded + '" ' +
                'aria-label="Toggle navigation"><span class="navbar-toggler-icon">' +
                '</span></button><div id="navbar" class="collapse navbar-collapse' +
                (isExpanded ? ' show' : '') + '"><ul class="navbar-nav"><li>' +
                '<button id="newbutton" type="button" class="hidden">New</button>' +
                '</li></ul></div></div></nav>';
        }

        it(
            'collapses the expanded navigation when displayed on a small screen',
            function () {
                document.documentElement.innerHTML = navbar(true);
                PrivateBin.TopNav.init();
                let clicks = 0;
                query('.navbar-toggler').addEventListener('click', function () {
                    ++clicks;
                });
                PrivateBin.TopNav.collapseBar();
                assert.strictEqual(clicks, 1, 'toggler is clicked once');
                assert.ok(!query('#navbar').classList.contains('show'), 'navigation is collapsing');
            }
        );

        it(
            'leaves the collapsed navigation alone',
            function () {
                document.documentElement.innerHTML = navbar(false);
                PrivateBin.TopNav.init();
                let clicks = 0;
                query('.navbar-toggler').addEventListener('click', function () {
                    ++clicks;
                });
                PrivateBin.TopNav.collapseBar();
                assert.strictEqual(clicks, 0, 'toggler is not clicked');
                assert.ok(!query('#navbar').classList.contains('collapsing'), 'navigation is not toggled');
            }
        );

        it(
            'ignores pages without a navigation',
            function () {
                document.documentElement.innerHTML = '<body></body>';
                PrivateBin.TopNav.collapseBar();
            }
        );
    });

    describe('resetInput', function () {
        before(function () {
            cleanup();
        });

        it(
            'reset inputs to defaults (options off)',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li id="burnafterreadingoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="burnafterreading" name="burnafterreading" /> ' +
                    'Burn after reading</label></li><li id="opendiscussionoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="opendiscussion" name="opendiscussion" /> ' +
                    'Open discussion</label></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    !PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !PrivateBin.TopNav.getOpenDiscussion()
                );
                query('#burnafterreading').checked = true;
                query('#opendiscussion').checked = true;
                results.push(
                    PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    PrivateBin.TopNav.getOpenDiscussion()
                );
                PrivateBin.TopNav.resetInput();
                results.push(
                    !PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !PrivateBin.TopNav.getOpenDiscussion()
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );

        it(
            'reset inputs to defaults (burnafterreading on)',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li id="burnafterreadingoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="burnafterreading" name="burnafterreading" checked="checked" /> ' +
                    'Burn after reading</label></li><li id="opendiscussionoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="opendiscussion" name="opendiscussion" checked="checked" /> ' +
                    'Open discussion</label></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !PrivateBin.TopNav.getOpenDiscussion()
                );
                query('#burnafterreading').checked = false;
                query('#burnafterreading').removeAttribute('checked');
                results.push(
                    !PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !PrivateBin.TopNav.getOpenDiscussion()
                );
                PrivateBin.TopNav.resetInput();
                results.push(
                    PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !PrivateBin.TopNav.getOpenDiscussion()
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );

        it(
            'reset inputs to defaults (opendiscussion on)',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li id="burnafterreadingoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="burnafterreading" name="burnafterreading" /> ' +
                    'Burn after reading</label></li><li id="opendiscussionoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="opendiscussion" name="opendiscussion" checked="checked" /> ' +
                    'Open discussion</label></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    !PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    PrivateBin.TopNav.getOpenDiscussion()
                );
                query('#opendiscussion').checked = false;
                query('#opendiscussion').removeAttribute('checked');
                query('#burnafterreading').checked = true;
                query('#burnafterreading').setAttribute('checked', 'checked');
                results.push(
                    PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !PrivateBin.TopNav.getOpenDiscussion()
                );
                PrivateBin.TopNav.resetInput();
                results.push(
                    !PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    PrivateBin.TopNav.getOpenDiscussion()
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );

        it(
            'tolerates a missing discussion option (discussion disabled)',
            function () {
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li id="burnafterreadingoption" ' +
                    'class="buttondisabled"><label><input type="checkbox" ' +
                    'id="burnafterreading" name="burnafterreading" /> ' +
                    'Burn after reading</label></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                assert.doesNotThrow(() => PrivateBin.TopNav.resetInput());
                assert.ok(!query('#burnafterreadingoption').classList.contains('buttondisabled'));
                cleanup();
            }
        );
    });

    describe('getExpiration', function () {
        before(function () {
            cleanup();
        });

        it(
            'returns the currently selected expiration date',
            function () {
                document.documentElement.innerHTML =
                    '<select id="pasteExpiration" name="pasteExpiration">' +
                    '<option value="1day">1 day</option>' +
                    '<option value="never">Never</option></select>';
                PrivateBin.TopNav.init();
                assert.strictEqual(PrivateBin.TopNav.getExpiration(), '1day');
                cleanup();
            }
        );

        it(
            'returns the expiration date the user selected',
            function () {
                document.documentElement.innerHTML =
                    '<select id="pasteExpiration" name="pasteExpiration">' +
                    '<option value="1day">1 day</option>' +
                    '<option value="never">Never</option></select>';
                PrivateBin.TopNav.init();
                const select = query('#pasteExpiration');
                select.value = 'never';
                select.dispatchEvent(new window.Event('change'));
                assert.strictEqual(PrivateBin.TopNav.getExpiration(), 'never');
                cleanup();
            }
        );
    });

    describe('setFormat', function () {
        before(function () {
            cleanup();
        });

        const formatter =
            '<li id="formatter" class="nav-item d-flex">' +
            '<select id="pasteFormatter" name="pasteFormatter" class="form-select">' +
            '<option value="plaintext" selected="selected">Plain Text</option>' +
            '<option value="syntaxhighlighting">Source Code</option>' +
            '<option value="markdown">Markdown</option></select></li>';

        it(
            'selects the given format',
            function () {
                fc.assert(fc.property(
                    fc.constantFrom('plaintext', 'syntaxhighlighting', 'markdown'),
                    function (format) {
                        document.documentElement.innerHTML = formatter;
                        PrivateBin.TopNav.init();
                        PrivateBin.TopNav.setFormat(format);
                        assert.strictEqual(query('#pasteFormatter').value, format);
                    }
                ));
                cleanup();
            }
        );

        it(
            'tolerates a missing formatter (formatter selection disabled)',
            function () {
                document.documentElement.innerHTML = '<nav><div id="navbar"></div></nav>';
                PrivateBin.TopNav.init();
                assert.doesNotThrow(() => PrivateBin.TopNav.setFormat('markdown'));
                cleanup();
            }
        );

        it(
            'passes the format the user selected to the viewer',
            function () {
                fc.assert(fc.property(
                    fc.constantFrom('plaintext', 'syntaxhighlighting', 'markdown'),
                    function (format) {
                        // replacing the markup is enough, init binds to the new elements
                        document.documentElement.innerHTML = formatter;
                        PrivateBin.TopNav.init();
                        const select = query('#pasteFormatter');
                        select.value = format;
                        select.dispatchEvent(new window.Event('change'));
                        assert.strictEqual(PrivateBin.PasteViewer.getFormat(), format);
                    }
                ));
                cleanup();
            }
        );
    });

    describe('getFileList', function () {
        before(function () {
            cleanup();
        });

        const File = window.File,
            FileList = window.FileList,
            path = require('path'), // eslint-disable-line global-require
            mime = require('mime-types'); // eslint-disable-line global-require

        // mocking file input as per https://github.com/jsdom/jsdom/issues/1272
        function createFile(file_path) {
            const lastModified = fs.statSync(file_path).mtimeMs;

            return new File(
                [new fs.readFileSync(file_path)],
                path.basename(file_path),
                {
                    lastModified,
                    type: mime.lookup(file_path) || ''
                }
            );
        }

        function addFileList(input, file_paths) {
            if (typeof file_paths === 'string') {
                file_paths = [file_paths];
            } else if (!Array.isArray(file_paths)) {
                throw new Error('file_paths needs to be a file path string or an Array of file path strings');
            }

            const file_list = file_paths.map(fp => createFile(fp));
            Object.setPrototypeOf(file_list, Object.create(FileList.prototype));

            Object.defineProperty(input, 'files', {
                value: file_list,
                writeable: false
            });

            return input;
        }

        it(
            'returns the selected files',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li id="attach" class="hidden ' +
                    'dropdown"><a href="#" class="nav-link dropdown-toggle" ' +
                    'data-bs-toggle="dropdown" role="button" ' +
                    'aria-expanded="false">Attach a file</a>' +
                    '<ul class="dropdown-menu px-2"><li id="filewrap">' +
                    '<div><input type="file" id="file" name="file" /></div>' +
                    '</li><li id="customattachment" class="hidden"></li><li>' +
                    '<a id="fileremovebutton"  href="#">Remove attachment</a>' +
                    '</li></ul></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    PrivateBin.TopNav.getFileList() === null
                );
                addFileList(query('#file'), [
                    '../img/logo.svg',
                    '../img/busy.gif'
                ]);
                const files = PrivateBin.TopNav.getFileList();
                results.push(
                    files[0].name === 'logo.svg' &&
                    files[1].name === 'busy.gif'
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );
    });

    describe('getBurnAfterReading', function () {
        before(function () {
            cleanup();
        });

        it(
            'returns if the burn-after-reading checkbox was ticked',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li id="burnafterreadingoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="burnafterreading" name="burnafterreading" /> ' +
                    'Burn after reading</label></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    !PrivateBin.TopNav.getBurnAfterReading()
                );
                query('#burnafterreading').checked = true;
                results.push(
                    PrivateBin.TopNav.getBurnAfterReading()
                );
                query('#burnafterreading').checked = false;
                query('#burnafterreading').removeAttribute('checked');
                results.push(
                    !PrivateBin.TopNav.getBurnAfterReading()
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );
    });

    describe('getOpenDiscussion', function () {
        before(function () {
            cleanup();
        });

        it(
            'returns if the open-discussion checkbox was ticked',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li id="opendiscussionoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="opendiscussion" name="opendiscussion" /> ' +
                    'Open discussion</label></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    !PrivateBin.TopNav.getOpenDiscussion()
                );
                query('#opendiscussion').checked = true;
                results.push(
                    PrivateBin.TopNav.getOpenDiscussion()
                );
                query('#opendiscussion').checked = false;
                query('#opendiscussion').removeAttribute('checked');
                results.push(
                    !PrivateBin.TopNav.getOpenDiscussion()
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );
    });

    describe('getPassword', function () {
        before(function () {
            globalThis.cleanup();
        });

        it('returns the contents of the password input', () => {
            fc.assert(fc.property(
                fc.string(),
                function (password) {
                    password = password.replace(/\r+|\n+/g, '');
                    let results = [];
                    document.documentElement.innerHTML =
                        '<nav><div id="navbar"><ul><li><div id="password" ' +
                        'class="navbar-form hidden"><input type="password" ' +
                        'id="passwordinput" placeholder="Password (recommended)" ' +
                        'class="form-control" size="23" /></div></li></ul></div></nav>';
                    PrivateBin.TopNav.init();
                    results.push(
                        PrivateBin.TopNav.getPassword() === ''
                    );
                    query('#passwordinput').value = password;
                    results.push(
                        PrivateBin.TopNav.getPassword() === password
                    );
                    query('#passwordinput').value = '';
                    results.push(
                        PrivateBin.TopNav.getPassword() === ''
                    );
                    globalThis.cleanup();
                    const result = results.every(element => element);
                    if (!result) {
                        console.log(results);
                    }
                    return result;
                }
            ));
        });
    });

    describe('getCustomAttachment', function () {
        before(function () {
            cleanup();
        });

        it(
            'returns the custom attachment element',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul><li id="attach" class="hidden ' +
                    'dropdown"><a href="#" class="nav-link dropdown-toggle" ' +
                    'data-bs-toggle="dropdown" role="button" ' +
                    'aria-expanded="false">Attach a file</a>' +
                    '<ul class="dropdown-menu px-2"><li id="filewrap">' +
                    '<div><input type="file" id="file" name="file" /></div>' +
                    '</li><li id="customattachment" class="hidden"></li><li>' +
                    '<a id="fileremovebutton"  href="#">Remove attachment</a>' +
                    '</li></ul></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    !PrivateBin.TopNav.getCustomAttachment().classList.contains('test')
                );
                query('#customattachment').classList.add('test');
                results.push(
                    PrivateBin.TopNav.getCustomAttachment().classList.contains('test')
                );
                cleanup();
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
            }
        );
    });

    describe('hideAllButtons', function () {
        before(function () {
            cleanup();
        });

        it(
            'hides all buttons correctly',
            function () {
                // Insert any setup code needed for the hideAllButtons function
                // Example: Initialize the DOM elements required for testing
                document.body.innerHTML =
                    '<nav class="navbar navbar-inverse navbar-static-top">' +
                    '<div id="navbar" class="navbar-collapse collapse"><ul ' +
                    'class="nav navbar-nav"><li><button id="newbutton" ' +
                    'type="button" class="hidden btn btn-warning navbar-btn">' +
                    '<svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="img/bootstrap-icons.svg#file-earmark"></use></svg>' +
                    ' New</button><button id="clonebutton" type="button"' +
                    ' class="hidden btn btn-warning navbar-btn">' +
                    '<svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="img/bootstrap-icons.svg#copy"></use></svg>' +
                    ' Clone</button><button ' +
                    'id="rawtextbutton" type="button" class="hidden btn ' +
                    'btn-warning navbar-btn"><svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="img/bootstrap-icons.svg#filetype-txt"></use></svg> ' +
                    'Raw text</button><button id="qrcodelink" type="button" ' +
                    'data-toggle="modal" data-target="#qrcodemodal" ' +
                    'class="hidden btn btn-warning navbar-btn">' +
                    '<svg width="16" height="16" fill="currentColor" aria-hidden="true"><use href="img/bootstrap-icons.svg#qr-code"></use></svg>' +
                    ' QR code</button></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                PrivateBin.TopNav.hideAllButtons();

                assert.ok(query('#newbutton').classList.contains('hidden'));
                assert.ok(query('#clonebutton').classList.contains('hidden'));
                assert.ok(query('#rawtextbutton').classList.contains('hidden'));
                assert.ok(query('#qrcodelink').classList.contains('hidden'));
                cleanup();
            }
        );
    });

    describe('showRetryButton & hideRetryButton', function () {
        afterEach(function () {
            cleanup();
        });

        it(
            'shows and hides the retry button with its list item and runs the callback once',
            function () {
                cleanup();
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul class="navbar-nav">' +
                    '<li class="nav-item hidden"><button id="retrybutton" ' +
                    'type="button" class="hidden">Retry</button></li>' +
                    '<li><button id="newbutton" type="button">New</button>' +
                    '</li></ul></div></nav>';
                PrivateBin.TopNav.init();
                let calls = 0;
                PrivateBin.TopNav.setRetryCallback(function () {
                    calls++;
                });
                const retryItem = query('#retrybutton').parentElement;

                assert.ok(query('#retrybutton').classList.contains('hidden'));
                assert.ok(retryItem.classList.contains('hidden'));

                PrivateBin.TopNav.showRetryButton();
                assert.ok(!query('#retrybutton').classList.contains('hidden'));
                assert.ok(!retryItem.classList.contains('hidden'));

                query('#retrybutton').click();
                assert.strictEqual(calls, 1);

                PrivateBin.TopNav.hideRetryButton();
                assert.ok(query('#retrybutton').classList.contains('hidden'));
                assert.ok(retryItem.classList.contains('hidden'));

                PrivateBin.TopNav.showRetryButton();
                PrivateBin.TopNav.hideRetryButton();
                assert.ok(query('#retrybutton').classList.contains('hidden'));
                assert.ok(retryItem.classList.contains('hidden'));
                assert.ok(!query('#newbutton').classList.contains('hidden'));
                assert.strictEqual(calls, 1);
            }
        );
    });

    describe('rawText', function () {
        before(function () {
            cleanup();
        });

        // TODO triggers error messages in jsDOM since version 12, but passes
        it(
            'displays raw text view correctly',
            function () {
                const clean = globalThis.cleanup('', {url: 'https://privatebin.net/?0123456789abcdef#1'});
                document.documentElement.innerHTML = `
                <li id="loadingindicator" class="hidden"></li>
                <button id="rawtextbutton"></button>`;
                const sample = 'example';
                PrivateBin.Alert.init(); // required because of locading indiator being used
                PrivateBin.TopNav.init();
                PrivateBin.PasteViewer.setText(sample);
                PrivateBin.Helper.reset();
                query('#rawtextbutton').click();
                assert.strictEqual(document.querySelector('pre').textContent, sample);
                clean();
            }
        );
    });
});
