<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\I18n;

class I18nMock extends I18n
{
    public static function resetAvailableLanguages()
    {
        self::$_availableLanguages = array();
    }

    public static function resetPath($path = '')
    {
        self::$_path = $path;
    }

    public static function getPath($file = '')
    {
        return self::_getPath($file);
    }
}

class I18nTest extends TestCase
{
    private $_translations = array();

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_translations = json_decode(
            file_get_contents(PATH . 'i18n' . DIRECTORY_SEPARATOR . 'de.json'),
            true
        );
    }

    public function tearDown(): void
    {
        unset($_COOKIE['lang'], $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    }

    public function testTranslationFallback()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'foobar';
        $messageId                       = 'It does not matter if the message ID exists';
        I18n::loadTranslations();
        $this->assertEquals($messageId, I18n::_($messageId), 'fallback to en');
        I18n::getLanguageLabels();
    }

    public function testCookieLanguageDeDetection()
    {
        $_COOKIE['lang'] = 'de';
        I18n::loadTranslations();
        $this->assertEquals($_COOKIE['lang'], I18n::getLanguage(), 'browser language de');
        $this->assertEquals('0 Stunden', I18n::_('%d hours', 0), '0 hours in German');
        $this->assertEquals('1 Stunde',  I18n::_('%d hours', 1), '1 hour in German');
        $this->assertEquals('2 Stunden', I18n::_('%d hours', 2), '2 hours in German');
    }

    public function testBrowserLanguageDeDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-CH,de;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2,fr;q=0.0';
        I18n::loadTranslations();
        $this->assertEquals('de', I18n::getLanguage(), 'browser language de');
        $this->assertEquals('0 Stunden', I18n::_('%d hours', 0), '0 hours in German');
        $this->assertEquals('1 Stunde',  I18n::_('%d hours', 1), '1 hour in German');
        $this->assertEquals('2 Stunden', I18n::_('%d hours', 2), '2 hours in German');
    }

    public function testBrowserLanguageFrDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-CH,fr;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2,de;q=0.0';
        I18n::loadTranslations();
        $this->assertEquals('fr', I18n::getLanguage(), 'browser language fr');
        $this->assertEquals('0 heure',  I18n::_('%d hours', 0), '0 hours in French');
        $this->assertEquals('1 heure',  I18n::_('%d hours', 1), '1 hour in French');
        $this->assertEquals('2 heures', I18n::_('%d hours', 2), '2 hours in French');
    }

    public function testBrowserLanguageNoDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'no;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        I18n::loadTranslations();
        $this->assertEquals('no', I18n::getLanguage(), 'browser language no');
        $this->assertEquals('0 timer',  I18n::_('%d hours', 0), '0 hours in Norwegian');
        $this->assertEquals('1 time',  I18n::_('%d hours', 1), '1 hour in Norwegian');
        $this->assertEquals('2 timer', I18n::_('%d hours', 2), '2 hours in Norwegian');
    }

    public function testBrowserLanguageOcDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'oc;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        I18n::loadTranslations();
        $this->assertEquals('oc', I18n::getLanguage(), 'browser language oc');
        $this->assertEquals('0 ora',  I18n::_('%d hours', 0), '0 hours in Occitan');
        $this->assertEquals('1 ora',  I18n::_('%d hours', 1), '1 hour in Occitan');
        $this->assertEquals('2 oras', I18n::_('%d hours', 2), '2 hours in Occitan');
    }

    public function testBrowserLanguageZhDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'zh;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        I18n::loadTranslations();
        $this->assertEquals('zh', I18n::getLanguage(), 'browser language zh');
        $this->assertEquals('0 小时',  I18n::_('%d hours', 0), '0 hours in Chinese');
        $this->assertEquals('1 小时',  I18n::_('%d hours', 1), '1 hour in Chinese');
        $this->assertEquals('2 小时', I18n::_('%d hours', 2), '2 hours in Chinese');
    }

    public function testBrowserLanguagePlDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'pl;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        I18n::loadTranslations();
        $this->assertEquals('pl', I18n::getLanguage(), 'browser language pl');
        $this->assertEquals('1 godzina', I18n::_('%d hours', 1), '1 hour in Polish');
        $this->assertEquals('2 godzina', I18n::_('%d hours', 2), '2 hours in Polish');
        $this->assertEquals('12 godzinę', I18n::_('%d hours', 12), '12 hours in Polish');
        $this->assertEquals('22 godzina', I18n::_('%d hours', 22), '22 hours in Polish');
        $this->assertEquals('1 minut',  I18n::_('%d minutes', 1), '1 minute in Polish');
        $this->assertEquals('3 minut',  I18n::_('%d minutes', 3), '3 minutes in Polish');
        $this->assertEquals('13 minut',  I18n::_('%d minutes', 13), '13 minutes in Polish');
        $this->assertEquals('23 minut',  I18n::_('%d minutes', 23), '23 minutes in Polish');
    }

    public function testBrowserLanguageRuDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        I18n::loadTranslations();
        $this->assertEquals('ru', I18n::getLanguage(), 'browser language ru');
        $this->assertEquals('1 минуту',  I18n::_('%d minutes', 1), '1 minute in Russian');
        $this->assertEquals('3 минуты',  I18n::_('%d minutes', 3), '3 minutes in Russian');
        $this->assertEquals('10 минут',  I18n::_('%d minutes', 10), '10 minutes in Russian');
        $this->assertEquals('21 минуту',  I18n::_('%d minutes', 21), '21 minutes in Russian');
    }

    public function testBrowserLanguageSlDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'sl;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        I18n::loadTranslations();
        $this->assertEquals('sl', I18n::getLanguage(), 'browser language sl');
        $this->assertEquals('0 ura',  I18n::_('%d hours', 0), '0 hours in Slowene');
        $this->assertEquals('1 uri',  I18n::_('%d hours', 1), '1 hour in Slowene');
        $this->assertEquals('2 ure', I18n::_('%d hours', 2), '2 hours in Slowene');
        $this->assertEquals('3 ur',  I18n::_('%d hours', 3), '3 hours in Slowene');
        $this->assertEquals('11 ura',  I18n::_('%d hours', 11), '11 hours in Slowene');
        $this->assertEquals('101 uri',  I18n::_('%d hours', 101), '101 hours in Slowene');
        $this->assertEquals('102 ure', I18n::_('%d hours', 102), '102 hours in Slowene');
        $this->assertEquals('104 ur',  I18n::_('%d hours', 104), '104 hours in Slowene');
    }

    public function testBrowserLanguageCsDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'cs;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        I18n::loadTranslations();
        $this->assertEquals('cs', I18n::getLanguage(), 'browser language cs');
        $this->assertEquals('1 hodina', I18n::_('%d hours', 1), '1 hour in Czech');
        $this->assertEquals('2 hodiny', I18n::_('%d hours', 2), '2 hours in Czech');
        $this->assertEquals('5 minut',  I18n::_('%d minutes', 5), '5 minutes in Czech');
        $this->assertEquals('14 minut',  I18n::_('%d minutes', 14), '14 minutes in Czech');
    }

    public function testBrowserLanguageAnyDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = '*';
        I18n::loadTranslations();
        $this->assertTrue(strlen(I18n::getLanguage()) >= 2, 'browser language any');
    }

    public function testVariableInjection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'foobar';
        I18n::loadTranslations();
        $this->assertEquals('some string + 1', I18n::_('some %s + %d', 'string', 1), 'browser language en');
    }

    public function testHtmlEntityEncoding()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'foobar';
        I18n::loadTranslations();
        $input  = '&<>"\'/`=';
        $result = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5 | ENT_DISALLOWED, 'UTF-8', false);
        $this->assertEquals($result, I18n::encode($input), 'encodes HTML entities');
        $this->assertEquals('<a>some ' . $result . ' + 1</a>', I18n::_('<a>some %s + %d</a>', $input, 1), 'encodes parameters in translations');
        $this->assertEquals($result . $result, I18n::_($input . '%s', $input), 'encodes message ID as well, when no link');
    }

    public function testFallbackAlwaysPresent()
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_i18n';
        if (!is_dir($path)) {
            mkdir($path);
        }

        $languageIterator = new AppendIterator();
        $languageIterator->append(new GlobIterator(I18nMock::getPath('??.json')));
        $languageIterator->append(new GlobIterator(I18nMock::getPath('???.json'))); // for jbo
        $languageCount = 0;
        foreach ($languageIterator as $file) {
            ++$languageCount;
            $this->assertTrue(copy($file->getPathname(), $path . DIRECTORY_SEPARATOR . $file->getBasename()));
        }

        I18nMock::resetPath($path);
        $languagesDevelopment = I18nMock::getAvailableLanguages();
        $this->assertEquals($languageCount, count($languagesDevelopment), 'all copied languages detected');
        $this->assertTrue(in_array('en', $languagesDevelopment), 'English fallback present');

        unlink($path . DIRECTORY_SEPARATOR . 'en.json');
        I18nMock::resetAvailableLanguages();
        $languagesDeployed = I18nMock::getAvailableLanguages();
        $this->assertEquals($languageCount, count($languagesDeployed), 'all copied languages detected, plus fallback');
        $this->assertTrue(in_array('en', $languagesDeployed), 'English fallback still present');

        I18nMock::resetAvailableLanguages();
        I18nMock::resetPath();
        Helper::rmDir($path);
    }

    public function testMessageIdsExistInAllLanguages()
    {
        $messageIds = array();
        $languages  = array();
        $dir        = dir(PATH . 'i18n');
        while (false !== ($file = $dir->read())) {
            if (strlen($file) === 7) {
                $language            = substr($file, 0, 2);
                $languageMessageIds  = array_keys(
                    json_decode(
                        file_get_contents(PATH . 'i18n' . DIRECTORY_SEPARATOR . $file),
                        true
                    )
                );
                $messageIds           = array_unique(array_merge($messageIds, $languageMessageIds));
                $languages[$language] = $languageMessageIds;
            }
        }
        foreach ($messageIds as $messageId) {
            foreach (array_keys($languages) as $language) {
                // most languages don't translate the data size units, ignore those
                if ($messageId !== 'B' && strlen($messageId) !== 3 && strpos($messageId, 'B', 2) !== 2) {
                    $this->assertContains(
                        $messageId,
                        $languages[$language],
                        "message ID '$messageId' exists in translation file $language.json"
                    );
                }
            }
        }
    }
}
