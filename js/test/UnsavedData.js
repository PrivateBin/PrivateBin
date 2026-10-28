'use strict';
require('../common');

describe('TopNav unsaved data warning', function () {
    let promptCount, newPasteCount;

    // builds a navbar with the New button and the language menu, plus the editor
    function setUp(text) {
        cleanup();
        promptCount = 0;
        newPasteCount = 0;
        document.documentElement.innerHTML =
            '<nav><div id="navbar"><ul>' +
            '<li><button id="newbutton" type="button" class="hidden">New</button></li>' +
            '<li><button id="clonebutton" type="button" class="hidden">Clone</button></li>' +
            '<li><button id="rawtextbutton" type="button" class="hidden">Raw text</button></li>' +
            '<li><button id="downloadtextbutton" type="button" class="hidden">Save document</button></li>' +
            '<li><a id="expiration" href="#" class="hidden">Expiration</a></li>' +
            '<li><div id="burnafterreadingoption" class="hidden">Burn after reading</div></li>' +
            '<li><a id="formatter" href="#" class="hidden">Format</a></li>' +
            '<li><button id="sendbutton" type="button" class="hidden">Create</button></li>' +
            '<li id="language"><ul class="dropdown-menu"><li>' +
            '<a href="#" class="dropdown-item" data-lang="fr">Français</a>' +
            '</li></ul></li>' +
            '</ul></div></nav>' +
            '<textarea id="message"></textarea>';
        PrivateBin.Editor.init();
        PrivateBin.Editor.setText(text);
        PrivateBin.Controller.hideStatusMessages = function () {};
        PrivateBin.Controller.newPaste = function () {
            newPasteCount++;
        };
        PrivateBin.TopNav.init();
        PrivateBin.TopNav.showCreateButtons();
    }

    // stubs the modal: records that it was shown, and optionally "clicks" Continue
    function stubPrompt(clickContinue) {
        PrivateBin.Prompt.requestUnsavedDataConfirmation = function (onConfirm) {
            promptCount++;
            if (clickContinue) {
                onConfirm();
            }
        };
    }

    function click(selector) {
        document.querySelector(selector).dispatchEvent(
            new window.MouseEvent('click', {bubbles: true, cancelable: true})
        );
    }

    // dispatches beforeunload, returns whether leaving was blocked
    function leavePage() {
        const event = new window.Event('beforeunload', {cancelable: true});
        window.dispatchEvent(event);
        return event.defaultPrevented;
    }

    afterEach(function () {
        cleanup();
    });

    it('creates a new document right away, when the editor is empty', function () {
        setUp('');
        stubPrompt(false);
        click('#newbutton');
        assert.strictEqual(promptCount, 0);
        assert.strictEqual(newPasteCount, 1);
    });

    it('ignores whitespace-only text', function () {
        setUp('  \n\t ');
        assert.strictEqual(PrivateBin.TopNav.hasUnsavedData(), false);
    });

    it('asks before creating a new document, when there is unsent text', function () {
        setUp('my secret notes');
        stubPrompt(false);
        click('#newbutton');
        assert.strictEqual(promptCount, 1);
        assert.strictEqual(newPasteCount, 0, 'the text must not be discarded on Cancel');
    });

    it('creates a new document after the user confirmed', function () {
        setUp('my secret notes');
        stubPrompt(true);
        click('#newbutton');
        assert.strictEqual(promptCount, 1);
        assert.strictEqual(newPasteCount, 1);
    });

    it('asks before changing the language, when there is unsent text', function () {
        setUp('my secret notes');
        document.cookie = 'lang=en';
        stubPrompt(false);
        click('#language a');
        assert.strictEqual(promptCount, 1);
        assert.ok(document.cookie.indexOf('lang=fr') === -1, 'the language must not change on Cancel');
    });

    it('warns before an opened burn after reading document gets lost', function () {
        setUp('');
        PrivateBin.TopNav.hideCreateButtons();
        PrivateBin.TopNav.showViewButtons();
        PrivateBin.TopNav.setBurnAfterReadingDisplayed(true);
        assert.strictEqual(PrivateBin.TopNav.hasUnsavedData(), true);
        PrivateBin.TopNav.hideViewButtons();
        assert.strictEqual(PrivateBin.TopNav.hasUnsavedData(), false, 'the warning ends with the document view');
    });

    it('does not warn on the confirmation page of a newly created burn after reading document', function () {
        setUp('');
        PrivateBin.TopNav.hideCreateButtons();
        // after creating it, the creator sees the link, but the document was not read yet
        window.location.hash = '#-5QhBzTzMWVEkWtk4wcPh2YKaAGqaiw2zv3nvvB9P6KB';
        PrivateBin.TopNav.showViewButtons();
        assert.strictEqual(PrivateBin.TopNav.hasUnsavedData(), false);
        assert.ok(!leavePage());
    });

    it('blocks leaving the page only when there is unsaved data', function () {
        setUp('my secret notes');
        assert.ok(leavePage());
        PrivateBin.Editor.setText('');
        assert.ok(!leavePage());
    });
});

describe('Prompt.requestUnsavedDataConfirmation', function () {
    afterEach(function () {
        cleanup();
    });

    it('falls back to the browser dialog without Bootstrap, and only continues if confirmed', function () {
        cleanup();
        let continued = 0;
        window.confirm = function () {
            return false;
        };
        PrivateBin.Prompt.requestUnsavedDataConfirmation(function () {
            continued++;
        });
        assert.strictEqual(continued, 0, 'Cancel must not continue');

        window.confirm = function () {
            return true;
        };
        PrivateBin.Prompt.requestUnsavedDataConfirmation(function () {
            continued++;
        });
        assert.strictEqual(continued, 1, 'OK must continue');
    });
});
