<?php
/**
 * ZeroBin
 *
 * a zero-knowledge paste bin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:zerobin
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.20
 */

/**
 * i18n
 *
 * provides internationalization tools like translation, browser language detection, etc.
 */
class i18n
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
     * @param  string $messageId
     * @param  mixed $args one or multiple parameters injected into placeholders
     * @return string
     */
    public static function _($messageId)
    {
        return call_user_func_array(array('i18n', 'translate'), func_get_args());
    }

    /**
     * translate a string
     *
     * @access public
     * @static
     * @param  string $messageId
     * @param  mixed $args one or multiple parameters injected into placeholders
     * @return string
     */
    public static function translate($messageId)
    {
        if (empty($messageId)) return $messageId;
        if (count(self::$_translations) === 0) self::loadTranslations();
        $messages = $messageId;
        if (is_array($messageId))
        {
            $messageId = count($messageId) > 1 ? $messageId[1] : $messageId[0];
        }
        if (!array_key_exists($messageId, self::$_translations))
        {
            self::$_translations[$messageId] = $messages;
        }
        $args = func_get_args();
        if (is_array(self::$_translations[$messageId]))
        {
            $number = (int) $args[1];
            $key = self::_getPluralForm($number);
            $max = count(self::$_translations[$messageId]) - 1;
            if ($key > $max) $key = $max;

            $args[0] = self::$_translations[$messageId][$key];
            $args[1] = $number;
        }
        else
        {
            $args[0] = self::$_translations[$messageId];
        }
        return call_user_func_array('sprintf', $args);
    }

    /**
     * loads translations
     *
     * From: http://stackoverflow.com/questions/3770513/detect-browser-language-in-php#3771447
     *
     * @access protected
     * @static
     * @return void
     */
    public static function loadTranslations()
    {
        // find a matching translation file
        $availableLanguages = array();
        $path = PUBLIC_PATH . DIRECTORY_SEPARATOR . 'i18n';
        $i18n = dir($path);
        while (false !== ($file = $i18n->read()))
        {
            if (preg_match('/^([a-z]{2}).json$/', $file, $match) === 1)
            {
                $availableLanguages[] = $match[1];
            }
        }

        $match = self::_getMatchingLanguage(
            self::getBrowserLanguages(), $availableLanguages
        );
        // load translations
        if ($match != 'en')
        {
            self::$_language = $match;
            self::$_translations = json_decode(
                file_get_contents($path . DIRECTORY_SEPARATOR . $match . '.json'),
                true
            );
        }
    }

    /**
     * detect the clients supported languages and return them ordered by preference
     *
     * From: http://stackoverflow.com/questions/3770513/detect-browser-language-in-php#3771447
     *
     * @return array
     */
    public static function getBrowserLanguages()
    {
        $languages = array();
        if (array_key_exists('HTTP_ACCEPT_LANGUAGE', $_SERVER))
        {
            $languageRanges = explode(',', trim($_SERVER['HTTP_ACCEPT_LANGUAGE']));
            foreach ($languageRanges as $languageRange) {
                if (preg_match(
                    '/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/',
                    trim($languageRange), $match
                ))
                {
                    if (!isset($match[2]))
                    {
                        $match[2] = '1.0';
                    }
                    else
                    {
                        $match[2] = (string) floatval($match[2]);
                    }
                    if (!isset($languages[$match[2]]))
                    {
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
     * determines the plural form to use based on current language and given number
     *
     * From: http://localization-guide.readthedocs.org/en/latest/l10n/pluralforms.html
     *
     * @param int $n
     * @return int
     */
    protected static function _getPluralForm($n)
    {
        switch (self::$_language) {
            case 'fr':
                return ($n > 1 ? 1 : 0);
            case 'pl':
                return ($n == 1 ? 0 : $n%10 >= 2 && $n %10 <=4 && ($n%100 < 10 || $n%100 >= 20) ? 1 : 2);
            // en, de
            default:
                return ($n != 1 ? 1 : 0);
        }
    }

    /**
     * compares two language preference arrays and returns the preferred match
     *
     * From: http://stackoverflow.com/questions/3770513/detect-browser-language-in-php#3771447
     *
     * @param array $acceptedLanguages
     * @param array $availableLanguages
     * @return string
     */
    protected static function _getMatchingLanguage($acceptedLanguages, $availableLanguages) {
        $matches = array();
        $any = false;
        foreach ($acceptedLanguages as $acceptedQuality => $acceptedValues) {
            $acceptedQuality = floatval($acceptedQuality);
            if ($acceptedQuality === 0.0) continue;
            foreach ($availableLanguages as $availableValue)
            {
                $availableQuality = 1.0;
                foreach ($acceptedValues as $acceptedValue)
                {
                    if ($acceptedValue === '*')
                    {
                        $any = true;
                    }
                    $matchingGrade = self::_matchLanguage($acceptedValue, $availableValue);
                    if ($matchingGrade > 0)
                    {
                        $q = (string) ($acceptedQuality * $availableQuality * $matchingGrade);
                        if (!isset($matches[$q]))
                        {
                            $matches[$q] = array();
                        }
                        if (!in_array($availableValue, $matches[$q]))
                        {
                            $matches[$q][] = $availableValue;
                        }
                    }
                }
            }
        }
        if (count($matches) === 0 && $any)
        {
            if (count($availableLanguages) > 0)
            {
                $matches['1.0'] = $availableLanguages;
            }
        }
        if (count($matches) === 0)
        {
            return 'en';
        }
        krsort($matches);
        $topmatches = current($matches);
        return current($topmatches);
    }

    /**
     * compare two language IDs and return the degree they match
     *
     * From: http://stackoverflow.com/questions/3770513/detect-browser-language-in-php#3771447
     *
     * @param string $a
     * @param string $b
     * @return float
     */
    protected static function _matchLanguage($a, $b) {
        $a = explode('-', $a);
        $b = explode('-', $b);
        for ($i=0, $n=min(count($a), count($b)); $i<$n; $i++)
        {
            if ($a[$i] !== $b[$i]) break;
        }
        return $i === 0 ? 0 : (float) $i / count($a);
    }
}
