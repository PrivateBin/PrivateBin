'use strict';
const common = require('../common');
const fc = require('fast-check');
const path = require('path');

/**
 * icon markup of the bootstrap5 template
 *
 * @param {string} name - bootstrap icon name
 * @returns {string}
 */
function icon(name) {
    return '<svg width="16" height="16" fill="currentColor" aria-hidden="true">' +
        '<use href="img/bootstrap-icons.svg#' + name + '"></use></svg>';
}

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
                        'class="d-flex align-items-center gap-2 alert alert-info">' +
                        '<div>' + icon('info-circle') + ' <span>' +
                        message + '</span></div></div>';
                    document.body.innerHTML =
                        '<div id="status" role="alert" class="d-flex ' +
                        'align-items-center gap-2 alert alert-info hidden">' +
                        '<div>' + icon('info-circle') + ' </div></div>';
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
                function (customIcon, message) {
                    customIcon = customIcon.join('');
                    message = message.join('');
                    const expected = '<div id="status" role="alert" ' +
                        'class="d-flex align-items-center gap-2 alert alert-info">' +
                        '<div>' + icon(customIcon || 'info-circle') + ' <span>' +
                        message + '</span></div></div>';
                    document.body.innerHTML =
                        '<div id="status" role="alert" class="d-flex ' +
                        'align-items-center gap-2 alert alert-info hidden">' +
                        '<div>' + icon('info-circle') + ' </div></div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showStatus(message, customIcon);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });

        it('replaces a status message rendered by the server, keeping its buttons (bootstrap)', () => {
            fc.assert(fc.property(
                fc.array(common.fcAlnumString(), {minLength: 1}),
                fc.array(common.fcAlnumString(), {minLength: 1}),
                function (serverMessage, message) {
                    serverMessage = 'server ' + serverMessage.join('');
                    message = message.join('');
                    const button = '<button type="button" id="new-from-alert">Start over</button>';
                    document.body.innerHTML =
                        '<div id="status" role="alert" class="d-flex ' +
                        'align-items-center gap-2 alert alert-success"><div>' +
                        icon('info-circle') + ' ' + serverMessage + ' </div>' +
                        button + '</div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showStatus(message);
                    const status = document.getElementById('status');
                    return status.innerHTML === '<div>' + icon('info-circle') +
                        ' <span>' + message + '</span></div>' + button &&
                        status.textContent.indexOf(serverMessage) === -1;
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
                function (message) {
                    message = message.join('');
                    const expected = '<div id="errormessage" role="alert" ' +
                        'class="alert alert-danger">' + icon('exclamation-triangle') +
                        ' <span>' + message + '</span></div>';
                    document.body.innerHTML =
                        '<div id="errormessage" role="alert" class="hidden ' +
                        'alert alert-danger">' + icon('exclamation-triangle') + ' </div>';
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
                function (customIcon, message) {
                    customIcon = customIcon.join('');
                    message = message.join('');
                    const expected = '<div id="errormessage" role="alert" ' +
                        'class="alert alert-danger">' +
                        icon(customIcon || 'exclamation-triangle') +
                        ' <span>' + message + '</span></div>';
                    document.body.innerHTML =
                        '<div id="errormessage" role="alert" class="hidden ' +
                        'alert alert-danger">' + icon('exclamation-triangle') + ' </div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showWarning(message, customIcon);
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
                function (message) {
                    message = message.join('');
                    const expected = '<div id="errormessage" role="alert" ' +
                        'class="alert alert-danger">' + icon('exclamation-triangle') +
                        ' <span>' + message + '</span></div>';
                    document.body.innerHTML =
                        '<div id="errormessage" role="alert" class="hidden ' +
                        'alert alert-danger">' + icon('exclamation-triangle') + ' </div>';
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
                function (customIcon, message) {
                    customIcon = customIcon.join('');
                    message = message.join('');
                    const expected = '<div id="errormessage" role="alert" ' +
                        'class="alert alert-danger">' +
                        icon(customIcon || 'exclamation-triangle') +
                        ' <span>' + message + '</span></div>';
                    document.body.innerHTML =
                        '<div id="errormessage" role="alert" class="hidden ' +
                        'alert alert-danger">' + icon('exclamation-triangle') + ' </div>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showError(message, customIcon);
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
                        'class="alert alert-info">' + icon('fire') +
                        ' <span>' + string + message + number + '</span></div>';
                    document.body.innerHTML =
                        '<div id="remainingtime" role="alert" class="hidden ' +
                        'alert alert-info">' + icon('fire') + ' </div>';
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
                function (message, customIcon) {
                    message = message.join('');
                    customIcon = customIcon.join('');
                    const defaultMessage = 'Loading…';
                    if (message.length === 0) {
                        message = defaultMessage;
                    }
                    const expected = '<ul class="navbar-nav"><li ' +
                        'id="loadingindicator" class="navbar-text me-auto">' +
                        icon(customIcon || 'clock') + ' <span>' + message +
                        '</span></li></ul>';
                    document.body.innerHTML =
                        '<ul class="navbar-nav"><li id="loadingindicator" ' +
                        'class="navbar-text hidden me-auto">' + icon('clock') +
                        ' ' + defaultMessage + '</li></ul>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showLoading(message, customIcon);
                    const result = document.body.innerHTML;
                    return expected === result;
                }
            ));
        });

        it('keeps the previous icon, if none is given (bootstrap)', () => {
            fc.assert(fc.property(
                fc.constantFrom('clock', 'cloud-download', 'cloud-upload'),
                fc.constantFrom(undefined, null, ''),
                function (customIcon, noIcon) {
                    document.body.innerHTML =
                        '<ul class="navbar-nav"><li id="loadingindicator" ' +
                        'class="navbar-text hidden me-auto">' + icon('clock') +
                        ' Loading…</li></ul>';
                    PrivateBin.Alert.init();
                    PrivateBin.Alert.showLoading('first', customIcon);
                    PrivateBin.Alert.hideLoading();
                    PrivateBin.Alert.showLoading('second', noIcon);
                    const use = document.querySelector('#loadingindicator use');
                    return use.getAttribute('href') === 'img/bootstrap-icons.svg#' + customIcon &&
                        document.querySelectorAll('#loadingindicator svg').length === 1 &&
                        document.querySelector('#loadingindicator span').textContent === 'second';
                }
            ));
        });
    });

    describe('hideLoading', function () {
        it(
            'hides the loading message',
            function() {
                document.body.innerHTML =
                    '<ul class="navbar-nav"><li id="loadingindicator" ' +
                    'class="navbar-text me-auto">' + icon('clock') +
                    ' Loading…</li></ul>';
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
                    '<div id="status" role="alert" class="d-flex ' +
                    'align-items-center gap-2 alert alert-info"><div>' +
                    icon('info-circle') + ' </div></div>' +
                    '<div id="errormessage" role="alert" class="alert ' +
                    'alert-danger">' + icon('exclamation-triangle') + ' </div>';
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
                        '<ul class="navbar-nav"><li id="loadingindicator" ' +
                        'class="navbar-text hidden me-auto">' + icon('clock') +
                        ' ' + defaultMessage + '</li></ul>' +
                        '<div id="remainingtime" role="alert" class="hidden ' +
                        'alert alert-info">' + icon('fire') + ' </div>' +
                        '<div id="status" role="alert" class="d-flex ' +
                        'align-items-center gap-2 alert alert-info"><div>' +
                        icon('info-circle') + ' </div></div>' +
                        '<div id="errormessage" role="alert" class="alert ' +
                        'alert-danger">' + icon('exclamation-triangle') + ' </div>';
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

    describe('icons', function () {
        it('only uses icons that exist in the icon sprite', function () {
            const source = fs.readFileSync(path.join(__dirname, '..', 'privatebin.js'), 'utf8');
            const sprite = fs.readFileSync(path.join(__dirname, '..', '..', 'img', 'bootstrap-icons.svg'), 'utf8');
            const calls = source.match(/(Alert\.show(Status|Warning|Error|Loading)|Helper\.setIcon)\([^;]*\);/g);
            assert.ok(calls.length > 0, 'found icon setting calls');
            let names = [];
            calls.forEach(function (call) {
                // skip the first argument, the message or element
                const icons = call.substring(call.indexOf(',')).match(/'[a-z][a-z0-9-]*'/g) || [];
                names = names.concat(icons.map(name => name.slice(1, -1)));
            });
            assert.ok(names.length > 0, 'found icon names');
            names.forEach(function (name) {
                assert.ok(sprite.includes('id="' + name + '"'), 'icon ' + name + ' exists in sprite');
            });
        });
    });
});
