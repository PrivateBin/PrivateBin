'use strict';
var common = require('../common');

describe('UiHelper', function () {
    // TODO: As per https://github.com/tmpvar/jsdom/issues/1565 there is no navigation support in jsdom, yet.
    // for now we use a mock function to trigger the event
    describe('historyChange', function () {
        this.timeout(30000);
        beforeEach(function () {
            $.PrivateBin.Helper.reset();
            cleanup();
        });

        jsc.property(
            'redirects to home, when the state is null',
            common.jscUrl(false, false),
            function (url) {
                const expected = common.urlToString(url),
                    clean = jsdom('', {url: expected});

                // make window.location.href writable
                Object.defineProperty(window.location, 'href', {
                    writable: true,
                    value: window.location.href
                });
                $.PrivateBin.UiHelper.mockHistoryChange();
                $.PrivateBin.Helper.reset();
                var result = window.location.href;
                clean();
                return expected === result;
            }
        );

        jsc.property(
            'does not redirect to home, when a new paste is created',
            common.jscUrl(false),
            jsc.nearray(common.jscBase64String()),
            function (url, fragment) {
                url.fragment = fragment.join('');
                const expected = common.urlToString(url),
                    clean = jsdom('', {url: expected});

                // make window.location.href writable
                Object.defineProperty(window.location, 'href', {
                    writable: true,
                    value: window.location.href
                });
                $.PrivateBin.UiHelper.mockHistoryChange([
                    {type: 'newpaste'}, '', expected
                ]);
                $.PrivateBin.Helper.reset();
                var result = window.location.href;
                clean();
                return expected === result;
            }
        );
    });

    describe('reloadHome', function () {
        this.timeout(30000);
        before(function () {
            $.PrivateBin.Helper.reset();
        });

        jsc.property(
            'redirects to home',
            common.jscUrl(),
            function (url) {
                const clean = jsdom('', {url: common.urlToString(url)});
                delete(url.query);
                delete(url.fragment);
                const expected = common.urlToString(url);

                // make window.location.href writable
                Object.defineProperty(window.location, 'href', {
                    writable: true,
                    value: window.location.href
                });
                $.PrivateBin.UiHelper.reloadHome();
                $.PrivateBin.Helper.reset();
                var result = window.location.href;
                clean();
                return expected === result;
            }
        );
    });

    describe('isVisible', function () {
        // TODO As per https://github.com/tmpvar/jsdom/issues/1048 there is no layout support in jsdom, yet.
        // once it is supported or a workaround is found, uncomment the section below
        /*
        before(function () {
            $.PrivateBin.Helper.reset();
        });

        jsc.property(
            'detect visible elements',
            jsc.nearray(common.jscAlnumString()),
            jsc.nearray(common.jscA2zString()),
            function (id, element) {
                id = id.join('');
                element = element.join('');
                var clean = jsdom(
                    '<' + element + ' id="' + id + '"></' + element + '>'
                );
                var result = $.PrivateBin.UiHelper.isVisible($('#' + id));
                clean();
                return result;
            }
        );
        */
    });

    describe('scrollTo', function () {
        // TODO Did not find a way to test that, see isVisible test above
    });
});

