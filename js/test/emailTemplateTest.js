'use strict';
var common = require('../common');
const assert = require('assert');

// DOM builder that mirrors bootstrap5.php navbar
function buildEmailDomNoShortUrl() {
    $('body').html(
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
        '<input id="burnafterreadingoption" type="checkbox">'
    );
}

// DOM builder that adds the shortener result block
function buildEmailDomWithShortUrl() {
    buildEmailDomNoShortUrl();
    $('body').html(
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
        '</div>'
    );
}


function stubWinOpen($element) {
    const win = $element[0].ownerDocument.defaultView;

    // Some helpers in privatebin.js expect a global document.
    global.document = win.document;

    let openedUrl = null;
    const origOpen = win.open;

    // Prefer simple assignment; if blocked, fall back to defineProperty.
    try {
        win.open = function (url) {
            openedUrl = url;
            return {};
        };
    } catch (e) {
        Object.defineProperty(win, 'open', {
            value: function (url) {
                openedUrl = url;
                return {};
            },
            configurable: true,
            writable: true
        });
    }

    return {
        getUrl: () => openedUrl,
        restore: () => { try { win.open = origOpen; } catch (e) { /* suppress exception in restore */ } },
        win
    };
}


// Extract and decode the body from a "mailto:?body=..." URL.
function extractMailtoBody(mailtoUrl) {
    assert.match(mailtoUrl, /^mailto:\?body=/, 'expected a mailto:?body= URL');
    return decodeURIComponent(mailtoUrl.replace(/^mailto:\?body=/, ''));
}

describe('Email - mail body content (short URL vs. fallback)', function () {
    before(function () {
        cleanup(); // provided by common
    });

    it('Uses the short URL when #pasteurl is present and never includes "undefined"', function () {
        buildEmailDomWithShortUrl();               // with #pastelink/#pasteurl
        $.PrivateBin.TopNav.init();
        $.PrivateBin.TopNav.showEmailButton(0);

        const $emailBtn = $('#emaillink');
        assert.ok(!$emailBtn.hasClass('hidden'), '#emaillink should be visible after showEmailButton');

        const { getUrl, restore } = stubWinOpen($emailBtn);
        try {
            $emailBtn.trigger('click');
            const openedUrl = getUrl();
            assert.ok(openedUrl, 'window.open should have been called');

            const body = extractMailtoBody(openedUrl);
            assert.match(body, /https:\/\/short\.example\/xYz/, 'email body should include the short URL');
            assert.doesNotMatch(body, /undefined/, 'email body must not contain "undefined"');
        } finally {
            restore();
            cleanup();
        }
    });

    it('Falls back to window.location.href when #pasteurl is absent and never includes "undefined"', function () {
        buildEmailDomNoShortUrl();      // No #pasteurl
        $.PrivateBin.TopNav.init();
        $.PrivateBin.TopNav.showEmailButton(0);

        const $emailBtn = $('#emaillink');
        assert.ok(!$emailBtn.hasClass('hidden'), '#emaillink should be visible after showEmailButton');

        const { getUrl, restore, win } = stubWinOpen($emailBtn);
        try {
            $emailBtn.trigger('click');
            const openedUrl = getUrl();
            assert.ok(openedUrl, 'window.open should have been called');

            const body = extractMailtoBody(openedUrl);
            assert.match(body, new RegExp(win.location.href), 'email body should include the fallback page URL');
            assert.doesNotMatch(body, /undefined/, 'email body must not contain "undefined"');
        } finally {
            restore();
            cleanup();
        }
    });
});
