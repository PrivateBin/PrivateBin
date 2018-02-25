'use strict';
var common = require('../common');

describe('TopNav', function () {
    describe('showViewButtons & hideViewButtons', function () {
        before(function () {
            cleanup();
        });

        it(
            'displays & hides navigation elements for viewing an existing paste',
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
                $.PrivateBin.TopNav.showViewButtons();
                results.push(
                    !$('#newbutton').hasClass('hidden') &&
                    !$('#clonebutton').hasClass('hidden') &&
                    !$('#rawtextbutton').hasClass('hidden') &&
                    !$('#qrcodelink').hasClass('hidden')
                );
                $.PrivateBin.TopNav.hideViewButtons();
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

    describe('showCreateButtons & hideCreateButtons', function () {
        before(function () {
            cleanup();
        });

        it(
            'displays & hides navigation elements for creating a paste',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li><button id="newbutton" ' +
                    'type="button" class="hidden">New</button></li><li><a ' +
                    'id="expiration" href="#" class="hidden">Expiration</a>' +
                    '</li><li><div id="burnafterreadingoption" class="hidden">' +
                    'Burn after reading</div></li><li><div id="opendiscussion' +
                    'option" class="hidden">Open discussion</div></li><li>' +
                    '<div id="password" class="hidden">Password</div></li>' +
                    '<li id="attach" class="hidden">Attach a file</li><li>' +
                    '<a id="formatter" href="#" class="hidden">Format</a>' +
                    '</li><li><button id="sendbutton" type="button" ' +
                    'class="hidden">Send</button></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    $('#sendbutton').hasClass('hidden') &&
                    $('#expiration').hasClass('hidden') &&
                    $('#formatter').hasClass('hidden') &&
                    $('#burnafterreadingoption').hasClass('hidden') &&
                    $('#opendiscussionoption').hasClass('hidden') &&
                    $('#newbutton').hasClass('hidden') &&
                    $('#password').hasClass('hidden') &&
                    $('#attach').hasClass('hidden')
                );
                $.PrivateBin.TopNav.showCreateButtons();
                results.push(
                    !$('#sendbutton').hasClass('hidden') &&
                    !$('#expiration').hasClass('hidden') &&
                    !$('#formatter').hasClass('hidden') &&
                    !$('#burnafterreadingoption').hasClass('hidden') &&
                    !$('#opendiscussionoption').hasClass('hidden') &&
                    !$('#newbutton').hasClass('hidden') &&
                    !$('#password').hasClass('hidden') &&
                    !$('#attach').hasClass('hidden')
                );
                $.PrivateBin.TopNav.hideCreateButtons();
                results.push(
                    $('#sendbutton').hasClass('hidden') &&
                    $('#expiration').hasClass('hidden') &&
                    $('#formatter').hasClass('hidden') &&
                    $('#burnafterreadingoption').hasClass('hidden') &&
                    $('#opendiscussionoption').hasClass('hidden') &&
                    $('#newbutton').hasClass('hidden') &&
                    $('#password').hasClass('hidden') &&
                    $('#attach').hasClass('hidden')
                );
                cleanup();
                return results.every(element => element);
            }
        );
    });
});

