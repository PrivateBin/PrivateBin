'use strict';
var common = require('../common');

describe('Alert', function () {
    describe('showStatus', function () {
        before(function () {
            cleanup();
        });

        jsc.property(
            'shows a status message',
            jsc.array(common.jscAlnumString()),
            jsc.array(common.jscAlnumString()),
            function (icon, message) {
                icon = icon.join('');
                message = message.join('');
                var expected = '<div id="status" role="alert" ' +
                    'class="statusmessage alert alert-info"><span ' +
                    'class="glyphicon glyphicon-' + icon +
                    '" aria-hidden="true"></span> ' + message + '</div>';
                $('body').html(
                    '<div id="status" role="alert" class="statusmessage ' +
                    'alert alert-info hidden"><span class="glyphicon ' +
                    'glyphicon-info-sign" aria-hidden="true"></span> </div>'
                );
                $.PrivateBin.Alert.init();
                $.PrivateBin.Alert.showStatus(message, icon);
                var result = $('body').html();
                return expected === result;
            }
        );
    });

    describe('showError', function () {
        before(function () {
            cleanup();
        });

        jsc.property(
            'shows an error message',
            jsc.array(common.jscAlnumString()),
            jsc.array(common.jscAlnumString()),
            function (icon, message) {
                icon = icon.join('');
                message = message.join('');
                var expected = '<div id="errormessage" role="alert" ' +
                    'class="statusmessage alert alert-danger"><span ' +
                    'class="glyphicon glyphicon-' + icon +
                    '" aria-hidden="true"></span> ' + message + '</div>';
                $('body').html(
                    '<div id="errormessage" role="alert" class="statusmessage ' +
                    'alert alert-danger hidden"><span class="glyphicon ' +
                    'glyphicon-alert" aria-hidden="true"></span> </div>'
                );
                $.PrivateBin.Alert.init();
                $.PrivateBin.Alert.showError(message, icon);
                var result = $('body').html();
                return expected === result;
            }
        );
    });

    describe('showRemaining', function () {
        before(function () {
            cleanup();
        });

        jsc.property(
            'shows remaining time',
            jsc.array(common.jscAlnumString()),
            jsc.array(common.jscAlnumString()),
            'integer',
            function (message, string, number) {
                message = message.join('');
                string = string.join('');
                var expected = '<div id="remainingtime" role="alert" ' +
                    'class="alert alert-info"><span ' +
                    'class="glyphicon glyphicon-fire" aria-hidden="true">' +
                    '</span> ' + string + message + number + '</div>';
                $('body').html(
                    '<div id="remainingtime" role="alert" class="hidden ' +
                    'alert alert-info"><span class="glyphicon ' +
                    'glyphicon-fire" aria-hidden="true"></span> </div>'
                );
                $.PrivateBin.Alert.init();
                $.PrivateBin.Alert.showRemaining(['%s' + message + '%d', string, number]);
                var result = $('body').html();
                return expected === result;
            }
        );
    });

    describe('showLoading', function () {
        before(function () {
            cleanup();
        });

        jsc.property(
            'shows a loading message',
            jsc.array(common.jscAlnumString()),
            jsc.array(common.jscAlnumString()),
            function (message, icon) {
                message = message.join('');
                icon = icon.join('');
                var defaultMessage = 'Loading…';
                if (message.length === 0) {
                    message = defaultMessage;
                }
                var expected = '<ul class="nav navbar-nav"><li ' +
                    'id="loadingindicator" class="navbar-text"><span ' +
                    'class="glyphicon glyphicon-' + icon +
                    '" aria-hidden="true"></span> ' + message + '</li></ul>';
                $('body').html(
                    '<ul class="nav navbar-nav"><li id="loadingindicator" ' +
                    'class="navbar-text hidden"><span class="glyphicon ' +
                    'glyphicon-time" aria-hidden="true"></span> ' +
                    defaultMessage + '</li></ul>'
                );
                $.PrivateBin.Alert.init();
                $.PrivateBin.Alert.showLoading(message, icon);
                var result = $('body').html();
                return expected === result;
            }
        );
    });

    describe('hideLoading', function () {
        before(function () {
            cleanup();
        });

        it(
            'hides the loading message',
            function() {
                $('body').html(
                    '<ul class="nav navbar-nav"><li id="loadingindicator" ' +
                    'class="navbar-text"><span class="glyphicon ' +
                    'glyphicon-time" aria-hidden="true"></span> ' +
                    'Loading…</li></ul>'
                );
                $('body').addClass('loading');
                $.PrivateBin.Alert.init();
                $.PrivateBin.Alert.hideLoading();
                assert.ok(
                    !$('body').hasClass('loading') &&
                    $('#loadingindicator').hasClass('hidden')
                );
            }
        );
    });

    describe('hideMessages', function () {
        before(function () {
            cleanup();
        });

        it(
            'hides all messages',
            function() {
                $('body').html(
                    '<div id="status" role="alert" class="statusmessage ' +
                    'alert alert-info"><span class="glyphicon ' +
                    'glyphicon-info-sign" aria-hidden="true"></span> </div>' +
                    '<div id="errormessage" role="alert" class="statusmessage ' +
                    'alert alert-danger"><span class="glyphicon ' +
                    'glyphicon-alert" aria-hidden="true"></span> </div>'
                );
                $.PrivateBin.Alert.init();
                $.PrivateBin.Alert.hideMessages();
                assert.ok(
                    $('#status').hasClass('hidden') &&
                    $('#errormessage').hasClass('hidden')
                );
            }
        );
    });

    describe('setCustomHandler', function () {
        before(function () {
            cleanup();
        });

        jsc.property(
            'calls a given handler function',
            'nat 3',
            jsc.array(common.jscAlnumString()),
            function (trigger, message) {
                message = message.join('');
                var handlerCalled = false,
                    defaultMessage = 'Loading…',
                    functions = [
                        $.PrivateBin.Alert.showStatus,
                        $.PrivateBin.Alert.showError,
                        $.PrivateBin.Alert.showRemaining,
                        $.PrivateBin.Alert.showLoading
                    ];
                if (message.length === 0) {
                    message = defaultMessage;
                }
                $('body').html(
                    '<ul class="nav navbar-nav"><li id="loadingindicator" ' +
                    'class="navbar-text hidden"><span class="glyphicon ' +
                    'glyphicon-time" aria-hidden="true"></span> ' +
                    defaultMessage + '</li></ul>' +
                    '<div id="remainingtime" role="alert" class="hidden ' +
                    'alert alert-info"><span class="glyphicon ' +
                    'glyphicon-fire" aria-hidden="true"></span> </div>' +
                    '<div id="status" role="alert" class="statusmessage ' +
                    'alert alert-info"><span class="glyphicon ' +
                    'glyphicon-info-sign" aria-hidden="true"></span> </div>' +
                    '<div id="errormessage" role="alert" class="statusmessage ' +
                    'alert alert-danger"><span class="glyphicon ' +
                    'glyphicon-alert" aria-hidden="true"></span> </div>'
                );
                $.PrivateBin.Alert.init();
                $.PrivateBin.Alert.setCustomHandler(function(id, $element) {
                    handlerCalled = true;
                    return jsc.random(0, 1) ? true : $element;
                });
                functions[trigger](message);
                return handlerCalled;
            }
        );
    });
});

