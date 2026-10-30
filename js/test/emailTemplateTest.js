'use strict';
require('../common');
const fc = require('fast-check');

// DOM builder that mirrors bootstrap5.php navbar
function buildEmailDomNoShortUrl() {
    document.documentElement.innerHTML =
        // TopNav expects initially hidden #emaillink BUTTON.
        '<nav><div id="navbar"><ul>' +
          '<li>' +
            '<button id="clonebutton" type="button" class="btn btn-warning navbar-btn">' +
              '<span class="glyphicon glyphicon-duplicate" aria-hidden="true"></span> Clone' +
            '</button>' +
          '</li>' +
          '<li>' +
            '<button id="emaillink" type="button" class="hidden btn btn-secondary">Email</button>' +
          '</li>' +
        '</ul></div></nav>' +
        '<input id="burnafterreadingoption" type="checkbox">' +
        // include dummy email confirm modal for sendEmail
        '<div id="emailconfirmmodal" class="hidden">' +
            '<div id="emailconfirm-timezone-current"></div>' +
            '<div id="emailconfirm-timezone-utc"></div>' +
        '</div>'
}

// DOM builder that adds the shortener result block
function buildEmailDomWithShortUrl() {
    buildEmailDomNoShortUrl();
    document.documentElement.innerHTML =
        // TopNav expectsinitially hidden #emaillink BUTTON.
        '<nav><div id="navbar"><ul>' +
          '<li>' +
            '<button id="clonebutton" type="button" class="btn btn-warning navbar-btn">' +
              '<span class="glyphicon glyphicon-duplicate" aria-hidden="true"></span> Clone' +
            '</button>' +
          '</li>' +
          '<li>' +
            '<button id="emaillink" type="button" class="hidden btn btn-secondary">Email</button>' +
          '</li>' +
        '</ul></div></nav>' +
        '<input id="burnafterreadingoption" type="checkbox">' +
        '<div id="pastelink">Your document is ' +
          '<a id="pasteurl" href="https://short.example/xYz">https://short.example/xYz</a> ' +
          '<span id="copyhint">(Hit <kbd>Ctrl</kbd>+<kbd>c</kbd> to copy)</span>' +
        '</div>' +
        // add a minimal email confirmation modal so sendEmail does not crash
        '<div id="emailconfirmmodal" class="hidden">' +
            '<div id="emailconfirm-timezone-current"></div>' +
            '<div id="emailconfirm-timezone-utc"></div>' +
        '</div>'
}


function makeWindowOpenMock() {
    const originalOpen = window.open;
    let openedUrl = null;
    let openedCount = 0;
    let mockRestoreFn = null;

    if (typeof jest !== 'undefined' && typeof jest.spyOn === 'function') {
        const spy = jest.spyOn(window, 'open').mockImplementation((url) => {
            openedUrl = url;
            openedCount++;
            return {};
        });
        mockRestoreFn = () => spy.mockRestore();
    } else {
        window.open = function (url) {
            openedUrl = url;
            openedCount++;
            return {};
        };
        mockRestoreFn = () => { window.open = originalOpen; };
    }

    return {
        getUrl: () => openedUrl,
        getCount: () => openedCount,
        restore: () => {
            if (mockRestoreFn) {
                mockRestoreFn();
            }
        }
    };
}


// Extract and decode the body from a "mailto:?subject=...&body=..." URL.
function extractMailtoBody(mailtoUrl) {
    assert.match(mailtoUrl, /^mailto:\?/, 'expected a mailto: URL');
    const match = mailtoUrl.match(/[?&]body=([^&]*)/);
    assert.ok(match, 'expected the mailto: URL to have a body= parameter');
    return decodeURIComponent(match[1]);
}

// Extract and decode the subject from a "mailto:?subject=...&body=..." URL.
function extractMailtoSubject(mailtoUrl) {
    assert.match(mailtoUrl, /^mailto:\?/, 'expected a mailto: URL');
    const match = mailtoUrl.match(/[?&]subject=([^&]*)/);
    assert.ok(match, 'expected the mailto: URL to have a subject= parameter');
    return decodeURIComponent(match[1]);
}

describe('Email - mail body content (short URL vs. fallback)', function () {
    beforeEach(function () {
        cleanup(); // provided by common
    });

    it('Uses the short URL when #pasteurl is present and never includes "undefined"', function () {
        buildEmailDomWithShortUrl(); // with #pastelink/#pasteurl
        PrivateBin.TopNav.init();
        PrivateBin.TopNav.showEmailButton(0);

        const emailBtn = document.getElementById('emaillink');
        assert.ok(!emailBtn.classList.contains('hidden'), '#emaillink should be visible after showEmailButton');

        const { getUrl, restore } = makeWindowOpenMock();
        try {
            emailBtn.click();
            document.getElementById('emailconfirm-timezone-current').click();

            const openedUrl = getUrl();
            assert.ok(openedUrl, 'window.open should have been called');

            const body = extractMailtoBody(openedUrl);
            assert.match(body, /https:\/\/short\.example\/xYz/, 'email body should include the short URL');
            assert.doesNotMatch(body, /undefined/, 'email body must not contain "undefined"');
        } finally {
            restore();
        }
    });

    it('Falls back to window.location.href when #pasteurl is absent and never includes "undefined"', function () {
        buildEmailDomNoShortUrl(); // No #pasteurl
        PrivateBin.TopNav.init();
        PrivateBin.TopNav.showEmailButton(0);

        const emailBtn = document.getElementById('emaillink');
        assert.ok(!emailBtn.classList.contains('hidden'), '#emaillink should be visible after showEmailButton');

        const { getUrl, restore } = makeWindowOpenMock();
        try {
            emailBtn.click();
            document.getElementById('emailconfirm-timezone-current').click();

            const openedUrl = getUrl();
            assert.ok(openedUrl, 'window.open should have been called');

            const body = extractMailtoBody(openedUrl);
            assert.match(body, new RegExp(window.location.href), 'email body should include the fallback page URL');
            assert.doesNotMatch(body, /undefined/, 'email body must not contain "undefined"');
        } finally {
            restore();
        }
    });

    it('Uses the viewed paste burn-after-reading state instead of the instance default', function () {
        buildEmailDomNoShortUrl();
        const burnAfterReading = document.createElement('input');
        burnAfterReading.id = 'burnafterreading';
        burnAfterReading.type = 'checkbox';
        burnAfterReading.checked = true;
        document.body.appendChild(burnAfterReading);
        PrivateBin.TopNav.init();
        PrivateBin.TopNav.showEmailButton(0, false);

        const { getUrl, restore } = makeWindowOpenMock();
        try {
            document.getElementById('emaillink').click();
            document.getElementById('emailconfirm-timezone-current').click();

            const body = extractMailtoBody(getUrl());
            assert.doesNotMatch(
                body,
                /only be accessed once/,
                'email body must use the viewed paste metadata'
            );
        } finally {
            restore();
        }
    });
});

describe('Email - mail subject', function () {
    beforeEach(function () {
        cleanup(); // provided by common
    });

    it('Includes a non-empty subject naming the instance, with no expiration confirmation step', function () {
        buildEmailDomNoShortUrl();
        // buildEmailDomNoShortUrl() replaces documentElement.innerHTML (dropping <title>),
        // so document.title must be (re-)set after calling it, not before.
        document.title = 'My PrivateBin Instance';
        PrivateBin.TopNav.init();
        PrivateBin.TopNav.showEmailButton(0);

        const { getUrl, restore } = makeWindowOpenMock();
        try {
            document.getElementById('emaillink').click();

            const openedUrl = getUrl();
            assert.ok(openedUrl, 'window.open should have been called');

            const subject = extractMailtoSubject(openedUrl);
            assert.match(subject, /My PrivateBin Instance/, 'subject should name the instance');
        } finally {
            restore();
        }
    });

    it('Includes the same subject after the expiration confirmation step', function () {
        buildEmailDomWithShortUrl();
        document.title = 'My PrivateBin Instance';
        PrivateBin.TopNav.init();
        // a non-zero remaining time routes through the timezone confirmation modal
        PrivateBin.TopNav.showEmailButton(3600);

        const { getUrl, restore } = makeWindowOpenMock();
        try {
            document.getElementById('emaillink').click();
            document.getElementById('emailconfirm-timezone-current').click();

            const openedUrl = getUrl();
            assert.ok(openedUrl, 'window.open should have been called');

            const subject = extractMailtoSubject(openedUrl);
            assert.match(subject, /My PrivateBin Instance/, 'subject should name the instance');
        } finally {
            restore();
        }
    });

    it('Percent-encodes the subject so a space-containing instance name does not break the mailto URL', function () {
        buildEmailDomNoShortUrl();
        document.title = 'My Cool Instance';
        PrivateBin.TopNav.init();
        PrivateBin.TopNav.showEmailButton(0);

        const { getUrl, restore } = makeWindowOpenMock();
        try {
            document.getElementById('emaillink').click();

            const openedUrl = getUrl();
            const rawSubjectParam = openedUrl.split('&body=')[0];
            assert.strictEqual(
                rawSubjectParam,
                `mailto:?subject=${encodeURIComponent('Encrypted note on My Cool Instance')}`,
                'raw subject parameter should be exactly the encoded translated string'
            );
            assert.doesNotMatch(rawSubjectParam, / /, 'raw mailto URL must not contain a literal space');

            const subject = extractMailtoSubject(openedUrl);
            assert.strictEqual(subject, 'Encrypted note on My Cool Instance', 'decoded subject should round-trip exactly');
        } finally {
            restore();
        }
    });
});

describe('Email - click handlers', function () {
    beforeEach(function () {
        cleanup(); // provided by common
    });

    it('Sends a single e-mail after the confirmation dialog was dismissed', function () {
        buildEmailDomNoShortUrl();
        PrivateBin.TopNav.init();
        PrivateBin.TopNav.showEmailButton(3600);

        const { getCount, restore } = makeWindowOpenMock();
        try {
            // open the dialog and dismiss it without choosing a timezone
            document.getElementById('emaillink').click();
            assert.strictEqual(getCount(), 0, 'no e-mail before choosing a timezone');

            document.getElementById('emaillink').click();
            document.getElementById('emailconfirm-timezone-current').click();
            assert.strictEqual(getCount(), 1, 'exactly one e-mail with the current timezone');

            document.getElementById('emaillink').click();
            document.getElementById('emailconfirm-timezone-utc').click();
            assert.strictEqual(getCount(), 2, 'exactly one more e-mail with UTC');
        } finally {
            restore();
        }
    });

    it('Sends a single e-mail per click after the button was shown again', function () {
        buildEmailDomNoShortUrl();
        PrivateBin.TopNav.init();
        // i.e. sending a second document without reloading the page
        PrivateBin.TopNav.showEmailButton(0);
        PrivateBin.TopNav.showEmailButton(0);

        const { getCount, restore } = makeWindowOpenMock();
        try {
            document.getElementById('emaillink').click();
            assert.strictEqual(getCount(), 1, 'exactly one e-mail per click');
        } finally {
            restore();
        }
    });

    it('Uses the expiration of the document the button was last shown for', function () {
        buildEmailDomNoShortUrl();
        PrivateBin.TopNav.init();
        // first document expires, the second never does
        PrivateBin.TopNav.showEmailButton(3600);
        PrivateBin.TopNav.showEmailButton(0);

        const { getUrl, getCount, restore } = makeWindowOpenMock();
        try {
            document.getElementById('emaillink').click();
            assert.strictEqual(getCount(), 1, 'sent directly, without confirmation dialog');
            assert.doesNotMatch(extractMailtoBody(getUrl()), /expire/i, 'body has no expiration');

            document.getElementById('emailconfirm-timezone-current').click();
            assert.strictEqual(getCount(), 1, 'no e-mail from a stale confirmation dialog');
        } finally {
            restore();
        }
    });

    it('Sends no e-mail from a confirmation dialog opened for an earlier document', function () {
        buildEmailDomNoShortUrl();
        PrivateBin.TopNav.init();
        PrivateBin.TopNav.showEmailButton(3600);

        const { getCount, restore } = makeWindowOpenMock();
        try {
            // open the dialog, but start a new document instead of choosing
            document.getElementById('emaillink').click();
            PrivateBin.TopNav.hideEmailButton();
            PrivateBin.TopNav.showEmailButton(0);

            document.getElementById('emailconfirm-timezone-current').click();
            document.getElementById('emailconfirm-timezone-utc').click();
            assert.strictEqual(getCount(), 0, 'no e-mail from a stale confirmation dialog');
        } finally {
            restore();
        }
    });

    it('Sends no e-mail after the button was hidden', function () {
        buildEmailDomNoShortUrl();
        PrivateBin.TopNav.init();
        PrivateBin.TopNav.showEmailButton(0);
        PrivateBin.TopNav.hideEmailButton();

        const { getCount, restore } = makeWindowOpenMock();
        try {
            // i.e. a keyboard shortcut or assistive technology activating it
            document.getElementById('emaillink').click();
            assert.strictEqual(getCount(), 0, 'no e-mail from a hidden button');
        } finally {
            restore();
        }
    });

    it('Sends exactly one e-mail, regardless of how often the button was shown or the dialog dismissed', function () {
        fc.assert(fc.property(
            fc.integer({min: 1, max: 5}),
            fc.integer({min: 0, max: 5}),
            fc.constantFrom('current', 'utc'),
            function (shown, dismissed, timezone) {
                cleanup();
                buildEmailDomNoShortUrl();
                PrivateBin.TopNav.init();
                for (let i = 0; i < shown; ++i) {
                    PrivateBin.TopNav.showEmailButton(3600);
                }

                const { getCount, restore } = makeWindowOpenMock();
                try {
                    for (let i = 0; i < dismissed; ++i) {
                        document.getElementById('emaillink').click();
                    }
                    document.getElementById('emaillink').click();
                    document.getElementById('emailconfirm-timezone-' + timezone).click();
                    assert.strictEqual(getCount(), 1);
                } finally {
                    restore();
                }
            }
        ));
    });
});
