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
});
