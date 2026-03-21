'use strict';
require('../common');

describe('Editor', function () {
    describe('show, hide, getText, setText & isPreview', function () {
        this.timeout(30000);

        jsc.property(
            'returns text fed into the textarea, handles editor tabs',
            'string',
            function (text) {
                var clean = globalThis.cleanup(),
                    results = [];
                document.body.innerHTML = (
                    '<ul id="editorTabs" class="nav nav-tabs hidden"><li ' +
                    'role="presentation" class="active"><a id="messageedit" ' +
                    'href="#">Editor</a></li><li role="presentation"><a ' +
                    'id="messagepreview" href="#">Preview</a></li></ul><div ' +
                    'id="placeholder" class="hidden">+++ no document text +++</div>' +
                    '<div id="prettymessage" class="hidden"><pre id="prettyprint" ' +
                    'class="prettyprint linenums:1"></pre></div><div ' +
                    'id="plaintext" class="hidden"></div><p><textarea ' +
                    'id="message" name="message" cols="80" rows="25" ' +
                    'class="form-control hidden"></textarea></p>'
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
        );
    });
});
