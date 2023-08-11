'use strict';
require('../common');

describe('Editor', function () {
    describe('show, hide, getText, setText & isPreview', function () {
        this.timeout(30000);

        jsc.property(
            'returns text fed into the textarea, handles editor tabs',
            'string',
            function (text) {
                var clean = jsdom(),
                    results = [];
                $('body').html(
                    '<ul id="editorTabs" class="nav nav-tabs hidden"><li ' +
                    'role="presentation" class="active"><a id="messageedit" ' +
                    'href="#">Editor</a></li><li role="presentation"><a ' +
                    'id="messagepreview" href="#">Preview</a></li></ul><div ' +
                    'id="placeholder" class="hidden">+++ no paste text +++</div>' +
                    '<div id="prettymessage" class="hidden"><pre id="prettyprint" ' +
                    'class="prettyprint linenums:1"></pre></div><div ' +
                    'id="plaintext" class="hidden"></div><p><textarea ' +
                    'id="message" name="message" cols="80" rows="25" ' +
                    'class="form-control hidden"></textarea></p>'
                );
                $.PrivateBin.Editor.init();
                results.push(
                    $('#editorTabs').hasClass('hidden') &&
                    $('#message').hasClass('hidden')
                );
                $.PrivateBin.Editor.show();
                results.push(
                    !$('#editorTabs').hasClass('hidden') &&
                    !$('#message').hasClass('hidden')
                );
                $.PrivateBin.Editor.hide();
                results.push(
                    $('#editorTabs').hasClass('hidden') &&
                    $('#message').hasClass('hidden')
                );
                $.PrivateBin.Editor.show();
                $.PrivateBin.Editor.focusInput();
                results.push(
                    $.PrivateBin.Editor.getText().length === 0
                );
                $.PrivateBin.Editor.setText(text);
                results.push(
                    $.PrivateBin.Editor.getText() === $('#message').val()
                );
                $.PrivateBin.Editor.setText();
                results.push(
                    !$.PrivateBin.Editor.isPreview() &&
                    !$('#message').hasClass('hidden')
                );
                $('#messagepreview').trigger('click');
                results.push(
                    $.PrivateBin.Editor.isPreview() &&
                    $('#message').hasClass('hidden')
                );
                $('#messageedit').trigger('click');
                results.push(
                    !$.PrivateBin.Editor.isPreview() &&
                    !$('#message').hasClass('hidden')
                );
                clean();
                return results.every(element => element);
            }
        );
    });
});
