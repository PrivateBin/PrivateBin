<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\I18n;
use PrivateBin\View;

class ViewTest extends TestCase
{
    private static $error = 'foo bar';

    private static $status = '!*#@?$+';

    private static $is_deleted = false;

    private static $formatters = array(
        'plaintext'          => 'Plain Text',
        'syntaxhighlighting' => 'Source Code',
        'markdown'           => 'Markdown',
    );

    private static $formatter_default = 'plaintext';

    private static $expire = array(
        '5min'  => '5 minutes',
        '1hour' => '1 hour',
        'never' => 'Never',
    );

    private static $expire_default = '1hour';

    private static $version = 'Version 1.2.3';

    private $_content = array();

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
        $page->assign('ZEROBINCOMPATIBILITY', false);
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
        $page->assign('SRI', array());

        $dir = dir(PATH . 'tpl');
        while (false !== ($file = $dir->read())) {
            if (substr($file, -4) === '.php') {
                $template = substr($file, 0, -4);
                ob_start();
                $page->draw($template);
                $this->_content[$template] = ob_get_contents();
                ob_end_clean();
            }
        }
        // check bootstrap variants
        $template = 'bootstrap-page';
        ob_start();
        $page->draw($template);
        $this->_content[$template] = ob_get_contents();
        ob_end_clean();
        foreach (array('-dark', '-compact') as $suffix) {
            $template = 'bootstrap' . $suffix;
            ob_start();
            $page->draw($template);
            $this->_content[$template] = ob_get_contents();
            ob_end_clean();

            $template .= '-page';
            ob_start();
            $page->draw($template);
            $this->_content[$template] = ob_get_contents();
            ob_end_clean();
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
            if ($template === 'yourlsproxy') {
                // yourlsproxy template only displays error message
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

    public function testMissingTemplate()
    {
        $test = new View;
        $this->expectException(Exception::class);
        $this->expectExceptionCode(80);
        $test->draw('123456789 does not exist!');
    }
}
