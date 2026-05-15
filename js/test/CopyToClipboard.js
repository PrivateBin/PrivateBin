'use strict';
const common = require('../common');

describe('CopyToClipboard', function() {
    this.timeout(30000);

    describe ('Copy document to clipboard', function () {
        jsc.property('Copy with button click',
            common.jscFormats(),
            'nestring',
            async function (format, text) {
                var clean = jsdom();
                common.enableClipboard();

                $('body').html(
                    '<div id="placeholder"></div>' +
					'<div id="attachmentPreview" class="hidden"></div>' +
                    '<h5 id="copyShortcutHint" class="hidden">' +
						'<small id="copyShortcutHintText"></small>' +
						'<button type="button" id="copyShortcutHintBtn"></button>' +
					'</h5>' +
                    '<div id="prettymessage" class="hidden">' +
						'<pre id="prettyprint"></pre>' +
					'</div>'
                );

                $.PrivateBin.PasteViewer.init();
                $.PrivateBin.PasteViewer.setFormat(format);
                $.PrivateBin.PasteViewer.setText(text);
                $.PrivateBin.PasteViewer.run();

                $.PrivateBin.CopyToClipboard.init();

                $('#copyShortcutHintBtn').trigger('click');

                const savedToClipboardText = await navigator.clipboard.readText();

                clean();

                return text === savedToClipboardText;
            }
        );

        /**
         * Unfortunately in JSVerify impossible to check if copy with shortcut when user selected some text on the page
         * (the copy document to clipboard should not work in this case) due to lacking window.getSelection() in jsdom.
         */
        jsc.property('Copy with keyboard shortcut',
            common.jscFormats(),
            'nestring',
            async function (format, text) {
                var clean = jsdom();
                common.enableClipboard();

                $('body').html(
                    '<div id="placeholder"></div>' +
					'<div id="attachmentPreview" class="hidden"></div>' +
                    '<h5 id="copyShortcutHint" class="hidden">' +
						'<small id="copyShortcutHintText"></small>' +
						'<button type="button" id="copyShortcutHintBtn"></button>' +
					'</h5>' +
                    '<div id="prettymessage" class="hidden">' +
						'<pre id="prettyprint"></pre>' +
					'</div>'
                );

                $.PrivateBin.PasteViewer.init();
                $.PrivateBin.PasteViewer.setFormat(format);
                $.PrivateBin.PasteViewer.setText(text);
                $.PrivateBin.PasteViewer.run();

                $.PrivateBin.CopyToClipboard.init();

                $('body').trigger('copy');

                const copiedTextWithoutSelectedText = await navigator.clipboard.readText();

                clean();

                return copiedTextWithoutSelectedText === text;
            }
        );
    });


    jsc.property('Copy link to clipboard',
        'nestring',
        async function (text) {
            var clean = jsdom();
            common.enableClipboard();

            $('body').html('<button id="copyLink"></button>');

            $.PrivateBin.CopyToClipboard.init();
            $.PrivateBin.CopyToClipboard.setUrl(text);

            $('#copyLink').trigger('click');

            const copiedText = await navigator.clipboard.readText();

            clean();

            return text === copiedText;
        }
    );


    describe('Keyboard shortcut hint', function () {
        jsc.property('Show hint', function () {
                var clean = jsdom();

                $('body').html('<h5 id="copyShortcutHint" class="hidden"></h5>');

                $.PrivateBin.CopyToClipboard.init();
                $.PrivateBin.CopyToClipboard.showKeyboardShortcutHint();

                const hasHidden = $('#copyShortcutHint').hasClass('hidden');

                clean();

                return !hasHidden;
            }
        );

        jsc.property('Hide hint', function () {
                var clean = jsdom();

                $('body').html('<h5 id="copyShortcutHint"></h5>');

                $.PrivateBin.CopyToClipboard.init();
                $.PrivateBin.CopyToClipboard.hideKeyboardShortcutHint();

                const hasHidden = $('#copyShortcutHint').hasClass('hidden');

                clean();

                return hasHidden;
            }
        );
    });
});