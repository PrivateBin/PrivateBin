<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\I18n;
use PrivateBin\View;

class ViewTest extends TestCase
{
    private static $error = 'foo bar';

    private static $status = '!*#@?$+';

    private static $is_deleted = false;

    private static $formatters = [
        'plaintext'          => 'Plain Text',
        'syntaxhighlighting' => 'Source Code',
        'markdown'           => 'Markdown',
    ];

    private static $formatter_default = 'plaintext';

    private static $expire = [
        '5min'  => '5 minutes',
        '1hour' => '1 hour',
        'never' => 'Never',
    ];

    private static $expire_default = '1hour';

    private static $version = 'Version 1.2.3';

    private $_content = [];

    private $_page;

    public function setUp(): void
    {
        /* Setup Routine */
        $page = new View;
        $page->assign('NAME', 'PrivateBinTest');
        $page->assign('BASEPATH', '');
        $page->assign('ERROR', self::$error);
        $page->assign('STATUS', self::$status);
        $page->assign('ISDELETED', self::$is_deleted);
        $page->assign('VERSION', self::$version);
        $page->assign('DISCUSSION', true);
        $page->assign('OPENDISCUSSION', true);
        $page->assign('MARKDOWN', true);
        $page->assign('SYNTAXHIGHLIGHTING', true);
        $page->assign('SYNTAXHIGHLIGHTINGTHEME', 'sons-of-obsidian');
        $page->assign('FORMATTER', self::$formatters);
        $page->assign('FORMATTERDEFAULT', self::$formatter_default);
        $page->assign('BURNAFTERREADINGSELECTED', false);
        $page->assign('PASSWORD', true);
        $page->assign('FILEUPLOAD', false);
        $page->assign('INFO', 'example');
        $page->assign('NOTICE', 'example');
        $page->assign('LANGUAGESELECTION', '');
        $page->assign('LANGUAGES', I18n::getLanguageLabels(I18n::getAvailableLanguages()));
        $page->assign('EXPIRE', self::$expire);
        $page->assign('EXPIREDEFAULT', self::$expire_default);
        $page->assign('URLSHORTENER', '');
        $page->assign('QRCODE', true);
        $page->assign('EMAIL', true);
        $page->assign('HTTPWARNING', true);
        $page->assign('HTTPSLINK', 'https://example.com/');
        $page->assign('COMPRESSION', 'zlib');
        $page->assign('CSPHEADER', 'default-src \'none\'');
        $page->assign('SRI', []);
        $this->_page = $page;

        foreach (new DirectoryIterator(PATH . 'tpl') as $file) {
            if ($file->getExtension() === 'php') {
                $template = $file->getBasename('.php');
                ob_start();
                $page->draw($template);
                $this->_content[$template] = ob_get_contents();
                ob_end_clean();
            }
        }
    }

    public function testTemplateRendersCorrectly()
    {
        foreach ($this->_content as $template => $content) {
            $this->assertMatchesRegularExpression(
                '#<div[^>]+id="errormessage"[^>]*>.*' . self::$error . '#s',
                $content,
                $template . ': outputs error correctly'
            );
            if ($template === 'shortenerproxy') {
                // shortenerproxy template only displays error message
                continue;
            }
            $this->assertMatchesRegularExpression(
                '#<[^>]+id="password"[^>]*>#',
                $content,
                $template . ': password available if configured'
            );
            $this->assertMatchesRegularExpression(
                '#<input[^>]+id="opendiscussion"[^>]*checked="checked"[^>]*>#',
                $content,
                $template . ': checked discussion if configured'
            );
            $this->assertMatchesRegularExpression(
                '#<[^>]+id="opendiscussionoption"[^>]*>#',
                $content,
                $template . ': discussions available if configured'
            );
            // testing version number in JS address, since other instances may not be present in different templates
            $this->assertMatchesRegularExpression(
                '#<script[^>]+src="js/privatebin.js\\?' . rawurlencode(self::$version) . '"[^>]*>#',
                $content,
                $template . ': outputs version correctly'
            );
        }
    }

    public function testBootstrap5RetryButtonStartsHiddenInItsListItem()
    {
        $this->assertArrayHasKey('bootstrap5', $this->_content);
        $this->assertMatchesRegularExpression(
            '#<li class="nav-item d-flex flex-lg-row flex-column hidden">\s*'
            . '<button id="retrybutton" type="button" class="reloadlink hidden\b#',
            $this->_content['bootstrap5'],
            'bootstrap5: retry button stays inside an initially hidden list item'
        );
    }

    private function _renderModern(array $options): string
    {
        $this->_page->assign('TEMPLATEOPTIONS', $options);
        ob_start();
        $this->_page->draw('modern');
        return ob_get_clean();
    }

    public function testModernTemplateDefaultOptions()
    {
        $content = $this->_renderModern([]);
        $this->assertStringContainsString('<img alt="" src="img/icon.svg" height="28" />', $content, 'uses default logo');
        $this->assertStringNotContainsString('pb-logo-dark', $content, 'no dark logo by default');
        $this->assertStringNotContainsString('data-accent', $content, 'no accent by default');
    }

    public function testModernTemplateOptions()
    {
        $content = $this->_renderModern([
            'logo'      => 'img/custom/logo.svg',
            'logo_dark' => 'img/custom/logo-dark.svg',
            'accent'    => 'blue',
        ]);
        $this->assertMatchesRegularExpression('#<html[^>]+data-accent="blue"#', $content, 'sets accent');
        $this->assertStringContainsString('src="img/custom/logo.svg" height="28" class="pb-logo-light"', $content, 'uses configured logo');
        $this->assertStringContainsString('src="img/custom/logo-dark.svg" height="28" class="pb-logo-dark"', $content, 'uses configured dark logo');
        $this->assertStringNotContainsString('img/icon.svg', $content, 'replaces default logo');
    }

    public function testModernTemplateEncodesOptions()
    {
        $content = $this->_renderModern([
            'logo'   => '"><script>alert(1)</script>',
            'accent' => '"><script>alert(2)</script>',
        ]);
        $this->assertStringNotContainsString('<script>alert', $content, 'encodes option values');
    }

    public function testMissingTemplate()
    {
        $test = new View;
        $this->expectException(Exception::class);
        $this->expectExceptionCode(80);
        $test->draw('123456789 does not exist!');
    }

    public function testInvalidTemplate()
    {
        $test = new View;
        $this->expectException(Exception::class);
        $this->expectExceptionCode(81);
        $test->draw('../index');
    }
}
