'use strict';
const common = require('../common');

describe('CopyToClipboard', function() {
    this.timeout(30000);

    describe ('Copy paste co clipboard', function () {
        jsc.property('Copy with button click', common.jscFormats(), 'nestring', async function (format, text) {
            var clean = jsdom();
            common.enableClipboard();
    
            $('body').html(
                '<div id="placeholder" class="hidden">+++ no paste text ' +
                '+++</div><div id="prettymessage" class="hidden">' +
                '<button type="button" id="prettyMessageCopyBtn"><svg id="copyIcon"></svg>' +
                '<svg id="copySuccessIcon"></svg></button><pre ' +
                'id="prettyprint" class="prettyprint linenums:1"></pre>' +
                '</div><div id="plaintext" class="hidden"></div>'
            );
    
            $.PrivateBin.PasteViewer.init();
            $.PrivateBin.PasteViewer.setFormat(format);
            $.PrivateBin.PasteViewer.setText(text);
            $.PrivateBin.PasteViewer.run();
    
            $.PrivateBin.CopyToClipboard.init();
            
            $('#prettyMessageCopyBtn').trigger('click');

            const savedToClipboardText = await navigator.clipboard.readText();
    
            clean();
    
            return text === savedToClipboardText;
        });

        /**
         * Unfortunately in JSVerify impossible to check if copy with shortcut when user selected some text on the page
         * (the copy paste to clipboard should not work in this case) due to lucking window.getSelection() in jsdom.
         */
        jsc.property('Copy with keyboard shortcut',  common.jscFormats(), 'nestring', async function (format, text) {
            var clean = jsdom();
            common.enableClipboard();
    
            $('body').html(
                '<div id="placeholder">+++ no paste text ' +
                '+++</div><div id="prettymessage" class="hidden">' +
                '<button type="button" id="prettyMessageCopyBtn"><svg id="copyIcon"></svg>' +
                '<svg id="copySuccessIcon"></svg></button><pre ' +
                'id="prettyprint" class="prettyprint linenums:1"></pre>' +
                '</div><div id="plaintext" class="hidden"></div>'
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
        });
    });


    jsc.property('Copy link to clipboard', 'nestring', async function (text) {
        var clean = jsdom();
        common.enableClipboard();

        $('body').html('<button id="copyLink"></button>');

        $.PrivateBin.CopyToClipboard.init();
        $.PrivateBin.CopyToClipboard.setUrl(text);

        $('#copyLink').trigger('click');

        const copiedText = await navigator.clipboard.readText();

        clean();

        return text === copiedText;
    });


    describe('Keyboard shortcut hint', function () {
        jsc.property('Show hint', 'nestring', function (text) {
            var clean = jsdom();
    
            $('body').html('<small id="copyShortcutHintText"></small>');
    
            $.PrivateBin.CopyToClipboard.init();
            $.PrivateBin.CopyToClipboard.showKeyboardShortcutHint();
    
            const keyboardShortcutHint = $('#copyShortcutHintText').text();
    
            clean();
    
            return keyboardShortcutHint.length > 0;
        });

        jsc.property('Hide hint', 'nestring', function (text) {
            var clean = jsdom();
    
            $('body').html('<small id="copyShortcutHintText">' + text + '</small>');
    
            $.PrivateBin.CopyToClipboard.init();
            $.PrivateBin.CopyToClipboard.hideKeyboardShortcutHint();
    
            const keyboardShortcutHint = $('#copyShortcutHintText').text();
    
            clean();
    
            return keyboardShortcutHint.length === 0;
        });
    });

});