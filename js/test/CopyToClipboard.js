'use strict';
const common = require('../common');
const fc = require('fast-check');

describe('CopyToClipboard', function () {
    afterEach(() => {
        globalThis.cleanup();
    });

    describe('Copy document to clipboard', function () {
        it('Copy with button click', async function () {
            await fc.assert(fc.asyncProperty(
                common.fcFormats(),
                fc.string({minLength: 1}),
                async function (format, text) {
                    common.enableClipboard();

                    document.body.innerHTML = (
                        '<div id="status" class="hidden"></div>' +
                        '<div id="errormessage" class="hidden"></div>' +
                        '<div id="placeholder" class="hidden">+++ no document text ' +
                        '+++</div><div id="prettymessage" class="hidden">' +
                        '<button type="button" id="prettyMessageCopyBtn"><svg id="copyIcon"></svg>' +
                        '<svg id="copySuccessIcon"></svg></button><pre ' +
                        'id="prettyprint" class="prettyprint linenums:1"></pre>' +
                        '</div><div id="plaintext" class="hidden"></div>'
                    );

                    PrivateBin.Alert.init();
                    PrivateBin.PasteViewer.init();
                    PrivateBin.PasteViewer.setFormat(format);
                    PrivateBin.PasteViewer.setText(text);
                    PrivateBin.PasteViewer.run();

                    PrivateBin.CopyToClipboard.init();

                    document.getElementById('prettyMessageCopyBtn').click();

                    const savedToClipboardText = await navigator.clipboard.readText();

                    return text === savedToClipboardText;
                }
            ));
        });

        /**
         * Unfortunately, in JSVerify impossible to check if copy with shortcut when user selected some text on the page
         * (the copy document to clipboard should not work in this case) due to lacking window.getSelection() in jsdom.
         */
        it('Copy with keyboard shortcut', async function () {
            await fc.assert(fc.asyncProperty(
                common.fcFormats(),
                fc.string({minLength: 1}),
                async function (format, text) {
                    common.enableClipboard();

                    document.body.innerHTML = (
                        '<div id="status" class="hidden"></div>' +
                        '<div id="errormessage" class="hidden"></div>' +
                        '<div id="placeholder">+++ no document text ' +
                        '+++</div><div id="prettymessage" class="hidden">' +
                        '<button type="button" id="prettyMessageCopyBtn"><svg id="copyIcon"></svg>' +
                        '<svg id="copySuccessIcon"></svg></button><pre ' +
                        'id="prettyprint" class="prettyprint linenums:1"></pre>' +
                        '</div><div id="plaintext" class="hidden"></div>'
                    );

                    PrivateBin.Alert.init();
                    PrivateBin.PasteViewer.init();
                    PrivateBin.PasteViewer.setFormat(format);
                    PrivateBin.PasteViewer.setText(text);
                    PrivateBin.PasteViewer.run();

                    PrivateBin.CopyToClipboard.init();

                    document.body.dispatchEvent(getClipboardEvent());

                    const copiedTextWithoutSelectedText = await navigator.clipboard.readText();

                    return copiedTextWithoutSelectedText === text;
                }
            ));
        });

        /**
         * ClipboardEvent is not supported in jsdom yet, so this creates a mock event to trigger the copy event listener.
         *
         * See https://github.com/jsdom/jsdom/issues/1568
         *
         * @returns {ClipboardEvent}
         */
        function getClipboardEvent() {
            /** {@type ClipboardEvent} */
            const clipboardEvent = new Event('copy', {
                bubbles: true,
                cancelable: true,
                composed: true
            });
            clipboardEvent['clipboardData'] = {
                getData: function () {
                    return '';
                }
            }
            return clipboardEvent;
        }
    });


    it('Copy link to clipboard', async function () {
        await fc.assert(fc.asyncProperty(fc.string(),
            async function (text) {
                common.enableClipboard();

                document.body.innerHTML = (
                    '<div id="status" class="hidden"></div>' +
                    '<div id="errormessage" class="hidden"></div>' +
                    '<button id="copyLink"></button>'
                );

                PrivateBin.Alert.init();
                PrivateBin.CopyToClipboard.init();
                PrivateBin.CopyToClipboard.setUrl(text);

                document.getElementById('copyLink').click();

                const copiedText = await navigator.clipboard.readText();

                return text === copiedText;
            })
        );
    });

    describe('Keyboard shortcut hint', function () {
        it('shows hint', () => {
            fc.assert(fc.property(fc.string(),
                function (text) {
                    document.body.innerHTML = '<small id="copyShortcutHintText"></small>';

                    PrivateBin.CopyToClipboard.init();
                    PrivateBin.CopyToClipboard.showKeyboardShortcutHint();

                    const keyboardShortcutHint = document.getElementById('copyShortcutHintText').textContent;

                    return keyboardShortcutHint.length > 0;
                }
            ));
        });
    });

    it('Hide hint', () => {
        fc.assert(fc.property(
            fc.string({minLength: 1}),
            function (text) {
                document.body.innerHTML = '<small id="copyShortcutHintText">' + text + '</small>';

                PrivateBin.CopyToClipboard.init();
                PrivateBin.CopyToClipboard.hideKeyboardShortcutHint();

                const keyboardShortcutHint = document.getElementById('copyShortcutHintText').textContent;

                return keyboardShortcutHint.length === 0;
            }
        ));
    });
});
