<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\TemplateSwitcher;


class TemplateSwitcherTest extends TestCase
{

    public function testSetTemplateFallback()
    {
        $defaultTemplateFallback = "bootstrap";
        $customTemplateFallback = "bootstrap-dark";
        $wrongTemplateFallback = "bootstrap-wrong";

        $this->assertEquals($defaultTemplateFallback, TemplateSwitcher::getTemplate(), "Default template fallback");

        TemplateSwitcher::setTemplateFallback($wrongTemplateFallback);
        $this->assertEquals($defaultTemplateFallback, TemplateSwitcher::getTemplate(), "Wrong template fallback");

        TemplateSwitcher::setTemplateFallback($customTemplateFallback);
        $this->assertEquals($customTemplateFallback, TemplateSwitcher::getTemplate(), "Custom template fallback");
    }


    public function testGetTemplate()
    {
        $defaultTemplateFallback = "bootstrap";
        $customTemplate = "bootstrap-dark";
        $customWrongTemplate = "bootstrap-wrong";

        TemplateSwitcher::setTemplateFallback($defaultTemplateFallback);

        $_COOKIE['template'] = $customWrongTemplate;
        $this->assertEquals($defaultTemplateFallback, TemplateSwitcher::getTemplate(), "Custom wrong template");

        $_COOKIE['template'] = $customTemplate;
        $this->assertEquals($customTemplate, TemplateSwitcher::getTemplate(), "Custom template");
    }


    public function testGetAvailableTemplates()
    {
        $this->assertNotEmpty(TemplateSwitcher::getAvailableTemplates(), "Available templates");
    }


    public function testIsTemplateAvailable()
    {
        $existingTemplate = "bootstrap";
        $nonExistentTemplate = "bootstrap-wrong";

        $this->assertTrue(TemplateSwitcher::isTemplateAvailable($existingTemplate), "Existing template");
        $this->assertFalse(TemplateSwitcher::isTemplateAvailable($nonExistentTemplate), "Non-existent template");
    }
}
