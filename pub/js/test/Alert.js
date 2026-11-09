'use strict';
const common = require('../common');
const fc = require('fast-check');

describe('Alert', function () {
    describe('showStatus', function () {
        it('shows a status message (basic)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                function (icon, message) {
                    icon = icon.join('');
                    message = message.join('');
                    const expected = '<div id="status">' + message + '</div>';
                    document.body.innerHTML =
                        '<div id="status"></div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showStatus(message, icon);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });

        it('shows a status message (bootstrap)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                function (message) {
                    message = message.join('');
                    const expected = '<div id="status" role="alert" ' +
                        'class="statusmessage alert alert-info"><span ' +
                        'class="glyphicon glyphicon-info-sign" ' +
                        'aria-hidden="true"></span> <span>' + message + '</span></div>';
                    document.body.innerHTML =
                        '<div id="status" role="alert" class="statusmessage ' +
                        'alert alert-info hidden"><span class="glyphicon ' +
                        'glyphicon-info-sign" aria-hidden="true"></span> </div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showStatus(message);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });

        it('shows a status message (bootstrap, custom icon)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                function (icon, message) {
                    icon = icon.join('');
                    message = message.join('');
                    const expected = '<div id="status" role="alert" ' +
                        'class="statusmessage alert alert-info"><span ' +
                        'class="glyphicon glyphicon-' + icon +
                        '" aria-hidden="true"></span> <span>' + message + '</span></div>';
                    document.body.innerHTML =
                        '<div id="status" role="alert" class="statusmessage ' +
                        'alert alert-info hidden"><span class="glyphicon ' +
                        'glyphicon-info-sign" aria-hidden="true"></span> </div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showStatus(message, icon);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });
    });

    describe('showWarning', function () {
        it('shows a warning message (basic)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                function (icon, message) {
                    icon = icon.join('');
                    message = message.join('');
                    const expected = '<div id="errormessage">' + message + '</div>';
                    document.body.innerHTML =
                        '<div id="errormessage"></div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showWarning(message, icon);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });

        it('shows a warning message (bootstrap)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                function (message) {
                    message = message.join('');
                    const expected = '<div id="errormessage" role="alert" ' +
                        'class="statusmessage alert alert-danger"><span ' +
                        'class="glyphicon glyphicon-warning-sign" ' +
                        'aria-hidden="true"></span> <span>' + message + '</span></div>';
                    document.body.innerHTML =
                        '<div id="errormessage" role="alert" class="statusmessage ' +
                        'alert alert-danger hidden"><span class="glyphicon ' +
                        'glyphicon-alert" aria-hidden="true"></span> </div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showWarning(message);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });

        it('shows a warning message (bootstrap, custom icon)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                function (icon, message) {
                    icon = icon.join('');
                    message = message.join('');
                    const expected = '<div id="errormessage" role="alert" ' +
                        'class="statusmessage alert alert-danger"><span ' +
                        'class="glyphicon glyphicon-' + icon +
                        '" aria-hidden="true"></span> <span>' + message + '</span></div>';
                    document.body.innerHTML =
                        '<div id="errormessage" role="alert" class="statusmessage ' +
                        'alert alert-danger hidden"><span class="glyphicon ' +
                        'glyphicon-alert" aria-hidden="true"></span> </div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showWarning(message, icon);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });
    });

    describe('showError', function () {
        it('shows an error message (basic)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                function (icon, message) {
                    icon = icon.join('');
                    message = message.join('');
                    const expected = '<div id="errormessage">' + message + '</div>';
                    document.body.innerHTML =
                        '<div id="errormessage"></div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showError(message, icon);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });

        it('shows an error message (bootstrap)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                function (icon, message) {
                    message = message.join('');
                    const expected = '<div id="errormessage" role="alert" ' +
                        'class="statusmessage alert alert-danger"><span ' +
                        'class="glyphicon glyphicon-alert" ' +
                        'aria-hidden="true"></span> <span>' + message + '</span></div>';
                    document.body.innerHTML =
                        '<div id="errormessage" role="alert" class="statusmessage ' +
                        'alert alert-danger hidden"><span class="glyphicon ' +
                        'glyphicon-alert" aria-hidden="true"></span> </div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showError(message);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });

        it('shows an error message (bootstrap, custom icon)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                function (icon, message) {
                    icon = icon.join('');
                    message = message.join('');
                    const expected = '<div id="errormessage" role="alert" ' +
                        'class="statusmessage alert alert-danger"><span ' +
                        'class="glyphicon glyphicon-' + icon +
                        '" aria-hidden="true"></span> <span>' + message + '</span></div>';
                    document.body.innerHTML =
                        '<div id="errormessage" role="alert" class="statusmessage ' +
                        'alert alert-danger hidden"><span class="glyphicon ' +
                        'glyphicon-alert" aria-hidden="true"></span> </div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showError(message, icon);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });
    });

    describe('showRemaining', function () {
        it('shows remaining time (basic)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                fc.integer(),
                function (message, string, number) {
                    message = message.join('');
                    string = string.join('');
                    const expected = '<div id="remainingtime" class="">' + string + message + number + '</div>';
                    document.body.innerHTML =
                        '<div id="remainingtime" class="hidden"></div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showRemaining(['%s' + message + '%d', string, number]);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });

        it('shows remaining time (bootstrap)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                fc.integer(),
                function (message, string, number) {
                    message = message.join('');
                    string = string.join('');
                    const expected = '<div id="remainingtime" role="alert" ' +
                        'class="alert alert-info"><span ' +
                        'class="glyphicon glyphicon-fire" aria-hidden="true">' +
                        '</span> <span>' + string + message + number + '</span></div>';
                    document.body.innerHTML =
                        '<div id="remainingtime" role="alert" class="hidden ' +
                        'alert alert-info"><span class="glyphicon ' +
                        'glyphicon-fire" aria-hidden="true"></span> </div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showRemaining(['%s' + message + '%d', string, number]);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });
    });

    describe('showLoading', function () {
        it('shows a loading message (basic)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                function (message, icon) {
                    message = message.join('');
                    icon = icon.join('');
                    const defaultMessage = 'Loading…';
                    if (message.length === 0) {
                        message = defaultMessage;
                    }
                    const expected = '<div id="loadingindicator" class="">' + message + '</div>';
                    document.body.innerHTML =
                        '<div id="loadingindicator" class="hidden">' + defaultMessage + '</div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showLoading(message, icon);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });

        it('shows a loading message (bootstrap)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString()),
                fc.array(common.fcAlnumString()),
                function (message, icon) {
                    message = message.join('');
                    icon = icon.join('');
                    const defaultMessage = 'Loading…';
                    if (message.length === 0) {
                        message = defaultMessage;
                    }
                    const expected = '<ul class="nav navbar-nav"><li ' +
                        'id="loadingindicator" class="navbar-text"><span ' +
                        'class="glyphicon glyphicon-' + icon +
                        '" aria-hidden="true"></span> <span>' + message + '</span></li></ul>';
                    document.body.innerHTML =
                        '<ul class="nav navbar-nav"><li id="loadingindicator" ' +
                        'class="navbar-text hidden"><span class="glyphicon ' +
                        'glyphicon-time" aria-hidden="true"></span> ' +
                        defaultMessage + '</li></ul>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showLoading(message, icon);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });
    });

    describe('hideLoading', function () {
        it(
            'hides the loading message',
            function() {
                document.body.innerHTML =
                    '<ul class="nav navbar-nav"><li id="loadingindicator" ' +
                    'class="navbar-text"><span class="glyphicon ' +
                    'glyphicon-time" aria-hidden="true"></span> ' +
                    'Loading…</li></ul>';
                document.body.classList.add('loading');
                PrivateBin.Alert.init();
                PrivateBin.Alert.hideLoading();
                assert.ok(!document.body.classList.contains('loading'));
                assert.ok(document.getElementById('loadingindicator').classList.contains('hidden'));
            }
        );
    });

    describe('hideMessages', function () {
        it(
            'hides all messages',
            function() {
                document.body.innerHTML =
                    '<div id="status" role="alert" class="statusmessage ' +
                    'alert alert-info"><span class="glyphicon ' +
                    'glyphicon-info-sign" aria-hidden="true"></span> </div>' +
                    '<div id="errormessage" role="alert" class="statusmessage ' +
                    'alert alert-danger"><span class="glyphicon ' +
                    'glyphicon-alert" aria-hidden="true"></span> </div>';
                PrivateBin.Alert.init();
                PrivateBin.Alert.hideMessages();
                assert.ok(document.getElementById('status').classList.contains('hidden'));
                assert.ok(document.getElementById('errormessage').classList.contains('hidden'));
            }
        );
    });

    describe('setCustomHandler', function () {
        it('calls a given handler function', () => {
            fc.assert(fc.property(
                fc.integer({min: 0, max: 3}),
                fc.array(common.fcAlnumString()),
                function (trigger, message) {
                    message = message.join('');
                    let handlerCalled = false,
                        defaultMessage = 'Loading…',
                        functions = [
                            PrivateBin.Alert.showStatus,
                            PrivateBin.Alert.showError,
                            PrivateBin.Alert.showRemaining,
                            PrivateBin.Alert.showLoading
                        ];
                    if (message.length === 0) {
                        message = defaultMessage;
                    }
                    document.body.innerHTML =
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
                        'glyphicon-alert" aria-hidden="true"></span> </div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.setCustomHandler(function(id, element) {
                        handlerCalled = true;
                        return Math.random() > 0.5 ? true : element;
                    });
                    functions[trigger](message);
                    PrivateBin.Alert.setCustomHandler(null);
                    return handlerCalled;
                }
            ));
        });
    });
});
