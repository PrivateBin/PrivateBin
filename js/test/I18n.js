'use strict';
var common = require('../common');

describe('I18n', function () {
    describe('translate', function () {
        before(function () {
            $.PrivateBin.I18n.reset();
        });

        jsc.property(
            'returns message ID unchanged if no translation found',
            'string',
            function (messageId) {
                messageId   = messageId.replace(/%(s|d)/g, '%%');
                var plurals = [messageId, messageId + 's'],
                    fake    = [messageId],
                    result  = $.PrivateBin.I18n.translate(messageId);
                $.PrivateBin.I18n.reset();

                var alias = $.PrivateBin.I18n._(messageId);
                $.PrivateBin.I18n.reset();

                var pluralResult = $.PrivateBin.I18n.translate(plurals);
                $.PrivateBin.I18n.reset();

                var pluralAlias = $.PrivateBin.I18n._(plurals);
                $.PrivateBin.I18n.reset();

                var fakeResult = $.PrivateBin.I18n.translate(fake);
                $.PrivateBin.I18n.reset();

                var fakeAlias = $.PrivateBin.I18n._(fake);
                $.PrivateBin.I18n.reset();

                return messageId === result && messageId === alias &&
                    messageId === pluralResult && messageId === pluralAlias &&
                    messageId === fakeResult && messageId === fakeAlias;
            }
        );
        jsc.property(
            'replaces %s in strings with first given parameter',
            'string',
            '(small nearray) string',
            'string',
            function (prefix, params, postfix) {
                prefix    =    prefix.replace(/%(s|d)/g, '%%');
                params[0] = params[0].replace(/%(s|d)/g, '%%');
                postfix   =   postfix.replace(/%(s|d)/g, '%%');
                var translation = prefix + params[0] + postfix;
                params.unshift(prefix + '%s' + postfix);
                var result = $.PrivateBin.I18n.translate.apply(this, params);
                $.PrivateBin.I18n.reset();
                var alias = $.PrivateBin.I18n._.apply(this, params);
                $.PrivateBin.I18n.reset();
                return translation === result && translation === alias;
            }
        );
    });

    describe('getPluralForm', function () {
        before(function () {
            $.PrivateBin.I18n.reset();
        });

        jsc.property(
            'returns valid key for plural form',
            common.jscSupportedLanguages(),
            'integer',
            function(language, n) {
                $.PrivateBin.I18n.reset(language);
                var result = $.PrivateBin.I18n.getPluralForm(n);
                // arabic seems to have the highest plural count with 6 forms
                return result >= 0 && result <= 5;
            }
        );
    });

    // loading of JSON via AJAX needs to be tested in the browser, this just mocks it
    // TODO: This needs to be tested using a browser.
    describe('loadTranslations', function () {
        this.timeout(30000);
        before(function () {
            $.PrivateBin.I18n.reset();
        });

        jsc.property(
            'downloads and handles any supported language',
            common.jscSupportedLanguages(),
            function(language) {
                // cleanup
                var clean = jsdom('', {cookie: ['lang=en']});
                $.PrivateBin.I18n.reset('en');
                $.PrivateBin.I18n.loadTranslations();
                clean();

                // mock
                clean = jsdom('', {cookie: ['lang=' + language]});
                $.PrivateBin.I18n.reset(language, require('../../i18n/' + language + '.json'));
                var result = $.PrivateBin.I18n.translate('en'),
                    alias  = $.PrivateBin.I18n._('en');
                clean();
                return language === result && language === alias;
            }
        );

        jsc.property(
            'should default to en',
            function() {
                var clean = jsdom('', {url: 'https://privatebin.net/'});

                // when navigator.userLanguage is undefined and no default language
                // is specified, it would throw an error
                [ 'language', 'userLanguage' ].forEach(function (key) {
                    Object.defineProperty(navigator, key, {
                        value: undefined,
                        writeable: false
                    });
                });

                $.PrivateBin.I18n.reset('en');
                $.PrivateBin.I18n.loadTranslations();
                var result = $.PrivateBin.I18n.translate('en'),
                    alias  = $.PrivateBin.I18n._('en');

                clean();
                return 'en' === result && 'en' === alias;
            }
        );
    });
});

