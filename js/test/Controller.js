'use strict';
require('../common');

describe('Controller', function () {
    describe('newPaste', function () {
        before(function () {
            cleanup();
        });

        it(
            'resets the formatter to the configured default',
            function () {
                const clean = jsdom('', {url: 'https://example.com/'});
                $('body').html(
                    '<div id="errormessage" class="hidden"><span></span><div></div></div>' +
                    '<div id="loadingindicator" class="hidden"><span></span><div></div></div>' +
                    '<div id="status" class="hidden"><span></span><div></div></div>' +
                    '<div id="remainingtime" class="hidden"><span></span><div></div></div>' +
                    '<div id="pastesuccess" class="hidden"></div><button id="shortenbutton"></button>' +
                    '<ul id="editorTabs"><li id="messagetab"><a></a></li><li id="previewtab"><a></a></li></ul>' +
                    '<textarea id="message"></textarea><div id="preview" class="hidden"></div>' +
                    '<div id="placeholder" class="hidden"></div><div id="prettymessage" class="hidden">' +
                    '<pre id="prettyprint"></pre></div><div id="plaintext" class="hidden"></div>' +
                    '<div id="attachment" class="hidden"></div><div id="attachmentPreview" class="hidden"></div>' +
                    '<div id="dragAndDropFileNames"></div><input id="file" type="file">' +
                    '<div id="discussion" class="hidden"><div id="commentcontainer"></div></div>' +
                    '<div id="commenttemplate"><div></div></div><div id="replytemplate"><div></div></div>' +
                    '<div id="attach" class="hidden"></div><input id="burnafterreading" type="checkbox">' +
                    '<div id="burnafterreadingoption" class="hidden"></div><button id="clonebutton"></button>' +
                    '<div id="customattachment" class="hidden"></div><div id="expiration" class="hidden"></div>' +
                    '<button id="fileremovebutton"></button><div id="filewrap"></div>' +
                    '<div id="formatter" class="hidden"><select id="pasteFormatter">' +
                    '<option value="plaintext">Plain Text</option>' +
                    '<option value="markdown" selected="selected">Markdown</option>' +
                    '</select></div>' +
                    '<button id="newbutton" class="hidden"></button><input id="opendiscussion" type="checkbox">' +
                    '<div id="opendiscussionoption" class="hidden"></div><div id="password" class="hidden"></div>' +
                    '<input id="passwordinput"><button id="rawtextbutton"></button><button id="downloadtextbutton"></button>' +
                    '<button id="retrybutton"></button><button id="sendbutton" class="hidden"></button>' +
                    '<button id="qrcodelink"></button><a id="emaillink"></a>' +
                    '<select id="pasteExpiration"><option value="1week" selected="selected">1 week</option></select>' +
                    '<span id="pasteExpirationDisplay"></span>'
                );

                $.PrivateBin.Alert.init();
                $.PrivateBin.Model.init();
                $.PrivateBin.AttachmentViewer.init();
                $.PrivateBin.DiscussionViewer.init();
                $.PrivateBin.Editor.init();
                $.PrivateBin.PasteStatus.init();
                $.PrivateBin.PasteViewer.init();
                $.PrivateBin.TopNav.init();

                $.PrivateBin.PasteViewer.setFormat('syntaxhighlighting');
                $.PrivateBin.Controller.newPaste();

                assert.strictEqual($.PrivateBin.PasteViewer.getFormat(), 'markdown');
                assert.strictEqual($('#pasteFormatter').val(), 'markdown');

                clean();
            }
        );
    });
});
