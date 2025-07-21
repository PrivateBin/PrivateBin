<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Configuration;
use PrivateBin\TemplateSwitcher;

class TemplateSwitcherTest extends TestCase
{
    public function testSetTemplateFallback()
    {
        $conf = new Configuration;

        $defaultTemplateFallback        = 'bootstrap5';
        $existingTemplateFallback       = 'bootstrap-dark';
        $wrongBootstrapTemplateFallback = 'bootstrap-wrong';
        $wrongTemplateFallback          = 'wrong-template';

        TemplateSwitcher::setAvailableTemplates($conf->getKey('availabletemplates'));

        TemplateSwitcher::setTemplateFallback($wrongBootstrapTemplateFallback);
        $this->assertEquals($defaultTemplateFallback, TemplateSwitcher::getTemplate(), 'Wrong bootstrap template fallback');

        TemplateSwitcher::setTemplateFallback($wrongTemplateFallback);
        $this->assertEquals($defaultTemplateFallback, TemplateSwitcher::getTemplate(), 'Wrong template fallback');

        TemplateSwitcher::setTemplateFallback($existingTemplateFallback);
        $this->assertEquals($existingTemplateFallback, TemplateSwitcher::getTemplate(), 'Correct template fallback');
    }

    public function testSetAvailableTemplates()
    {
        $conf               = new Configuration;
        $availableTemplates = $conf->getKey('availabletemplates');

        TemplateSwitcher::setAvailableTemplates($availableTemplates);
        $this->assertEquals($availableTemplates, TemplateSwitcher::getAvailableTemplates(), 'Set available templates');
    }

    public function testGetTemplate()
    {
        $defaultTemplateFallback = 'bootstrap5';
        $customTemplate          = 'bootstrap-dark';
        $customWrongTemplate     = 'bootstrap-wrong';

        TemplateSwitcher::setTemplateFallback($defaultTemplateFallback);

        $_COOKIE['template'] = $customWrongTemplate;
        $this->assertEquals($defaultTemplateFallback, TemplateSwitcher::getTemplate(), 'Custom wrong template');

        $_COOKIE['template'] = $customTemplate;
        $this->assertEquals($customTemplate, TemplateSwitcher::getTemplate(), 'Custom template');
    }

    public function testGetAvailableTemplates()
    {
        $this->assertNotEmpty(TemplateSwitcher::getAvailableTemplates(), 'Get available templates');
    }

    public function testIsTemplateAvailable()
    {
        $conf = new Configuration;

        $existingTemplate    = 'bootstrap';
        $nonExistentTemplate = 'bootstrap-wrong';

        TemplateSwitcher::setAvailableTemplates($conf->getKey('availabletemplates'));

        $this->assertTrue(TemplateSwitcher::isTemplateAvailable($existingTemplate), 'Existing template');
        $this->assertFalse(TemplateSwitcher::isTemplateAvailable($nonExistentTemplate), 'Non-existent template');
    }
}
