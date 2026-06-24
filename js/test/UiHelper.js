'use strict';
var common = require('../common');

describe('UiHelper', function () {
    describe('reloadHome', function () {
        // TODO triggers error messages in jsDOM since version 11
        /*
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

                $.PrivateBin.UiHelper.reloadHome();
                $.PrivateBin.Helper.reset();
                var result = window.location.href;
                clean();
                return expected === result;
            }
        );
        */
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
