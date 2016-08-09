<?php

use PrivateBin\Data\Filesystem;
use PrivateBin\PrivateBin;
use PrivateBin\Persistence\ServerSalt;
use PrivateBin\Persistence\TrafficLimiter;

class PrivateBinTest extends PHPUnit_Framework_TestCase
{
    protected $_model;

    protected $_path;

    public function setUp()
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        $this->_model = Filesystem::getInstance(array('dir' => $this->_path));
        ServerSalt::setPath($this->_path);
        $this->reset();
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        Helper::confRestore();
        Helper::rmDir($this->_path);
    }

    public function reset()
    {
        $_POST = array();
        $_GET = array();
        $_SERVER = array();
        if ($this->_model->exists(Helper::getPasteId())) {
            $this->_model->delete(Helper::getPasteId());
        }
        Helper::confRestore();
        $options = parse_ini_file(CONF, true);
        $options['purge']['dir'] = $this->_path;
        $options['traffic']['dir'] = $this->_path;
        $options['model_options']['dir'] = $this->_path;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
    }

    /**
     * @runInSeparateProcess
     */
    public function testView()
    {
        $this->reset();
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertContains(
            '<title>PrivateBin</title>',
            $content,
            'outputs title correctly'
        );
        $this->assertNotContains(
            'id="shortenbutton"',
            $content,
            'doesn\'t output shortener button'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testViewLanguageSelection()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['main']['languageselection'] = true;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_COOKIE['lang'] = 'de';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertContains(
            '<title>PrivateBin</title>',
            $content,
            'outputs title correctly'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testViewForceLanguageDefault()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['main']['languageselection'] = false;
        $options['main']['languagedefault'] = 'fr';
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_COOKIE['lang'] = 'de';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertContains(
            '<title>PrivateBin</title>',
            $content,
            'outputs title correctly'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testViewUrlShortener()
    {
        $shortener = 'https://shortener.example.com/api?link=';
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['main']['urlshortener'] = $shortener;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_COOKIE['lang'] = 'de';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertRegExp(
            '#id="shortenbutton"[^>]*data-shortener="' . preg_quote($shortener) . '"#',
            $content,
            'outputs configured shortener URL correctly'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testHtaccess()
    {
        $this->reset();
        $dirs = array('cfg', 'lib');
        foreach ($dirs as $dir) {
            $file = PATH . $dir . DIRECTORY_SEPARATOR . '.htaccess';
            @unlink($file);
        }
        ob_start();
        new PrivateBin;
        ob_end_clean();
        foreach ($dirs as $dir) {
            $file = PATH . $dir . DIRECTORY_SEPARATOR . '.htaccess';
            $this->assertFileExists(
                $file,
                "$dir htaccess recreated"
            );
        }
    }

    /**
     * @expectedException Exception
     * @expectedExceptionCode 2
     */
    public function testConf()
    {
        $this->reset();
        Helper::confBackup();
        file_put_contents(CONF, '');
        new PrivateBin;
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreate()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPaste();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $paste = $this->_model->read($response['id']);
        $this->assertEquals(
            hash_hmac('sha256', $response['id'], $paste->meta->salt),
            $response['deletetoken'],
            'outputs valid delete token'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidTimelimit()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPaste(array('expire' => 25));
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        TrafficLimiter::canPass();
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $paste = $this->_model->read($response['id']);
        $this->assertEquals(
            hash_hmac('sha256', $response['id'], $paste->meta->salt),
            $response['deletetoken'],
            'outputs valid delete token'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidSize()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['main']['sizelimit'] = 10;
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPaste();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateProxyHeader()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['header'] = 'X_FORWARDED_FOR';
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPaste();
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '::2';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $paste = $this->_model->read($response['id']);
        $this->assertEquals(
            hash_hmac('sha256', $response['id'], $paste->meta->salt),
            $response['deletetoken'],
            'outputs valid delete token'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateDuplicateId()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        $_POST = Helper::getPaste();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateValidExpire()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPaste();
        $_POST['expire'] = '5min';
        $_POST['formatter'] = 'foo';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $time = time();
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $paste = $this->_model->read($response['id']);
        $this->assertEquals(
            hash_hmac('sha256', $response['id'], $paste->meta->salt),
            $response['deletetoken'],
            'outputs valid delete token'
        );
        $this->assertGreaterThanOrEqual($time + 300, $paste->meta->expire_date, 'time is set correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateValidExpireWithDiscussion()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPaste();
        $_POST['expire'] = '5min';
        $_POST['opendiscussion'] = '1';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $time = time();
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $paste = $this->_model->read($response['id']);
        $this->assertEquals(
            hash_hmac('sha256', $response['id'], $paste->meta->salt),
            $response['deletetoken'],
            'outputs valid delete token'
        );
        $this->assertGreaterThanOrEqual($time + 300, $paste->meta->expire_date, 'time is set correctly');
        $this->assertEquals(1, $paste->meta->opendiscussion, 'discussion is enabled');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidExpire()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPaste();
        $_POST['expire'] = 'foo';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $paste = $this->_model->read($response['id']);
        $this->assertEquals(
            hash_hmac('sha256', $response['id'], $paste->meta->salt),
            $response['deletetoken'],
            'outputs valid delete token'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidBurn()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPaste();
        $_POST['burnafterreading'] = 'neither 1 nor 0';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidOpenDiscussion()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPaste();
        $_POST['opendiscussion'] = 'neither 1 nor 0';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateAttachment()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        $options['main']['fileupload'] = true;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPasteWithAttachment();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not exists before posting data');
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $original = json_decode(json_encode($_POST));
        $stored = $this->_model->read($response['id']);
        foreach (array('data', 'attachment', 'attachmentname') as $key) {
            $this->assertEquals($original->$key, $stored->$key);
        }
        $this->assertEquals(
            hash_hmac('sha256', $response['id'], $stored->meta->salt),
            $response['deletetoken'],
            'outputs valid delete token'
        );
    }

    /**
     * In some webserver setups (found with Suhosin) overly long POST params are
     * silently removed, check that this case is handled
     *
     * @runInSeparateProcess
     */
    public function testCreateBrokenAttachmentUpload()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        $options['main']['fileupload'] = true;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPasteWithAttachment();
        unset($_POST['attachment']);
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not exists before posting data');
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateTooSoon()
    {
        $this->reset();
        $_POST = Helper::getPaste();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new PrivateBin;
        ob_end_clean();
        $this->_model->delete(Helper::getPasteId());
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateValidNick()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getPaste();
        $_POST['nickname'] = Helper::getComment()['meta']['nickname'];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $paste = $this->_model->read($response['id']);
        $this->assertEquals(
            hash_hmac('sha256', $response['id'], $paste->meta->salt),
            $response['deletetoken'],
            'outputs valid delete token'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidNick()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getCommentPost();
        $_POST['pasteid'] = Helper::getPasteId();
        $_POST['parentid'] = Helper::getPasteId();
        $_POST['nickname'] = 'foo';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateComment()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getCommentPost();
        $_POST['pasteid'] = Helper::getPasteId();
        $_POST['parentid'] = Helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), $response['id']), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidComment()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getCommentPost();
        $_POST['pasteid'] = Helper::getPasteId();
        $_POST['parentid'] = 'foo';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateCommentDiscussionDisabled()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getCommentPost();
        $_POST['pasteid'] = Helper::getPasteId();
        $_POST['parentid'] = Helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $paste = Helper::getPaste(array('opendiscussion' => false));
        $this->_model->create(Helper::getPasteId(), $paste);
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateCommentInvalidPaste()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $_POST = Helper::getCommentPost();
        $_POST['pasteid'] = Helper::getPasteId();
        $_POST['parentid'] = Helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateDuplicateComment()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        $this->_model->createComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId(), Helper::getComment());
        $this->assertTrue($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'comment exists before posting data');
        $_POST = Helper::getCommentPost();
        $_POST['pasteid'] = Helper::getPasteId();
        $_POST['parentid'] = Helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertTrue($this->_model->existsComment(Helper::getPasteId(), Helper::getPasteId(), Helper::getCommentId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testRead()
    {
        $this->reset();
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        $_SERVER['QUERY_STRING'] = Helper::getPasteId();
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertContains(
            '<div id="cipherdata" class="hidden">' .
            htmlspecialchars(Helper::getPasteAsJson(), ENT_NOQUOTES) .
            '</div>',
            $content,
            'outputs data correctly'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadInvalidId()
    {
        $this->reset();
        $_SERVER['QUERY_STRING'] = 'foo';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertRegExp(
            '#<div[^>]*id="errormessage"[^>]*>.*Invalid paste ID\.</div>#',
            $content,
            'outputs error correctly'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadNonexisting()
    {
        $this->reset();
        $_SERVER['QUERY_STRING'] = Helper::getPasteId();
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertRegExp(
            '#<div[^>]*id="errormessage"[^>]*>.*Paste does not exist[^<]*</div>#',
            $content,
            'outputs error correctly'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadExpired()
    {
        $this->reset();
        $expiredPaste = Helper::getPaste(array('expire_date' => 1344803344));
        $this->_model->create(Helper::getPasteId(), $expiredPaste);
        $_SERVER['QUERY_STRING'] = Helper::getPasteId();
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertRegExp(
            '#<div[^>]*id="errormessage"[^>]*>.*Paste does not exist[^<]*</div>#',
            $content,
            'outputs error correctly'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadBurn()
    {
        $this->reset();
        $burnPaste = Helper::getPaste(array('burnafterreading' => true));
        $this->_model->create(Helper::getPasteId(), $burnPaste);
        $_SERVER['QUERY_STRING'] = Helper::getPasteId();
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        unset($burnPaste['meta']['salt']);
        $this->assertContains(
            '<div id="cipherdata" class="hidden">' .
            htmlspecialchars(Helper::getPasteAsJson($burnPaste['meta']), ENT_NOQUOTES) .
            '</div>',
            $content,
            'outputs data correctly'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadJson()
    {
        $this->reset();
        $paste = Helper::getPaste();
        $this->_model->create(Helper::getPasteId(), $paste);
        $_SERVER['QUERY_STRING'] = Helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs success status');
        $this->assertEquals(Helper::getPasteId(), $response['id'], 'outputs data correctly');
        $this->assertStringEndsWith('?' . $response['id'], $response['url'], 'returned URL points to new paste');
        $this->assertEquals($paste['data'], $response['data'], 'outputs data correctly');
        $this->assertEquals($paste['meta']['formatter'], $response['meta']['formatter'], 'outputs format correctly');
        $this->assertEquals($paste['meta']['postdate'], $response['meta']['postdate'], 'outputs postdate correctly');
        $this->assertEquals($paste['meta']['opendiscussion'], $response['meta']['opendiscussion'], 'outputs opendiscussion correctly');
        $this->assertEquals(0, $response['comment_count'], 'outputs comment_count correctly');
        $this->assertEquals(0, $response['comment_offset'], 'outputs comment_offset correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadInvalidJson()
    {
        $this->reset();
        $_SERVER['QUERY_STRING'] = Helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadOldSyntax()
    {
        $this->reset();
        $oldPaste = Helper::getPaste();
        $meta = array(
            'syntaxcoloring' => true,
            'postdate' => $oldPaste['meta']['postdate'],
            'opendiscussion' => $oldPaste['meta']['opendiscussion'],
        );
        $oldPaste['meta'] = $meta;
        $this->_model->create(Helper::getPasteId(), $oldPaste);
        $_SERVER['QUERY_STRING'] = Helper::getPasteId();
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $meta['formatter'] = 'syntaxhighlighting';
        $this->assertContains(
            '<div id="cipherdata" class="hidden">' .
            htmlspecialchars(Helper::getPasteAsJson($meta), ENT_NOQUOTES) .
            '</div>',
            $content,
            'outputs data correctly'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadOldFormat()
    {
        $this->reset();
        $oldPaste = Helper::getPaste();
        unset($oldPaste['meta']['formatter']);
        $this->_model->create(Helper::getPasteId(), $oldPaste);
        $_SERVER['QUERY_STRING'] = Helper::getPasteId();
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $oldPaste['meta']['formatter'] = 'plaintext';
        unset($oldPaste['meta']['salt']);
        $this->assertContains(
            '<div id="cipherdata" class="hidden">' .
            htmlspecialchars(Helper::getPasteAsJson($oldPaste['meta']), ENT_NOQUOTES) .
            '</div>',
            $content,
            'outputs data correctly'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testDelete()
    {
        $this->reset();
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists before deleting data');
        $paste = $this->_model->read(Helper::getPasteId());
        $_GET['pasteid'] = Helper::getPasteId();
        $_GET['deletetoken'] = hash_hmac('sha256', Helper::getPasteId(), $paste->meta->salt);
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertRegExp(
            '#<div[^>]*id="status"[^>]*>.*Paste was properly deleted[^<]*</div>#s',
            $content,
            'outputs deleted status correctly'
        );
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste successfully deleted');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteInvalidId()
    {
        $this->reset();
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        $_GET['pasteid'] = 'foo';
        $_GET['deletetoken'] = 'bar';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertRegExp(
            '#<div[^>]*id="errormessage"[^>]*>.*Invalid paste ID\.</div>#',
            $content,
            'outputs delete error correctly'
        );
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after failing to delete data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteInexistantId()
    {
        $this->reset();
        $_GET['pasteid'] = Helper::getPasteId();
        $_GET['deletetoken'] = 'bar';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertRegExp(
            '#<div[^>]*id="errormessage"[^>]*>.*Paste does not exist[^<]*</div>#',
            $content,
            'outputs delete error correctly'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteInvalidToken()
    {
        $this->reset();
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        $_GET['pasteid'] = Helper::getPasteId();
        $_GET['deletetoken'] = 'bar';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertRegExp(
            '#<div[^>]*id="errormessage"[^>]*>.*Wrong deletion token[^<]*</div>#',
            $content,
            'outputs delete error correctly'
        );
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists after failing to delete data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteBurnAfterReading()
    {
        $this->reset();
        $burnPaste = Helper::getPaste(array('burnafterreading' => true));
        $this->_model->create(Helper::getPasteId(), $burnPaste);
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists before deleting data');
        $_POST['deletetoken'] = 'burnafterreading';
        $_SERVER['QUERY_STRING'] = Helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste successfully deleted');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteInvalidBurnAfterReading()
    {
        $this->reset();
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists before deleting data');
        $_POST['deletetoken'] = 'burnafterreading';
        $_SERVER['QUERY_STRING'] = Helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste successfully deleted');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteExpired()
    {
        $this->reset();
        $expiredPaste = Helper::getPaste(array('expire_date' => 1000));
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste does not exist before being created');
        $this->_model->create(Helper::getPasteId(), $expiredPaste);
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists before deleting data');
        $_GET['pasteid'] = Helper::getPasteId();
        $_GET['deletetoken'] = 'does not matter in this context, but has to be set';
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertRegExp(
            '#<div[^>]*id="errormessage"[^>]*>.*Paste does not exist[^<]*</div>#',
            $content,
            'outputs error correctly'
        );
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste successfully deleted');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteMissingPerPasteSalt()
    {
        $this->reset();
        $paste = Helper::getPaste();
        unset($paste['meta']['salt']);
        $this->_model->create(Helper::getPasteId(), $paste);
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists before deleting data');
        $_GET['pasteid'] = Helper::getPasteId();
        $_GET['deletetoken'] = hash_hmac('sha256', Helper::getPasteId(), ServerSalt::get());
        ob_start();
        new PrivateBin;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertRegExp(
            '#<div[^>]*id="status"[^>]*>.*Paste was properly deleted[^<]*</div>#s',
            $content,
            'outputs deleted status correctly'
        );
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste successfully deleted');
    }
}
