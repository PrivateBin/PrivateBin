<?php declare(strict_types=1);
/**
 * PrivateBin
 *
 * a zero-knowledge paste bin
 *
 * @link      https://github.com/PrivateBin/PrivateBin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   https://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 */

namespace PrivateBin;

use AppendIterator;
use GlobIterator;

/**
 * I18n
 *
 * provides internationalization tools like translation, browser language detection, etc.
 */
class I18n
{
    /**
     * language
     *
     * @access protected
     * @static
     * @var    string
     */
    protected static $_language = 'en';

    /**
     * language fallback
     *
     * @access protected
     * @static
     * @var    string
     */
    protected static $_languageFallback = 'en';

    /**
     * language labels
     *
     * @access protected
     * @static
     * @var    array
     */
    protected static $_languageLabels = array();

    /**
     * available languages
     *
     * @access protected
     * @static
     * @var    array
     */
    protected static $_availableLanguages = array();

    /**
     * path to language files
     *
     * @access protected
     * @static
     * @var    string
     */
    protected static $_path = '';

    /**
     * translation cache
     *
     * @access protected
     * @static
     * @var    array
     */
    protected static $_translations = array();

    /**
     * translate a string, alias for translate()
     *
     * @access public
     * @static
     * @param  string|array $messageId
     * @param  mixed $args one or multiple parameters injected into placeholders
     * @return string
     */
    public static function _($messageId, ...$args)
    {
        return forward_static_call_array('PrivateBin\I18n::translate', func_get_args());
    }

    /**
     * translate a string
     *
     * @access public
     * @static
     * @param  string|array $messageId
     * @param  mixed $args one or multiple parameters injected into placeholders
     * @return string
     */
    public static function translate($messageId, ...$args)
    {
        if (empty($messageId)) {
            return $messageId;
        }
        if (empty(self::$_translations)) {
            self::loadTranslations();
        }
        $messages = $messageId;
        if (is_array($messageId)) {
            $messageId = count($messageId) > 1 ? $messageId[1] : $messageId[0];
        }
        if (!array_key_exists($messageId, self::$_translations)) {
            self::$_translations[$messageId] = $messages;
        }
        array_unshift($args, $messageId);
        if (is_array(self::$_translations[$messageId])) {
            $number = (int) $args[1];
            $key    = self::_getPluralForm($number);
            $max    = count(self::$_translations[$messageId]) - 1;
            if ($key > $max) {
                $key = $max;
            }

            $args[0] = self::$_translations[$messageId][$key];
            $args[1] = $number;
        } else {
            $args[0] = self::$_translations[$messageId];
        }
        // encode any non-integer arguments and the message ID, if it doesn't contain a link or keyboard input
        $argsCount = count($args);
        for ($i = 0; $i < $argsCount; ++$i) {
            if ($i === 0) {
                if (str_contains($args[0], '<a') || str_contains($args[0], '<kbd>')) {
                    continue;
                }
            } elseif (is_int($args[$i])) {
                continue;
            }
            $args[$i] = self::encode($args[$i]);
        }
        return call_user_func_array('sprintf', $args);
    }

    /**
     * encode HTML entities for output into an HTML5 document
     *
     * @access public
     * @static
     * @param  string $string
     * @return string
     */
    public static function encode($string)
    {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5 | ENT_DISALLOWED, 'UTF-8', false);
    }

    /**
     * loads translations
     *
     * From: https://stackoverflow.com/questions/3770513/detect-browser-language-in-php#3771447
     *
     * @access public
     * @static
     */
    public static function loadTranslations()
    {
        $availableLanguages = self::getAvailableLanguages();

        // check if the lang cookie was set and that language exists
        if (
            array_key_exists('lang', $_COOKIE) &&
            ($key = array_search($_COOKIE['lang'], $availableLanguages)) !== false
        ) {
            $match = $availableLanguages[$key];
        }
        // find a translation file matching the browsers language preferences
        else {
            $match = self::_getMatchingLanguage(
                self::getBrowserLanguages(), $availableLanguages
            );
        }

        // load translations
        self::$_language     = $match;
        self::$_translations = ($match == 'en') ? array() : Json::decode(
            file_get_contents(self::_getPath($match . '.json'))
        );
    }

    /**
     * get list of available translations based on files found
     *
     * @access public
     * @static
     * @return array
     */
    public static function getAvailableLanguages()
    {
        if (count(self::$_availableLanguages) == 0) {
            self::$_availableLanguages[] = 'en'; // en.json is not part of the release archive
            $languageIterator            = new AppendIterator();
            $languageIterator->append(new GlobIterator(self::_getPath('??.json')));
            $languageIterator->append(new GlobIterator(self::_getPath('???.json'))); // for jbo
            foreach ($languageIterator as $file) {
                $language = $file->getBasename('.json');
                if ($language != 'en') {
                    self::$_availableLanguages[] = $language;
                }
            }
        }
        return self::$_availableLanguages;
    }

    /**
     * detect the clients supported languages and return them ordered by preference
     *
     * From: https://stackoverflow.com/questions/3770513/detect-browser-language-in-php#3771447
     *
     * @access public
     * @static
     * @return array
     */
    public static function getBrowserLanguages()
    {
        $languages = array();
        if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER)) {
            $languageRanges = explode(',', trim($_SERVER['HTTP_ACCEPT_LANGUAGE']));
            foreach ($languageRanges as $languageRange) {
                if (preg_match(
                    '/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/',
                    trim($languageRange), $match
                )) {
                    if (!isset($match[2])) {
                        $match[2] = '1.0';
                    } else {
                        $match[2] = (string) floatval($match[2]);
                    }
                    if (!isset($languages[$match[2]])) {
                        $languages[$match[2]] = array();
                    }
                    $languages[$match[2]][] = strtolower($match[1]);
                }
            }
            krsort($languages);
        }
        return $languages;
    }

    /**
     * get currently loaded language
     *
     * @access public
     * @static
     * @return string
     */
    public static function getLanguage()
    {
        return self::$_language;
    }

    /**
     * get list of language labels
     *
     * Only for given language codes, otherwise all labels.
     *
     * @access public
     * @static
     * @param  array $languages
     * @return array
     */
    public static function getLanguageLabels($languages = array())
    {
        $file = self::_getPath('languages.json');
        if (count(self::$_languageLabels) == 0 && is_readable($file)) {
            self::$_languageLabels = Json::decode(file_get_contents($file));
        }
        if (count($languages) == 0) {
            return self::$_languageLabels;
        }
        return array_intersect_key(self::$_languageLabels, array_flip($languages));
    }

    /**
     * determines if the current language is written right-to-left (RTL)
     *
     * @access public
     * @static
     * @return bool
     */
    public static function isRtl()
    {
        return in_array(self::$_language, array('ar', 'he'));
    }

    /**
     * set the default language
     *
     * @access public
     * @static
     * @param  string $lang
     */
    public static function setLanguageFallback($lang)
    {
        if (in_array($lang, self::getAvailableLanguages())) {
            self::$_languageFallback = $lang;
        }
    }

    /**
     * get language file path
     *
     * @access protected
     * @static
     * @param  string $file
     * @return string
     */
    protected static function _getPath($file = '')
    {
        if (empty(self::$_path)) {
            self::$_path = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'i18n';
        }
        return self::$_path . (empty($file) ? '' : DIRECTORY_SEPARATOR . $file);
    }

    /**
     * determines the plural form to use based on current language and given number
     *
     * From: https://docs.translatehouse.org/projects/localization-guide/en/latest/l10n/pluralforms.html
     *
     * @access protected
     * @static
     * @param  int $n
     * @return int
     */
    protected static function _getPluralForm($n)
    {
        switch (self::$_language) {
            case 'ar':
                return $n === 0 ? 0 : ($n === 1 ? 1 : ($n === 2 ? 2 : ($n % 100 >= 3 && $n % 100 <= 10 ? 3 : ($n % 100 >= 11 ? 4 : 5))));
            case 'cs':
            case 'sk':
                return $n === 1 ? 0 : ($n >= 2 && $n <= 4 ? 1 : 2);
            case 'co':
            case 'fr':
            case 'oc':
            case 'tr':
            case 'zh':
                return $n > 1 ? 1 : 0;
            case 'he':
                return $n === 1 ? 0 : ($n === 2 ? 1 : (($n < 0 || $n > 10) && ($n % 10 === 0) ? 2 : 3));
            case 'id':
            case 'ja':
            case 'jbo':
            case 'th':
                return 0;
            case 'lt':
                return $n % 10 === 1 && $n % 100 !== 11 ? 0 : (($n % 10 >= 2 && $n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);
            case 'pl':
                return $n === 1 ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);
            case 'ro':
                return $n === 1 ? 0 : (($n === 0 || ($n % 100 > 0 && $n % 100 < 20)) ? 1 : 2);
            case 'ru':
            case 'uk':
                return $n % 10 === 1 && $n % 100 != 11 ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2);
            case 'sl':
                return $n % 100 === 1 ? 1 : ($n % 100 === 2 ? 2 : ($n % 100 === 3 || $n % 100 === 4 ? 3 : 0));
            default:
                // bg, ca, de, el, en, es, et, fi, hu, it, nl, no, pt
                return $n !== 1 ? 1 : 0;
        }
    }

    /**
     * compares two language preference arrays and returns the preferred match
     *
     * From: https://stackoverflow.com/questions/3770513/detect-browser-language-in-php#3771447
     *
     * @access protected
     * @static
     * @param  array $acceptedLanguages
     * @param  array $availableLanguages
     * @return string
     */
    protected static function _getMatchingLanguage($acceptedLanguages, $availableLanguages)
    {
        $matches = array();
        $any     = false;
        foreach ($acceptedLanguages as $acceptedQuality => $acceptedValues) {
            $acceptedQuality = floatval($acceptedQuality);
            if ($acceptedQuality === 0.0) {
                continue;
            }
            foreach ($availableLanguages as $availableValue) {
                $availableQuality = 1.0;
                foreach ($acceptedValues as $acceptedValue) {
                    if ($acceptedValue === '*') {
                        $any = true;
                    }
                    $matchingGrade = self::_matchLanguage($acceptedValue, $availableValue);
                    if ($matchingGrade > 0) {
                        $q = (string) ($acceptedQuality * $availableQuality * $matchingGrade);
                        if (!isset($matches[$q])) {
                            $matches[$q] = array();
                        }
                        if (!in_array($availableValue, $matches[$q])) {
                            $matches[$q][] = $availableValue;
                        }
                    }
                }
            }
        }
        if (count($matches) === 0 && $any) {
            if (count($availableLanguages) > 0) {
                $matches['1.0'] = $availableLanguages;
            }
        }
        if (count($matches) === 0) {
            return self::$_languageFallback;
        }
        krsort($matches);
        $topmatches = current($matches);
        return current($topmatches);
    }

    /**
     * compare two language IDs and return the degree they match
     *
     * From: https://stackoverflow.com/questions/3770513/detect-browser-language-in-php#3771447
     *
     * @access protected
     * @static
     * @param  string $a
     * @param  string $b
     * @return float
     */
    protected static function _matchLanguage($a, $b)
    {
        $a = explode('-', $a);
        $b = explode('-', $b);
        for ($i = 0, $n = min(count($a), count($b)); $i < $n; ++$i) {
            if ($a[$i] !== $b[$i]) {
                break;
            }
        }
        return $i === 0 ? 0 : (float) $i / count($a);
    }
}
