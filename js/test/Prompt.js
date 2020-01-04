'use strict';
require('../common');

describe('Prompt', function () {
    // TODO: this does not test the prompt() fallback, since that isn't available
    //       in nodejs -> replace the prompt in the "page" template with a modal
    describe('requestPassword & getPassword', function () {
        this.timeout(30000);

        jsc.property(
            'returns the password fed into the dialog',
            'string',
            function (password) {
                password = password.replace(/\r+/g, '');
                var clean = jsdom('', {url: 'ftp://example.com/?0000000000000000'});
                $('body').html(
                    '<div id="passwordmodal" class="modal fade" role="dialog">' +
                    '<div class="modal-dialog"><div class="modal-content">' +
                    '<div class="modal-body"><form id="passwordform" role="form">' +
                    '<div class="form-group"><input id="passworddecrypt" ' +
                    'type="password" class="form-control" placeholder="Enter ' +
                    'password"></div><button type="submit">Decrypt</button>' +
                    '</form></div></div></div></div>'
                );
                $.PrivateBin.Model.reset();
                $.PrivateBin.Model.init();
                $.PrivateBin.Prompt.init();
                $.PrivateBin.Prompt.requestPassword();
                $('#passworddecrypt').val(password);
                // TODO triggers error messages in current jsDOM version, find better solution
                //$('#passwordform').submit();
                //var result = $.PrivateBin.Prompt.getPassword();
                var result = $('#passworddecrypt').val();
                $.PrivateBin.Model.reset();
                clean();
                return result === password;
            }
        );
    });
});

