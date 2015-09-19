<?php
class i18nTest extends PHPUnit_Framework_TestCase
{
    private $_translations = array();

    public function setUp()
    {
        /* Setup Routine */
        $this->_translations = json_decode(
            file_get_contents(PATH . 'i18n' . DIRECTORY_SEPARATOR . 'de.json'),
            true
        );
    }

    public function tearDown()
    {
        /* Tear Down Routine */
    }

    public function testTranslationFallback()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'foobar';
        $messageId = 'It does not matter if the message ID exists';
        i18n::loadTranslations();
        $this->assertEquals($messageId, i18n::_($messageId), 'fallback to en');
    }

    public function testCookieLanguageDeDetection()
    {
        $_COOKIE['lang'] = 'de';
        i18n::loadTranslations();
        $this->assertEquals($this->_translations['en'], i18n::_('en'), 'browser language de');
        $this->assertEquals('0 Stunden', i18n::_('%d hours', 0), '0 hours in german');
        $this->assertEquals('1 Stunde',  i18n::_('%d hours', 1), '1 hour in german');
        $this->assertEquals('2 Stunden', i18n::_('%d hours', 2), '2 hours in french');
    }

    public function testBrowserLanguageDeDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-CH,de;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        i18n::loadTranslations();
        $this->assertEquals($this->_translations['en'], i18n::_('en'), 'browser language de');
        $this->assertEquals('0 Stunden', i18n::_('%d hours', 0), '0 hours in german');
        $this->assertEquals('1 Stunde',  i18n::_('%d hours', 1), '1 hour in german');
        $this->assertEquals('2 Stunden', i18n::_('%d hours', 2), '2 hours in french');
    }

    public function testBrowserLanguageFrDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-CH,fr;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        i18n::loadTranslations();
        $this->assertEquals('fr', i18n::_('en'), 'browser language fr');
        $this->assertEquals('0 heure',  i18n::_('%d hours', 0), '0 hours in french');
        $this->assertEquals('1 heure',  i18n::_('%d hours', 1), '1 hour in french');
        $this->assertEquals('2 heures', i18n::_('%d hours', 2), '2 hours in french');
    }

    public function testBrowserLanguagePlDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'pl;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        i18n::loadTranslations();
        $this->assertEquals('pl', i18n::_('en'), 'browser language pl');
        $this->assertEquals('2 godzina', i18n::_('%d hours', 2), 'hours in polish');
    }

    public function testBrowserLanguageAnyDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = '*';
        i18n::loadTranslations();
        $this->assertTrue(strlen(i18n::_('en')) == 2, 'browser language any');
    }

    public function testVariableInjection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'foobar';
        i18n::loadTranslations();
        $this->assertEquals('some string + 1', i18n::_('some %s + %d', 'string', 1), 'browser language en');
    }
}
