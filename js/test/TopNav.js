'use strict';
require('../common');
const fc = require('fast-check');

function query(selector) {
    if (selector.startsWith('#')) {
        return document.getElementById(selector.slice(1));
    }
    return document.querySelector(selector);
}

/**
 * builds the navigation with the e-mail button and its confirmation dialog, as
 * in the bootstrap5 template
 *
 * @param {boolean} withShortUrl - add the link of a shortened document URL
 */
function buildEmailDom(withShortUrl) {
    document.documentElement.innerHTML =
        '<nav><div id="navbar"><ul><li>' +
        '<button id="clonebutton" type="button" class="btn btn-secondary">' +
        '<svg width="16" height="16" fill="currentColor" aria-hidden="true">' +
        '<use href="img/bootstrap-icons.svg#copy"></use></svg> Clone</button>' +
        '</li><li>' +
        '<button id="emaillink" type="button" class="hidden btn btn-secondary">Email</button>' +
        '</li></ul></div></nav>' +
        '<input id="burnafterreadingoption" type="checkbox">' +
        (withShortUrl ?
            '<div id="pastelink">Your document is ' +
            '<a id="pasteurl" href="https://short.example/xYz">https://short.example/xYz</a> ' +
            '<span id="copyhint">(Hit <kbd>Ctrl</kbd>+<kbd>c</kbd> to copy)</span></div>' :
            '') +
        '<div id="emailconfirmmodal" tabindex="-1" class="modal" role="dialog" aria-hidden="true">' +
        '<div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-body row">' +
        '<button id="emailconfirm-timezone-current" type="button">Use Current Timezone</button>' +
        '<button id="emailconfirm-timezone-utc" type="button">Convert To UTC</button>' +
        '</div></div></div></div>';
}

/**
 * replaces window.open, to record the e-mails sent via mailto: URLs
 *
 * @returns {{getUrl: function, getCount: function, reset: function, restore: function}}
 */
function mockWindowOpen() {
    const originalOpen = window.open;
    let openedUrl = null,
        openedCount = 0;
    window.open = function (url) {
        openedUrl = url;
        ++openedCount;
        return {};
    };
    return {
        getUrl: () => openedUrl,
        getCount: () => openedCount,
        reset: () => {
            openedUrl = null;
            openedCount = 0;
        },
        restore: () => {
            window.open = originalOpen;
        }
    };
}

/**
 * extracts and decodes a parameter of a mailto: URL
 *
 * @param {string} mailtoUrl
 * @param {string} name - i.e. subject or body
 * @returns {string}
 */
function mailtoParameter(mailtoUrl, name) {
    assert.match(mailtoUrl, /^mailto:\?/, 'expected a mailto: URL');
    const match = mailtoUrl.match(new RegExp('[?&]' + name + '=([^&]*)'));
    assert.ok(match, 'expected the mailto: URL to have a ' + name + '= parameter');
    return decodeURIComponent(match[1]);
}

describe('TopNav', function () {
    describe('showEmailButton & hideEmailButton', function () {
        let windowOpen;

        beforeEach(function () {
            cleanup();
            windowOpen = mockWindowOpen();
        });

        afterEach(function () {
            windowOpen.restore();
            cleanup();
        });

        it('does nothing when the e-mail button is omitted from the page', function () {
            document.documentElement.innerHTML =
                '<nav><div id="navbar"><ul><li><button id="newbutton" ' +
                'type="button">New</button></li></ul></div></nav>';
            PrivateBin.TopNav.init();

            // cleanup() reloads PrivateBin, dropping this replacement
            let errorShown = false;
            PrivateBin.Alert.showError = function () {
                errorShown = true;
            };

            assert.doesNotThrow(() => PrivateBin.TopNav.showEmailButton());
            assert.strictEqual(errorShown, false);
        });

        it('sends a single e-mail after the confirmation dialog was dismissed', function () {
            buildEmailDom(false);
            PrivateBin.TopNav.init();
            PrivateBin.TopNav.showEmailButton(3600);

            // open the dialog and dismiss it without choosing a timezone
            query('#emaillink').click();
            assert.strictEqual(windowOpen.getCount(), 0, 'no e-mail before choosing a timezone');

            query('#emaillink').click();
            query('#emailconfirm-timezone-current').click();
            assert.strictEqual(windowOpen.getCount(), 1, 'exactly one e-mail with the current timezone');

            query('#emaillink').click();
            query('#emailconfirm-timezone-utc').click();
            assert.strictEqual(windowOpen.getCount(), 2, 'exactly one more e-mail with UTC');
        });

        it('sends a single e-mail per click after the button was shown again', function () {
            buildEmailDom(false);
            PrivateBin.TopNav.init();
            // i.e. sending a second document without reloading the page
            PrivateBin.TopNav.showEmailButton(0);
            PrivateBin.TopNav.showEmailButton(0);

            query('#emaillink').click();
            assert.strictEqual(windowOpen.getCount(), 1, 'exactly one e-mail per click');
        });

        it('uses the expiration of the document the button was last shown for', function () {
            buildEmailDom(false);
            PrivateBin.TopNav.init();
            // first document expires, the second never does
            PrivateBin.TopNav.showEmailButton(3600);
            PrivateBin.TopNav.showEmailButton(0);

            query('#emaillink').click();
            assert.strictEqual(windowOpen.getCount(), 1, 'sent directly, without confirmation dialog');
            assert.doesNotMatch(mailtoParameter(windowOpen.getUrl(), 'body'), /expire/i, 'body has no expiration');

            query('#emailconfirm-timezone-current').click();
            assert.strictEqual(windowOpen.getCount(), 1, 'no e-mail from a stale confirmation dialog');
        });

        it('sends no e-mail from a confirmation dialog opened for an earlier document', function () {
            buildEmailDom(false);
            PrivateBin.TopNav.init();
            PrivateBin.TopNav.showEmailButton(3600);

            // open the dialog, but start a new document instead of choosing
            query('#emaillink').click();
            PrivateBin.TopNav.hideEmailButton();
            PrivateBin.TopNav.showEmailButton(0);

            query('#emailconfirm-timezone-current').click();
            query('#emailconfirm-timezone-utc').click();
            assert.strictEqual(windowOpen.getCount(), 0, 'no e-mail from a stale confirmation dialog');
        });

        it('sends no e-mail after the button was hidden', function () {
            buildEmailDom(false);
            PrivateBin.TopNav.init();
            PrivateBin.TopNav.showEmailButton(0);
            PrivateBin.TopNav.hideEmailButton();

            // i.e. a keyboard shortcut or assistive technology activating it
            query('#emaillink').click();
            assert.strictEqual(windowOpen.getCount(), 0, 'no e-mail from a hidden button');
        });

        it('sends exactly one e-mail, regardless of how often the button was shown or the dialog dismissed', function () {
            fc.assert(fc.property(
                fc.integer({min: 1, max: 5}),
                fc.integer({min: 0, max: 5}),
                fc.constantFrom('current', 'utc'),
                function (shown, dismissed, timezone) {
                    // replacing the markup is enough, init binds to the new elements
                    buildEmailDom(false);
                    PrivateBin.TopNav.init();
                    for (let i = 0; i < shown; ++i) {
                        PrivateBin.TopNav.showEmailButton(3600);
                    }
                    windowOpen.reset();

                    for (let i = 0; i < dismissed; ++i) {
                        query('#emaillink').click();
                    }
                    query('#emaillink').click();
                    query('#emailconfirm-timezone-' + timezone).click();
                    assert.strictEqual(windowOpen.getCount(), 1);
                }
            ));
        });
    });

    describe('sendEmail', function () {
        let windowOpen;

        beforeEach(function () {
            cleanup();
            windowOpen = mockWindowOpen();
        });

        afterEach(function () {
            windowOpen.restore();
            cleanup();
        });

        it('puts the short URL into the mail body, if there is one', function () {
            buildEmailDom(true);
            PrivateBin.TopNav.init();
            PrivateBin.TopNav.showEmailButton(0);
            assert.ok(!query('#emaillink').classList.contains('hidden'), 'e-mail button is shown');

            query('#emaillink').click();
            assert.ok(windowOpen.getUrl(), 'an e-mail was sent');
            const body = mailtoParameter(windowOpen.getUrl(), 'body');
            assert.match(body, /https:\/\/short\.example\/xYz/, 'body contains the short URL');
            assert.doesNotMatch(body, /undefined/, 'body never contains "undefined"');
        });

        it('puts the page URL into the mail body, if there is no short URL', function () {
            buildEmailDom(false);
            PrivateBin.TopNav.init();
            PrivateBin.TopNav.showEmailButton(0);
            assert.ok(!query('#emaillink').classList.contains('hidden'), 'e-mail button is shown');

            query('#emaillink').click();
            assert.ok(windowOpen.getUrl(), 'an e-mail was sent');
            const body = mailtoParameter(windowOpen.getUrl(), 'body');
            assert.match(body, new RegExp(window.location.href), 'body contains the page URL');
            assert.doesNotMatch(body, /undefined/, 'body never contains "undefined"');
        });

        it('uses the burn-after-reading state of the viewed document, not the instance default', function () {
            buildEmailDom(false);
            const burnAfterReading = document.createElement('input');
            burnAfterReading.id = 'burnafterreading';
            burnAfterReading.type = 'checkbox';
            burnAfterReading.checked = true;
            document.body.appendChild(burnAfterReading);
            PrivateBin.TopNav.init();
            PrivateBin.TopNav.showEmailButton(0, false);

            query('#emaillink').click();
            assert.doesNotMatch(
                mailtoParameter(windowOpen.getUrl(), 'body'),
                /only be accessed once/,
                'body uses the metadata of the viewed document'
            );
        });

        it('names the instance in the mail subject', function () {
            buildEmailDom(false);
            // buildEmailDom() replaces the whole document, including its title
            document.title = 'My PrivateBin Instance';
            PrivateBin.TopNav.init();
            PrivateBin.TopNav.showEmailButton(0);

            query('#emaillink').click();
            assert.ok(windowOpen.getUrl(), 'an e-mail was sent');
            assert.match(mailtoParameter(windowOpen.getUrl(), 'subject'), /My PrivateBin Instance/);
        });

        it('names the instance in the mail subject after the expiration was confirmed', function () {
            buildEmailDom(true);
            document.title = 'My PrivateBin Instance';
            PrivateBin.TopNav.init();
            // a remaining time asks to confirm the timezone of the expiration
            PrivateBin.TopNav.showEmailButton(3600);

            query('#emaillink').click();
            query('#emailconfirm-timezone-current').click();
            assert.ok(windowOpen.getUrl(), 'an e-mail was sent');
            assert.match(mailtoParameter(windowOpen.getUrl(), 'subject'), /My PrivateBin Instance/);
        });

        it('percent-encodes the mail subject, i.e. spaces in the instance name', function () {
            buildEmailDom(false);
            document.title = 'My Cool Instance';
            PrivateBin.TopNav.init();
            PrivateBin.TopNav.showEmailButton(0);

            query('#emaillink').click();
            const rawSubjectParam = windowOpen.getUrl().split('&body=')[0];
            assert.strictEqual(
                rawSubjectParam,
                `mailto:?subject=${encodeURIComponent('Encrypted note on My Cool Instance')}`,
                'raw subject parameter is exactly the encoded translated string'
            );
            assert.doesNotMatch(rawSubjectParam, / /, 'raw mailto: URL contains no literal space');
            assert.strictEqual(
                mailtoParameter(windowOpen.getUrl(), 'subject'),
                'Encrypted note on My Cool Instance',
                'decoded subject round-trips exactly'
            );
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
                    <span class="glyphicon glyphicon-file" aria-hidden="true">
                        </span> New</button><button id="clonebutton" type="button" class="hidden btn btn-warning navbar-btn">
                    <span class="glyphicon glyphicon-duplicate" aria-hidden="true"></span> Clone</button>
                    <button id="rawtextbutton" type="button" class="hidden btn btn-warning navbar-btn">
                    <span class="glyphicon glyphicon-text-background" aria-hidden="true"></span> Raw text</button>
                    <button id="downloadtextbutton" type="button" class="hidden btn btn-default navbar-btn"></button>
                    <button id="qrcodelink" type="button" data-toggle="modal" data-target="#qrcodemodal" class="hidden btn btn-warning navbar-btn"/>
                    <span class="glyphicon glyphicon-qrcode" aria-hidden="true"></span> QR code</button></li></ul></div>
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
                    '<span class="glyphicon glyphicon-duplicate" aria-hidden=' +
                    '"true"></span> Clone</button></li></ul></div></nav>';
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
                    'btn-warning navbar-btn"><span class="glyphicon ' +
                    'glyphicon-text-background" aria-hidden="true"></span> ' +
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
                    'dropdown"><a href="#" class="dropdown-toggle" data-' +
                    'toggle="dropdown" role="button" aria-haspopup="true" ' +
                    'aria-expanded="false">Attach a file <span class="caret">' +
                    '</span></a><ul class="dropdown-menu"><li id="filewrap">' +
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
                    'dropdown"><a href="#" class="dropdown-toggle" data-' +
                    'toggle="dropdown" role="button" aria-haspopup="true" ' +
                    'aria-expanded="false">Attach a file <span class="caret">' +
                    '</span></a><ul class="dropdown-menu"><li id="filewrap">' +
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

        it(
            'collapses the navigation when displayed on a small screen',
            function () {
                let results = [];
                document.documentElement.innerHTML =
                    '<nav><div class="navbar-header"><button type="button" ' +
                    'class="navbar-toggle collapsed" data-toggle="collapse" ' +
                    'data-target="#navbar" aria-expanded="false" aria-controls' +
                    '="navbar">Toggle navigation</button><a class="reloadlink ' +
                    'navbar-brand" href=""><img alt="PrivateBin" ' +
                    'src="img/icon.svg" width="38" /></a></div><div ' +
                    'id="navbar"><ul><li><button id="newbutton" type=' +
                    '"button" class="hidden">New</button></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                results.push(
                    query('.navbar-toggle').classList.contains('collapsed') &&
                    query('#navbar').getAttribute('aria-expanded') !== 'true'
                );
                PrivateBin.TopNav.collapseBar();
                results.push(
                    query('.navbar-toggle').classList.contains('collapsed') &&
                    query('#navbar').getAttribute('aria-expanded') !== 'true'
                );
                /*
                with the upgrade for bootstrap-3.3.7.js to bootstrap-3.4.1.js
                the mobile interface detection changed to check if the
                ontouchstart event exists, which broke this section of the test
                const toggleBtn = document.querySelector('.navbar-toggle');
                toggleBtn.dispatchEvent(new MouseEvent('click'));
                results.push(
                    !toggleBtn.classList.contains('collapsed') &&
                    document.getElementById('navbar').getAttribute('aria-expanded') == 'true'
                );
                PrivateBin.TopNav.collapseBar();
                results.push(
                    document.querySelector('.navbar-toggle').classList.contains('collapsed') &&
                    document.getElementById('navbar').getAttribute('aria-expanded') == 'false'
                );
                */
                const result = results.every(element => element);
                if (!result) {
                    console.log(results);
                }
                assert.ok(result);
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
                    'dropdown"><a href="#" class="dropdown-toggle" data-' +
                    'toggle="dropdown" role="button" aria-haspopup="true" ' +
                    'aria-expanded="false">Attach a file <span class="caret">' +
                    '</span></a><ul class="dropdown-menu"><li id="filewrap">' +
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
                    'dropdown"><a href="#" class="dropdown-toggle" data-' +
                    'toggle="dropdown" role="button" aria-haspopup="true" ' +
                    'aria-expanded="false">Attach a file <span class="caret">' +
                    '</span></a><ul class="dropdown-menu"><li id="filewrap">' +
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
                    '<span class="glyphicon glyphicon-file" aria-hidden="true">' +
                    '</span> New</button><button id="clonebutton" type="button"' +
                    ' class="hidden btn btn-warning navbar-btn">' +
                    '<span class="glyphicon glyphicon-duplicate" ' +
                    'aria-hidden="true"></span> Clone</button><button ' +
                    'id="rawtextbutton" type="button" class="hidden btn ' +
                    'btn-warning navbar-btn"><span class="glyphicon ' +
                    'glyphicon-text-background" aria-hidden="true"></span> ' +
                    'Raw text</button><button id="qrcodelink" type="button" ' +
                    'data-toggle="modal" data-target="#qrcodemodal" ' +
                    'class="hidden btn btn-warning navbar-btn"><span ' +
                    'class="glyphicon glyphicon-qrcode" aria-hidden="true">' +
                    '</span> QR code</button></li></ul></div></nav>';
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
            delete global.bootstrap;
            cleanup();
        });

        it(
            'shows and hides the Bootstrap 5 retry button with its list item and runs the callback once',
            function () {
                cleanup();
                global.bootstrap = {};
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

        it(
            'toggles only the retry button on legacy Bootstrap 3 and page templates',
            function () {
                cleanup();
                delete global.bootstrap;
                document.documentElement.innerHTML =
                    '<nav><div id="navbar"><ul class="nav navbar-nav"><li>' +
                    '<button id="retrybutton" type="button" class="hidden">' +
                    'Retry</button><button id="newbutton" type="button">' +
                    'New</button></li></ul></div></nav>';
                PrivateBin.TopNav.init();
                let calls = 0;
                PrivateBin.TopNav.setRetryCallback(function () {
                    calls++;
                });
                const retryItem = query('#retrybutton').parentElement;

                assert.ok(query('#retrybutton').classList.contains('hidden'));
                assert.ok(!retryItem.classList.contains('hidden'));

                PrivateBin.TopNav.showRetryButton();
                assert.ok(!query('#retrybutton').classList.contains('hidden'));
                assert.ok(!retryItem.classList.contains('hidden'));
                assert.ok(!query('#newbutton').classList.contains('hidden'));

                query('#retrybutton').click();
                assert.strictEqual(calls, 1);

                PrivateBin.TopNav.hideRetryButton();
                assert.ok(query('#retrybutton').classList.contains('hidden'));
                assert.ok(!retryItem.classList.contains('hidden'));
                assert.ok(!query('#newbutton').classList.contains('hidden'));

                PrivateBin.TopNav.showRetryButton();
                PrivateBin.TopNav.hideRetryButton();
                assert.ok(query('#retrybutton').classList.contains('hidden'));
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
