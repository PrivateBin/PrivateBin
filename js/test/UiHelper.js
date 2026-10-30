'use strict';
const common = require('../common');
const fc = require('fast-check');

describe('UiHelper', function () {
    describe('historyChange', function () {
        this.timeout(30000);
        beforeEach(function () {
            PrivateBin.Helper.reset();
            cleanup();
        });


        /** @param {object?} state */
        function dispatchPopState(state = null) {
            let eventError = null;
            const captureError = event => {
                eventError = event.error;
                event.preventDefault();
            };
            window.addEventListener('error', captureError);
            PrivateBin.UiHelper.init();
            window.dispatchEvent(new window.PopStateEvent('popstate', {state: state}));
            window.removeEventListener('error', captureError);
            assert.strictEqual(eventError, null);
        }

        it('redirects to home, when the state is null', () => {
            fc.assert(fc.property(
                common.fcUrl(false, false),
                function (url) {
                    const expected = common.urlToString(url),
                        clean = globalThis.cleanup('', {url: expected});

                    dispatchPopState();
                    PrivateBin.Helper.reset();
                    var result = window.location.href;
                    clean();
                    return expected === result;
                }
            ));
        });

        it('does not redirect to home, when a new document is created', () => {
            fc.assert(fc.property(
                common.fcUrl(false),
                fc.array(common.fcBase64String(), {minLength: 1}),
                function (url, fragment) {
                    url.fragment = fragment.join('');
                    const expected = common.urlToString(url),
                        clean = globalThis.cleanup('', {url: expected});

                    dispatchPopState([
                        {type: 'newpaste'}, '', expected
                    ]);
                    PrivateBin.Helper.reset();
                    var result = window.location.href;
                    clean();
                    return expected === result;
                }
            ));
        });
    });

    describe('reloadHome', function () {
        // TODO triggers error messages in jsDOM since version 11
        /*
        this.timeout(30000);
        before(function () {
            PrivateBin.Helper.reset();
        });

        it('redirects to home', () => {
            fc.assert(fc.property(
                common.fcUrl(),
                function (url) {
                    const clean = globalThis.cleanup('', {url: common.urlToString(url)});
                    delete(url.query);
                    delete(url.fragment);
                    const expected = common.urlToString(url);

                    PrivateBin.UiHelper.reloadHome();
                    PrivateBin.Helper.reset();
                    var result = window.location.href;
                    clean();
                    return expected === result;
                }
            ));
        });
        */
    });

    describe('showModal & hideModal', function () {
        beforeEach(function () {
            cleanup();
            document.body.innerHTML =
                '<div id="testmodal" tabindex="-1" class="modal" role="dialog" aria-hidden="true">' +
                '<div class="modal-dialog" role="document"><div class="modal-content">' +
                '<div class="modal-body"><button type="button" data-bs-dismiss="modal">Close</button>' +
                '</div></div></div></div>';
        });

        /**
         * runs the given action, while collecting messages logged as errors
         *
         * @param {function} action
         * @returns {string[]} logged errors
         */
        function collectErrors(action) {
            const errors = [];
            const consoleError = console.error;
            console.error = function (...args) {
                errors.push(args.join(' '));
            };
            try {
                action();
            } finally {
                console.error = consoleError;
            }
            return errors;
        }

        it('shows and hides a modal repeatedly', function () {
            const modal = document.getElementById('testmodal');
            const errors = collectErrors(function () {
                for (let i = 0; i < 3; ++i) {
                    PrivateBin.UiHelper.showModal(modal);
                    assert.ok(modal.classList.contains('show'), 'modal is shown');
                    assert.strictEqual(modal.getAttribute('aria-modal'), 'true');
                    PrivateBin.UiHelper.hideModal(modal);
                    assert.ok(!modal.classList.contains('show'), 'modal is hidden');
                    assert.strictEqual(modal.style.display, 'none');
                }
            });
            assert.deepStrictEqual(errors, []);
        });

        it('ignores hiding a modal that was never shown', function () {
            const modal = document.getElementById('testmodal');
            assert.doesNotThrow(() => PrivateBin.UiHelper.hideModal(modal));
            assert.ok(!modal.classList.contains('show'));
        });

        it('keeps the options of the first showing of a modal', function () {
            const modal = document.getElementById('testmodal');
            PrivateBin.UiHelper.showModal(modal, {backdrop: 'static', keyboard: false});
            modal.dispatchEvent(new window.KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));
            assert.ok(modal.classList.contains('show'), 'modal can not be closed with Escape');
            PrivateBin.UiHelper.hideModal(modal);
            PrivateBin.UiHelper.showModal(modal);
            modal.dispatchEvent(new window.KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));
            assert.ok(modal.classList.contains('show'), 'modal still can not be closed with Escape');
        });

        it('lets a modal without options be closed with Escape', function () {
            const modal = document.getElementById('testmodal');
            PrivateBin.UiHelper.showModal(modal);
            modal.dispatchEvent(new window.KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));
            assert.ok(!modal.classList.contains('show'), 'modal was closed with Escape');
        });
    });
});
