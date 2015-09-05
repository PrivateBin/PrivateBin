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

    public function testBrowserLanguageDetection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-CH,de;q=0.8,en-GB;q=0.6,en-US;q=0.4,en;q=0.2';
        i18n::loadTranslations();
        $this->assertEquals($this->_translations['en'], i18n::_('en'), 'browser language de');
    }

    public function testVariableInjection()
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'foobar';
        i18n::loadTranslations();
        $this->assertEquals('some string + 1', i18n::_('some %s + %d', 'string', 1), 'browser language de');
    }
}
