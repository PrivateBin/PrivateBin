'use strict';
require('../common');

describe('Prompt', function () {
    describe('requestPassword & getPassword', function () {
        this.timeout(30000);

        jsc.property(
            'returns the password fed into the dialog',
            'string',
            function (password) {
                password = password.replace(/\r+|\n+/g, '');
                const clean = jsdom('', {url: 'ftp://example.com/?0000000000000000'});
                $('body').html(
                    '<div id="passwordmodal" class="modal fade" role="dialog">' +
                    '<div class="modal-dialog"><div class="modal-content">' +
                    '<div class="modal-body"><form id="passwordform" role="form">' +
                    '<div class="form-group"><input id="passworddecrypt" ' +
                    'type="password" class="form-control" placeholder="Enter ' +
                    'password"></div><button type="submit">Decrypt</button>' +
                    '</form></div></div></div></div>'
                );
                $('#passworddecrypt').val(password);
                const result = $('#passworddecrypt').val();
                clean();
                return result === password;
            }
        );
    });
});
