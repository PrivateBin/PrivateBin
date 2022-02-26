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
                assert.ok(results.every(element => element));
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
                assert.ok(results.every(element => element));
            }
        );
    });

    describe('showNewPasteButton', function () {
        before(function () {
            cleanup();
        });

        it(
            'displays the button for creating a paste',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li><button id="newbutton" type=' +
                    '"button" class="hidden">New</button></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    $('#newbutton').hasClass('hidden')
                );
                $.PrivateBin.TopNav.showNewPasteButton();
                results.push(
                    !$('#newbutton').hasClass('hidden')
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );
    });

    describe('hideCloneButton', function () {
        before(function () {
            cleanup();
        });

        it(
            'hides the button for cloning a paste',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li><button id="clonebutton" ' +
                    'type="button" class="btn btn-warning navbar-btn">' +
                    '<span class="glyphicon glyphicon-duplicate" aria-hidden=' +
                    '"true"></span> Clone</button></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    !$('#clonebutton').hasClass('hidden')
                );
                $.PrivateBin.TopNav.hideCloneButton();
                results.push(
                    $('#clonebutton').hasClass('hidden')
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );
    });

    describe('hideRawButton', function () {
        before(function () {
            cleanup();
        });

        it(
            'hides the raw text button',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li><button ' +
                    'id="rawtextbutton" type="button" class="btn ' +
                    'btn-warning navbar-btn"><span class="glyphicon ' +
                    'glyphicon-text-background" aria-hidden="true"></span> ' +
                    'Raw text</button></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    !$('#rawtextbutton').hasClass('hidden')
                );
                $.PrivateBin.TopNav.hideRawButton();
                results.push(
                    $('#rawtextbutton').hasClass('hidden')
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );
    });

    describe('hideFileSelector', function () {
        before(function () {
            cleanup();
        });

        it(
            'hides the file attachment selection button',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li id="attach" class="hidden ' +
                    'dropdown"><a href="#" class="dropdown-toggle" data-' +
                    'toggle="dropdown" role="button" aria-haspopup="true" ' +
                    'aria-expanded="false">Attach a file <span class="caret">' +
                    '</span></a><ul class="dropdown-menu"><li id="filewrap">' +
                    '<div><input type="file" id="file" name="file" /></div>' +
                    '</li><li id="customattachment" class="hidden"></li><li>' +
                    '<a id="fileremovebutton"  href="#">Remove attachment</a>' +
                    '</li></ul></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    !$('#filewrap').hasClass('hidden')
                );
                $.PrivateBin.TopNav.hideFileSelector();
                results.push(
                    $('#filewrap').hasClass('hidden')
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );
    });

    describe('showCustomAttachment', function () {
        before(function () {
            cleanup();
        });

        it(
            'display the custom file attachment',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li id="attach" class="hidden ' +
                    'dropdown"><a href="#" class="dropdown-toggle" data-' +
                    'toggle="dropdown" role="button" aria-haspopup="true" ' +
                    'aria-expanded="false">Attach a file <span class="caret">' +
                    '</span></a><ul class="dropdown-menu"><li id="filewrap">' +
                    '<div><input type="file" id="file" name="file" /></div>' +
                    '</li><li id="customattachment" class="hidden"></li><li>' +
                    '<a id="fileremovebutton"  href="#">Remove attachment</a>' +
                    '</li></ul></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    $('#customattachment').hasClass('hidden')
                );
                $.PrivateBin.TopNav.showCustomAttachment();
                results.push(
                    !$('#customattachment').hasClass('hidden')
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );
    });

    describe('collapseBar', function () {
        before(function () {
            cleanup();
        });

        it(
            'collapses the navigation when displayed on a small screen',
            function () {
                var clean = jsdom(),
                    results = [];
                $('body').html(
                    '<nav><div class="navbar-header"><button type="button" ' +
                    'class="navbar-toggle collapsed" data-toggle="collapse" ' +
                    'data-target="#navbar" aria-expanded="false" aria-controls' +
                    '="navbar">Toggle navigation</button><a class="reloadlink ' +
                    'navbar-brand" href=""><img alt="PrivateBin" ' +
                    'src="img/icon.svg" width="38" /></a></div><div ' +
                    'id="navbar"><ul><li><button id="newbutton" type=' +
                    '"button" class="hidden">New</button></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    $('.navbar-toggle').hasClass('collapsed') &&
                    $('#navbar').attr('aria-expanded') != 'true'
                );
                $.PrivateBin.TopNav.collapseBar();
                results.push(
                    $('.navbar-toggle').hasClass('collapsed') &&
                    $('#navbar').attr('aria-expanded') != 'true'
                );
                /*
                with the upgrade for bootstrap-3.3.7.js to bootstrap-3.4.1.js
                the mobile interface detection changed to check if the
                ontouchstart event exists, which broke this section of the test
                $('.navbar-toggle').trigger('click');
                results.push(
                    !$('.navbar-toggle').hasClass('collapsed') &&
                    $('#navbar').attr('aria-expanded') == 'true'
                );
                $.PrivateBin.TopNav.collapseBar();
                results.push(
                    $('.navbar-toggle').hasClass('collapsed') &&
                    $('#navbar').attr('aria-expanded') == 'false'
                );
                */
                clean();
                assert.ok(results.every(element => element));
            }
        );
    });

    describe('resetInput', function () {
        before(function () {
            cleanup();
        });

        it(
            'reset inputs to defaults (options off)',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li id="burnafterreadingoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="burnafterreading" name="burnafterreading" /> ' +
                    'Burn after reading</label></li><li id="opendiscussionoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="opendiscussion" name="opendiscussion" /> ' +
                    'Open discussion</label></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    !$.PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !$.PrivateBin.TopNav.getOpenDiscussion()
                );
                $('#burnafterreading').attr('checked', 'checked');
                $('#opendiscussion').attr('checked', 'checked');
                results.push(
                    $.PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    $.PrivateBin.TopNav.getOpenDiscussion()
                );
                $.PrivateBin.TopNav.resetInput();
                results.push(
                    !$.PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !$.PrivateBin.TopNav.getOpenDiscussion()
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );

        it(
            'reset inputs to defaults (burnafterreading on)',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li id="burnafterreadingoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="burnafterreading" name="burnafterreading" checked="checked" /> ' +
                    'Burn after reading</label></li><li id="opendiscussionoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="opendiscussion" name="opendiscussion" checked="checked" /> ' +
                    'Open discussion</label></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    $.PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !$.PrivateBin.TopNav.getOpenDiscussion()
                );
                $('#burnafterreading').removeAttr('checked');
                results.push(
                    !$.PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !$.PrivateBin.TopNav.getOpenDiscussion()
                );
                $.PrivateBin.TopNav.resetInput();
                results.push(
                    $.PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !$.PrivateBin.TopNav.getOpenDiscussion()
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );

        it(
            'reset inputs to defaults (opendiscussion on)',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li id="burnafterreadingoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="burnafterreading" name="burnafterreading" /> ' +
                    'Burn after reading</label></li><li id="opendiscussionoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="opendiscussion" name="opendiscussion" checked="checked" /> ' +
                    'Open discussion</label></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    !$.PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    $.PrivateBin.TopNav.getOpenDiscussion()
                );
                $('#opendiscussion').removeAttr('checked');
                $('#burnafterreading').prop('checked', true);
                results.push(
                    $.PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    !$.PrivateBin.TopNav.getOpenDiscussion()
                );
                $.PrivateBin.TopNav.resetInput();
                results.push(
                    !$.PrivateBin.TopNav.getBurnAfterReading()
                );
                results.push(
                    $.PrivateBin.TopNav.getOpenDiscussion()
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );
    });

    describe('getExpiration', function () {
        before(function () {
            cleanup();
        });

        it(
            'returns the currently selected expiration date',
            function () {
                $.PrivateBin.TopNav.init();
                assert.ok($.PrivateBin.TopNav.getExpiration() === null);
            }
        );
    });

    describe('getFileList', function () {
        before(function () {
            cleanup();
        });

        var File = window.File,
            FileList = window.FileList,
            path = require('path'),
            mime = require('mime-types');

        // mocking file input as per https://github.com/jsdom/jsdom/issues/1272
        function createFile(file_path) {
            var file = fs.statSync(file_path),
                lastModified = file.mtimeMs,
                size = file.size;

            return new File(
                [new fs.readFileSync(file_path)],
                path.basename(file_path),
                {
                    lastModified,
                    type: mime.lookup(file_path) || '',
                }
            )
        }

        function addFileList(input, file_paths) {
            if (typeof file_paths === 'string')
                file_paths = [file_paths]
            else if (!Array.isArray(file_paths)) {
                throw new Error('file_paths needs to be a file path string or an Array of file path strings')
            }

            const file_list = file_paths.map(fp => createFile(fp))
            file_list.__proto__ = Object.create(FileList.prototype)

            Object.defineProperty(input, 'files', {
                value: file_list,
                writeable: false,
            })

            return input
        }

        it(
            'returns the selected files',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li id="attach" class="hidden ' +
                    'dropdown"><a href="#" class="dropdown-toggle" data-' +
                    'toggle="dropdown" role="button" aria-haspopup="true" ' +
                    'aria-expanded="false">Attach a file <span class="caret">' +
                    '</span></a><ul class="dropdown-menu"><li id="filewrap">' +
                    '<div><input type="file" id="file" name="file" /></div>' +
                    '</li><li id="customattachment" class="hidden"></li><li>' +
                    '<a id="fileremovebutton"  href="#">Remove attachment</a>' +
                    '</li></ul></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    $.PrivateBin.TopNav.getFileList() === null
                );
                addFileList($('#file')[0], [
                    '../img/logo.svg',
                    '../img/busy.gif'
                ]);
                var files = $.PrivateBin.TopNav.getFileList();
                results.push(
                    files[0].name === 'logo.svg' &&
                    files[1].name === 'busy.gif'
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );
    });

    describe('getBurnAfterReading', function () {
        before(function () {
            cleanup();
        });

        it(
            'returns if the burn-after-reading checkbox was ticked',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li id="burnafterreadingoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="burnafterreading" name="burnafterreading" /> ' +
                    'Burn after reading</label></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    !$.PrivateBin.TopNav.getBurnAfterReading()
                );
                $('#burnafterreading').attr('checked', 'checked');
                results.push(
                    $.PrivateBin.TopNav.getBurnAfterReading()
                );
                $('#burnafterreading').removeAttr('checked');
                results.push(
                    !$.PrivateBin.TopNav.getBurnAfterReading()
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );
    });

    describe('getOpenDiscussion', function () {
        before(function () {
            cleanup();
        });

        it(
            'returns if the open-discussion checkbox was ticked',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li id="opendiscussionoption" ' +
                    'class="hidden"><label><input type="checkbox" ' +
                    'id="opendiscussion" name="opendiscussion" /> ' +
                    'Open discussion</label></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    !$.PrivateBin.TopNav.getOpenDiscussion()
                );
                $('#opendiscussion').attr('checked', 'checked');
                results.push(
                    $.PrivateBin.TopNav.getOpenDiscussion()
                );
                $('#opendiscussion').removeAttr('checked');
                results.push(
                    !$.PrivateBin.TopNav.getOpenDiscussion()
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );
    });


    describe('getPassword', function () {
        before(function () {
            cleanup();
        });

        jsc.property(
            'returns the contents of the password input',
            'string',
            function (password) {
                password = password.replace(/\r+/g, '');
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li><div id="password" ' +
                    'class="navbar-form hidden"><input type="password" ' +
                    'id="passwordinput" placeholder="Password (recommended)" ' +
                    'class="form-control" size="23" /></div></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    $.PrivateBin.TopNav.getPassword() === ''
                );
                $('#passwordinput').val(password);
                results.push(
                    $.PrivateBin.TopNav.getPassword() === password
                );
                $('#passwordinput').val('');
                results.push(
                    $.PrivateBin.TopNav.getPassword() === ''
                );
                cleanup();
                return results.every(element => element);
            }
        );
    });

    describe('getCustomAttachment', function () {
        before(function () {
            cleanup();
        });

        it(
            'returns the custom attachment element',
            function () {
                var results = [];
                $('body').html(
                    '<nav><div id="navbar"><ul><li id="attach" class="hidden ' +
                    'dropdown"><a href="#" class="dropdown-toggle" data-' +
                    'toggle="dropdown" role="button" aria-haspopup="true" ' +
                    'aria-expanded="false">Attach a file <span class="caret">' +
                    '</span></a><ul class="dropdown-menu"><li id="filewrap">' +
                    '<div><input type="file" id="file" name="file" /></div>' +
                    '</li><li id="customattachment" class="hidden"></li><li>' +
                    '<a id="fileremovebutton"  href="#">Remove attachment</a>' +
                    '</li></ul></li></ul></div></nav>'
                );
                $.PrivateBin.TopNav.init();
                results.push(
                    !$.PrivateBin.TopNav.getCustomAttachment().hasClass('test')
                );
                $('#customattachment').addClass('test');
                results.push(
                    $.PrivateBin.TopNav.getCustomAttachment().hasClass('test')
                );
                cleanup();
                assert.ok(results.every(element => element));
            }
        );
    });
});
