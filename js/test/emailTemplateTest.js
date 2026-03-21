'use strict';
require('../common');

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
    let mockRestoreFn = null;

    if (typeof jest !== 'undefined' && typeof jest.spyOn === 'function') {
        const spy = jest.spyOn(window, 'open').mockImplementation((url) => {
            openedUrl = url;
            return {};
        });
        mockRestoreFn = () => spy.mockRestore();
    } else {
        window.open = function (url) {
            openedUrl = url;
            return {};
        };
        mockRestoreFn = () => { window.open = originalOpen; };
    }

    return {
        getUrl: () => openedUrl,
        restore: () => {
            if (mockRestoreFn) {
                mockRestoreFn();
            }
        }
    };
}


// Extract and decode the body from a "mailto:?body=..." URL.
function extractMailtoBody(mailtoUrl) {
    assert.match(mailtoUrl, /^mailto:\?body=/, 'expected a mailto:?body= URL');
    return decodeURIComponent(mailtoUrl.replace(/^mailto:\?body=/, ''));
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
});
