'use strict';
require('../common');
const fc = require('fast-check');

describe('Editor', function () {
    describe('show, hide, getText, setText & isPreview', function () {
        this.timeout(30000);

        it('returns text fed into the textarea, handles editor tabs', () => {
            fc.assert(fc.property(
                fc.string(),
                function (text) {
                    var clean = globalThis.cleanup(),
                        results = [];
                    document.body.innerHTML = (
                        '<ul id="editorTabs" class="nav nav-tabs hidden">' +
                            '<li role="presentation" class="active">' +
                                '<a id="messageedit" href="#">Editor</a>' +
                            '</li>' +
                            '<li role="presentation">' +
                                '<a id="messagepreview" href="#">Preview</a>' +
                            '</li>' +
                        '</ul>' +
                        '<div id="placeholder" class="hidden">+++ no document text +++</div>' +
                        '<div id="prettymessage" class="hidden">' +
                            '<pre id="prettyprint" class="prettyprint linenums:1"></pre>' +
                        '</div>' +
                        '<div id="plaintext" class="hidden"></div>' +
                        '<p>' +
                            '<textarea id="message" name="message" cols="80" rows="25" class="form-control hidden"></textarea>' +
                        '</p>' +
                        '<input id="messagetab" type="checkbox" checked="checked" />'
                    );
                    PrivateBin.Editor.init();
                    results.push(
                        document.getElementById('editorTabs').classList.contains('hidden') &&
                        document.getElementById('message').classList.contains('hidden')
                    );
                    PrivateBin.Editor.show();
                    results.push(
                        !document.getElementById('editorTabs').classList.contains('hidden') &&
                        !document.getElementById('message').classList.contains('hidden')
                    );
                    PrivateBin.Editor.hide();
                    results.push(
                        document.getElementById('editorTabs').classList.contains('hidden') &&
                        document.getElementById('message').classList.contains('hidden')
                    );
                    PrivateBin.Editor.show();
                    PrivateBin.Editor.focusInput();
                    results.push(
                        PrivateBin.Editor.getText().length === 0
                    );
                    PrivateBin.Editor.setText(text);
                    results.push(
                        PrivateBin.Editor.getText() === document.getElementById('message').value
                    );
                    PrivateBin.Editor.setText();
                    results.push(
                        !PrivateBin.Editor.isPreview() &&
                        !document.getElementById('message').classList.contains('hidden')
                    );
                    document.getElementById('messagepreview').click();
                    results.push(
                        PrivateBin.Editor.isPreview() &&
                        document.getElementById('message').classList.contains('hidden')
                    );
                    document.getElementById('messageedit').click();
                    results.push(
                        !PrivateBin.Editor.isPreview() &&
                        !document.getElementById('message').classList.contains('hidden')
                    );
                    clean();
                    return results.every(element => element);
                }
            ));
        });
    });

    describe('updatePreviewTabVisibility', function () {
        beforeEach(function () {
            cleanup();
            document.body.innerHTML = (
                '<select id="pasteFormatter" name="pasteFormatter">' +
                    '<option value="plaintext">Plain Text</option>' +
                    '<option value="markdown">Markdown</option>' +
                '</select>' +
                '<ul id="editorTabs">' +
                    '<li role="presentation" class="active">' +
                        '<a id="messageedit" href="#">Editor</a>' +
                    '</li>' +
                    '<li role="presentation">' +
                        '<a id="messagepreview" href="#">Preview</a>' +
                    '</li>' +
                '</ul>' +
                '<div id="placeholder" class="hidden"></div>' +
                '<div id="prettymessage" class="hidden"><pre id="prettyprint"></pre></div>' +
                '<div id="plaintext" class="hidden"></div>' +
                '<textarea id="message" class="hidden"></textarea>' +
                '<input id="messagetab" type="checkbox" checked="checked" />'
            );
            PrivateBin.PasteViewer.init();
            PrivateBin.Editor.init();
        });

        afterEach(function () {
            cleanup();
        });

        it('hides preview tab for plaintext and shows it for other formats', function () {
            const previewParent = document.getElementById('messagepreview').parentElement;
            PrivateBin.PasteViewer.setFormat('plaintext');
            assert.ok(previewParent.classList.contains('hidden'));
            PrivateBin.PasteViewer.setFormat('markdown');
            assert.ok(!previewParent.classList.contains('hidden'));
        });

        it('leaves preview when switching to plaintext while previewing', function () {
            PrivateBin.PasteViewer.setFormat('markdown');
            PrivateBin.Editor.show();
            document.getElementById('messagepreview').click();
            assert.ok(PrivateBin.Editor.isPreview());
            PrivateBin.PasteViewer.setFormat('plaintext');
            assert.ok(!PrivateBin.Editor.isPreview());
            assert.ok(
                document.getElementById('messagepreview').parentElement.classList.contains('hidden')
            );
        });
    });
});
