<?php

use PrivateBin\I18n;
use PrivateBin\View;

class ViewTest extends PHPUnit_Framework_TestCase
{
    private static $error = 'foo bar';

    private static $status = '!*#@?$+';

    private static $formatters = array(
        'plaintext' => 'Plain Text',
        'syntaxhighlighting' => 'Source Code',
        'markdown' => 'Markdown',
    );

    private static $formatter_default = 'plaintext';

    private static $expire = array(
        '5min' => '5 minutes',
        '1hour' => '1 hour',
        'never' => 'Never',
    );

    private static $expire_default = '1hour';

    private static $version = 'Version 1.2.3';

    private $_content;

    public function setUp()
    {
        /* Setup Routine */
        $page = new View;
        $page->assign('CIPHERDATA', Helper::getPaste()['data']);
        $page->assign('ERROR', self::$error);
        $page->assign('STATUS', self::$status);
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
        $page->assign('NOTICE', 'example');
        $page->assign('LANGUAGESELECTION', '');
        $page->assign('LANGUAGES', I18n::getLanguageLabels(i18n::getAvailableLanguages()));
        $page->assign('EXPIRE', self::$expire);
        $page->assign('EXPIREDEFAULT', self::$expire_default);
        $page->assign('EXPIRECLONE', true);
        $page->assign('URLSHORTENER', '');
        ob_start();
        $page->draw('page');
        $this->_content = ob_get_contents();
        ob_end_clean();
    }

    public function tearDown()
    {
        /* Tear Down Routine */
    }

    public function testTemplateRendersCorrectly()
    {
        $this->assertContains(
            '<div id="cipherdata" class="hidden">' .
            htmlspecialchars(Helper::getPaste()['data'], ENT_NOQUOTES) .
            '</div>',
            $this->_content,
            'outputs data correctly'
        );
        $this->assertRegExp(
            '#<div[^>]+id="errormessage"[^>]*>.*' . self::$error . '</div>#',
            $this->_content,
            'outputs error correctly'
        );
        $this->assertRegExp(
            '#<[^>]+id="password"[^>]*>#',
            $this->_content,
            'password available if configured'
        );
        $this->assertRegExp(
            '#<input[^>]+id="opendiscussion"[^>]*checked="checked"[^>]*>#',
            $this->_content,
            'checked discussion if configured'
        );
        $this->assertRegExp(
            '#<[^>]+id="opendisc"[^>]*>#',
            $this->_content,
            'discussions available if configured'
        );
        // testing version number in JS address, since other instances may not be present in different templates
        $this->assertRegExp(
            '#<script[^>]+src="js/privatebin.js\\?' . rawurlencode(self::$version) . '"[^>]*>#',
            $this->_content,
            'outputs version correctly'
        );
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 80
     */
    public function testMissingTemplate()
    {
        $test = new View;
        $test->draw('123456789 does not exist!');
    }
}
