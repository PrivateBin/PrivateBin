'use strict';
var common = require('../common');

describe('TopNav', function () {
    describe('showViewButtons & hideViewButtons', function () {
        before(function () {
            cleanup();
        });

        it(
            'display & hide navigation elements for viewing an existing paste',
            function () {
                var results = [];
                $('body').html(
                    '<nav class="navbar navbar-inverse navbar-static-top">' +
                    '<div id="navbar" class="navbar-collapse collapse"><ul ' +
                    'class="nav navbar-nav"><li><button id="newbutton" ' +
                    'type="button" class="hidden btn btn-warning navbar-btn">' +
                    '<span class="glyphicon glyphicon-file" aria-hidden="true">' +
                    '</span> New</button><button id="clonebutton" type="button"' +
                    ' class="hidden btn btn-warning navbar-btn">' +
                    '<span class="glyphicon glyphicon-duplicate" ' +
                    'aria-hidden="true"></span> Clone</button><button ' +
                    'id="rawtextbutton" type="button" class="hidden btn ' +
                    'btn-warning navbar-btn"><span class="glyphicon ' +
                    'glyphicon-text-background" aria-hidden="true"></span> ' +
                    'Raw text</button><button id="qrcodelink" type="button" ' +
                    'data-toggle="modal" data-target="#qrcodemodal" ' +
                    'class="hidden btn btn-warning navbar-btn"><span ' +
                    'class="glyphicon glyphicon-qrcode" aria-hidden="true">' +
                    '</span> QR code</button></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    $('#newbutton').hasClass('hidden') &&
                    $('#clonebutton').hasClass('hidden') &&
                    $('#rawtextbutton').hasClass('hidden') &&
                    $('#qrcodelink').hasClass('hidden')
                );
                cleanup();
                return results.every(element => element);
            }
        );
    });
});

