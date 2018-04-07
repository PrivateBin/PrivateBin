import $ from 'jquery'
import jQuery from 'jquery'

/**
 * internationalization module
 *
 * @name I18n
 * @class
 */

/**
 * const for string of loaded language
 *
 * @name I18n.languageLoadedEvent
 * @private
 * @prop   {string}
 * @readonly
 */
var languageLoadedEvent = 'languageLoaded';

/**
 * supported languages, minus the built in 'en'
 *
 * @name I18n.supportedLanguages
 * @private
 * @prop   {string[]}
 * @readonly
 */
var supportedLanguages = ['de', 'es', 'fr', 'it', 'no', 'pl', 'pt', 'oc', 'ru', 'sl', 'zh'];

/**
 * built in language
 *
 * @name I18n.language
 * @private
 * @prop   {string|null}
 */
var language = null;

/**
 * translation cache
 *
 * @name I18n.translations
 * @private
 * @enum   {Object}
 */
var translations = {};

/**
 * translate a string, alias for I18n.translate
 *
 * @name   I18n._
 * @function
 * @param  {jQuery} $element - optional
 * @param  {string} messageId
 * @param  {...*} args - one or multiple parameters injected into placeholders
 * @return {string}
 */
export function _()
{
    return translate.apply(this, arguments);
}

/**
 * translate a string
 *
 * Optionally pass a jQuery element as the first parameter, to automatically
 * let the text of this element be replaced. In case the (asynchronously
 * loaded) language is not downloadet yet, this will make sure the string
 * is replaced when it is actually loaded.
 * So for easy translations passing the jQuery object to apply it to is
 * more save, especially when they are loaded in the beginning.
 *
 * @name   I18n.translate
 * @function
 * @param  {jQuery} $element - optional
 * @param  {string} messageId
 * @param  {...*} args - one or multiple parameters injected into placeholders
 * @return {string}
 */
export function translate()
{
    // convert parameters to array
    var args = Array.prototype.slice.call(arguments),
        messageId,
        $element = null;

    // parse arguments
    if (args[0] instanceof jQuery) {
        // optional jQuery element as first parameter
        $element = args[0];
        args.shift();
    }

    // extract messageId from arguments
    var usesPlurals = $.isArray(args[0]);
    if (usesPlurals) {
        // use the first plural form as messageId, otherwise the singular
        messageId = args[0].length > 1 ? args[0][1] : args[0][0];
    } else {
        messageId = args[0];
    }

    if (messageId.length === 0) {
        return messageId;
    }

    // if no translation string cannot be found (in translations object)
    if (!translations.hasOwnProperty(messageId) || language === null) {
        // if language is still loading and we have an elemt assigned
        if (language === null && $element !== null) {
            // handle the error by attaching the language loaded event
            var orgArguments = arguments;
            $(document).on(languageLoadedEvent, function () {
                // log to show that the previous error could be mitigated
                console.warn('Fix missing translation of \'' + messageId + '\' with now loaded language ' + language);
                // re-execute this function
                translate.apply(this, orgArguments);
            });

            // and fall back to English for now until the real language
            // file is loaded
        }

        // for all other langauges than English for which this behaviour
        // is expected as it is built-in, log error
        if (language !== null && language !== 'en') {
            console.error('Missing translation for: \'' + messageId + '\' in language ' + language);
            // fallback to English
        }

        // save English translation (should be the same on both sides)
        translations[messageId] = args[0];
    }

    // lookup plural translation
    if (usesPlurals && $.isArray(translations[messageId])) {
        var n = parseInt(args[1] || 1, 10),
            key = getPluralForm(n),
            maxKey = translations[messageId].length - 1;
        if (key > maxKey) {
            key = maxKey;
        }
        args[0] = translations[messageId][key];
        args[1] = n;
    } else {
        // lookup singular translation
        args[0] = translations[messageId];
    }

    // format string
    var output = Helper.sprintf.apply(this, args);

    // if $element is given, apply text to element
    if ($element !== null) {
        // get last text node of element
        var content = $element.contents();
        if (content.length > 1) {
            content[content.length - 1].nodeValue = ' ' + output;
        } else {
            $element.text(output);
        }
    }

    return output;
}

/**
 * per language functions to use to determine the plural form
 *
 * @see    {@link http://localization-guide.readthedocs.org/en/latest/l10n/pluralforms.html}
 * @name   I18n.getPluralForm
 * @function
 * @param  {int} n
 * @return {int} array key
 */
export function getPluralForm(n) {
    switch (language)
    {
        case 'fr':
        case 'oc':
        case 'zh':
            return n > 1 ? 1 : 0;
        case 'pl':
            return n === 1 ? 0 : (n % 10 >= 2 && n %10 <=4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2);
        case 'ru':
            return n % 10 === 1 && n % 100 !== 11 ? 0 : (n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2);
        case 'sl':
            return n % 100 === 1 ? 1 : (n % 100 === 2 ? 2 : (n % 100 === 3 || n % 100 === 4 ? 3 : 0));
        // de, en, es, it, no, pt
        default:
            return n !== 1 ? 1 : 0;
    }
}

/**
 * load translations into cache
 *
 * @name   I18n.loadTranslations
 * @function
 */
export function loadTranslations()
{
    var newLanguage = Helper.getCookie('lang');

    // auto-select language based on browser settings
    if (newLanguage.length === 0) {
        newLanguage = (navigator.language || navigator.userLanguage).substring(0, 2);
    }

    // if language is already used skip update
    if (newLanguage === language) {
        return;
    }

    // if language is built-in (English) skip update
    if (newLanguage === 'en') {
        language = 'en';
        return;
    }

    // if language is not supported, show error
    if (supportedLanguages.indexOf(newLanguage) === -1) {
        console.error('Language \'%s\' is not supported. Translation failed, fallback to English.', newLanguage);
        language = 'en';
        return;
    }

    // load strings from JSON
    $.getJSON('i18n/' + newLanguage + '.json', function(data) {
        language = newLanguage;
        translations = data;
        $(document).triggerHandler(languageLoadedEvent);
    }).fail(function (data, textStatus, errorMsg) {
        console.error('Language \'%s\' could not be loaded (%s: %s). Translation failed, fallback to English.', newLanguage, textStatus, errorMsg);
        language = 'en';
    });
}

/**
 * resets state, used for unit testing
 *
 * @name   I18n.reset
 * @function
 */
export function reset(mockLanguage, mockTranslations)
{
    language = mockLanguage || null;
    translations = mockTranslations || {};
}
