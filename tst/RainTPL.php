<?php
class RainTPLTest extends PHPUnit_Framework_TestCase
{
    private static $error = 'foo bar';

    private static $status = '!*#@?$+';

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
        $page = new RainTPL;
        $page::configure(array('cache_dir' => 'tmp/'));
        $page::$path_replace = false;

        // We escape it here because ENT_NOQUOTES can't be used in RainTPL templates.
        $page->assign('CIPHERDATA', htmlspecialchars(helper::getPaste()['data'], ENT_NOQUOTES));
        $page->assign('ERROR', self::$error);
        $page->assign('STATUS', self::$status);
        $page->assign('VERSION', self::$version);
        $page->assign('DISCUSSION', true);
        $page->assign('OPENDISCUSSION', true);
        $page->assign('MARKDOWN', true);
        $page->assign('SYNTAXHIGHLIGHTING', true);
        $page->assign('SYNTAXHIGHLIGHTINGTHEME', 'sons-of-obsidian');
        $page->assign('BURNAFTERREADINGSELECTED', false);
        $page->assign('PASSWORD', true);
        $page->assign('FILEUPLOAD', false);
        $page->assign('BASE64JSVERSION', '2.1.9');
        $page->assign('NOTICE', 'example');
        $page->assign('LANGUAGESELECTION', '');
        $page->assign('LANGUAGES', i18n::getLanguageLabels(i18n::getAvailableLanguages()));
        $page->assign('EXPIRE', self::$expire);
        $page->assign('EXPIREDEFAULT', self::$expire_default);
        $page->assign('EXPIRECLONE', true);
        $page->assign('URLSHORTENER', '');
        ob_start();
        $page->draw('page');
        $this->_content = ob_get_contents();
        // run a second time from cache
        $page->cache('page');
        $page->draw('page');
        ob_end_clean();
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        helper::rmdir(PATH . 'tmp');
    }

    public function testTemplateRendersCorrectly()
    {
        $this->assertContains(
            '<div id="cipherdata" class="hidden">' .
            htmlspecialchars(helper::getPaste()['data'], ENT_NOQUOTES) .
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
     * @expectedException RainTpl_Exception
     */
    public function testMissingTemplate()
    {
        $test = new RainTPL;
        $test->draw('123456789 does not exist!');
    }
}
