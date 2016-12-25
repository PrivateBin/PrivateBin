<?php

use PrivateBin\I18n;

class I18nTest extends PHPUnit_Framework_TestCase
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
        $messageId                       = 'It does not matter if the message ID exists';
        I18n::loadTranslations();
        $this->assertEquals($messageId, I18n::_($messageId), 'fallback to en');
    }

    public function testCookieLanguageDeDetection()
    {
        $_COOKIE['lang'] = 'de';
        I18n::loadTranslations();
        $this->assertEquals($this->_translations['en'], I18n::_('en'), 'browser language de');
        $this->assertEquals('0 Stunden', I18n::_('%d hours', 0), '0 hours in german');
        $this->assertEquals('1 Stunde',  I18n::_('%d hours', 1), '1 hour in german');
        $this->assertEquals('2 Stunden', I18n::_('%d hours', 2), '2 hours in french');
    }

    public function testBrowserLanguageDeDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-CH,de;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        I18n::loadTranslations();
        $this->assertEquals($this->_translations['en'], I18n::_('en'), 'browser language de');
        $this->assertEquals('0 Stunden', I18n::_('%d hours', 0), '0 hours in german');
        $this->assertEquals('1 Stunde',  I18n::_('%d hours', 1), '1 hour in german');
        $this->assertEquals('2 Stunden', I18n::_('%d hours', 2), '2 hours in french');
    }

    public function testBrowserLanguageFrDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-CH,fr;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        I18n::loadTranslations();
        $this->assertEquals('fr', I18n::_('en'), 'browser language fr');
        $this->assertEquals('0 heure',  I18n::_('%d hours', 0), '0 hours in french');
        $this->assertEquals('1 heure',  I18n::_('%d hours', 1), '1 hour in french');
        $this->assertEquals('2 heures', I18n::_('%d hours', 2), '2 hours in french');
    }

    public function testBrowserLanguagePlDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'pl;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        I18n::loadTranslations();
        $this->assertEquals('pl', I18n::_('en'), 'browser language pl');
        $this->assertEquals('1 godzina', I18n::_('%d hours', 1), '1 hour in polish');
        $this->assertEquals('2 godzina', I18n::_('%d hours', 2), '2 hours in polish');
        $this->assertEquals('12 godzinę', I18n::_('%d hours', 12), '12 hours in polish');
        $this->assertEquals('22 godzina', I18n::_('%d hours', 22), '22 hours in polish');
        $this->assertEquals('1 minut',  I18n::_('%d minutes', 1), '1 minute in polish');
        $this->assertEquals('3 minut',  I18n::_('%d minutes', 3), '3 minutes in polish');
        $this->assertEquals('13 minut',  I18n::_('%d minutes', 13), '13 minutes in polish');
        $this->assertEquals('23 minut',  I18n::_('%d minutes', 23), '23 minutes in polish');
    }

    public function testBrowserLanguageRuDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ru;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        I18n::loadTranslations();
        $this->assertEquals('ru', I18n::_('en'), 'browser language ru');
        $this->assertEquals('1 минуту',  I18n::_('%d minutes', 1), '1 minute in russian');
        $this->assertEquals('3 минуты',  I18n::_('%d minutes', 3), '3 minutes in russian');
        $this->assertEquals('10 минут',  I18n::_('%d minutes', 10), '10 minutes in russian');
        $this->assertEquals('21 минуту',  I18n::_('%d minutes', 21), '21 minutes in russian');
    }

    public function testBrowserLanguageAnyDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = '*';
        I18n::loadTranslations();
        $this->assertTrue(strlen(I18n::_('en')) == 2, 'browser language any');
    }

    public function testVariableInjection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'foobar';
        I18n::loadTranslations();
        $this->assertEquals('some string + 1', I18n::_('some %s + %d', 'string', 1), 'browser language en');
    }
}
