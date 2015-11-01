<?php
class zerobinTest extends PHPUnit_Framework_TestCase
{
    protected $_model;

    public function setUp()
    {
        /* Setup Routine */
        $this->_model = zerobin_data::getInstance(array('dir' => PATH . 'data'));
        serversalt::setPath(PATH . 'data');
        $this->reset();
    }

    public function tearDown()
    {
        /* Tear Down Routine */
        helper::confRestore();
    }

    public function reset()
    {
        $_POST = array();
        $_GET = array();
        $_SERVER = array();
        if ($this->_model->exists(helper::getPasteId()))
            $this->_model->delete(helper::getPasteId());
        helper::confRestore();
    }

    /**
     * @runInSeparateProcess
     */
    public function testView()
    {
        $this->reset();
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'tag' => 'title',
                'content' => 'ZeroBin'
            ),
            $content,
            'outputs title correctly'
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
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_COOKIE['lang'] = 'de';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'tag' => 'title',
                'content' => 'ZeroBin'
            ),
            $content,
            'outputs title correctly'
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
        new zerobin;
        $content = ob_get_contents();
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
        helper::confBackup();
        file_put_contents(CONF, '');
        ob_start();
        new zerobin;
        $content = ob_get_contents();
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreate()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getPaste();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertEquals(
            hash_hmac('sha1', $response['id'], serversalt::get()),
            $response['deletetoken'],
            'outputs valid delete token'
        );
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidTimelimit()
    {
        $this->reset();
        $_POST = helper::getPaste();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        trafficlimiter::canPass();
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste exists after posting data');
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
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getPaste();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateProxyHeader()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['header'] = 'X_FORWARDED_FOR';
        $options['traffic']['limit'] = 100;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getPaste();
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '::1';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateDuplicateId()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        $_POST = helper::getPaste();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateValidExpire()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getPaste();
        $_POST['expire'] = '5min';
        $_POST['formatter'] = 'foo';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertEquals(
            hash_hmac('sha1', $response['id'], serversalt::get()),
            $response['deletetoken'],
            'outputs valid delete token'
        );
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $paste = $this->_model->read($response['id']);
        $this->assertEquals(time() + 300, $paste->meta->expire_date, 'time is set correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateValidExpireWithDiscussion()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getPaste();
        $_POST['expire'] = '5min';
        $_POST['opendiscussion'] = '1';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertEquals(
            hash_hmac('sha1', $response['id'], serversalt::get()),
            $response['deletetoken'],
            'outputs valid delete token'
        );
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $paste = $this->_model->read($response['id']);
        $this->assertEquals(time() + 300, $paste->meta->expire_date, 'time is set correctly');
        $this->assertEquals(1, $paste->meta->opendiscussion, 'time is set correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidExpire()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getPaste();
        $_POST['expire'] = 'foo';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertEquals(
            hash_hmac('sha1', $response['id'], serversalt::get()),
            $response['deletetoken'],
            'outputs valid delete token'
        );
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidBurn()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getPaste();
        $_POST['burnafterreading'] = 'neither 1 nor 0';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidOpenDiscussion()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getPaste();
        $_POST['opendiscussion'] = 'neither 1 nor 0';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste exists after posting data');
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
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getPasteWithAttachment();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste does not exists before posting data');
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertEquals(
            hash_hmac('sha1', $response['id'], serversalt::get()),
            $response['deletetoken'],
            'outputs valid delete token'
        );
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $original = json_decode(json_encode($_POST));
        $stored = $this->_model->read($response['id']);
        foreach (array('data', 'attachment', 'attachmentname') as $key) {
            $this->assertEquals($original->$key, $stored->$key);
        }
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateValidNick()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getPaste();
        $_POST['nickname'] = helper::getComment()['meta']['nickname'];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertEquals(
            hash_hmac('sha1', $response['id'], serversalt::get()),
            $response['deletetoken'],
            'outputs valid delete token'
        );
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidNick()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getCommentPost();
        $_POST['pasteid'] = helper::getPasteId();
        $_POST['parentid'] = helper::getPasteId();
        $_POST['nickname'] = 'foo';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateComment()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getCommentPost();
        $_POST['pasteid'] = helper::getPasteId();
        $_POST['parentid'] = helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), $response['id']), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidComment()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getCommentPost();
        $_POST['pasteid'] = helper::getPasteId();
        $_POST['parentid'] = 'foo';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateCommentDiscussionDisabled()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getCommentPost();
        $_POST['pasteid'] = helper::getPasteId();
        $_POST['parentid'] = helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $paste = helper::getPaste(array('opendiscussion' => false));
        $this->_model->create(helper::getPasteId(), $paste);
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateCommentInvalidPaste()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $_POST = helper::getCommentPost();
        $_POST['pasteid'] = helper::getPasteId();
        $_POST['parentid'] = helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateDuplicateComment()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        $this->_model->createComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId(), helper::getComment());
        $this->assertTrue($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId()), 'comment exists before posting data');
        $_POST = helper::getCommentPost();
        $_POST['pasteid'] = helper::getPasteId();
        $_POST['parentid'] = helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertTrue($this->_model->existsComment(helper::getPasteId(), helper::getPasteId(), helper::getCommentId()), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testRead()
    {
        $this->reset();
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'cipherdata',
                'content' => htmlspecialchars(helper::getPasteAsJson(), ENT_NOQUOTES)
            ),
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
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'errormessage',
                'content' => 'Invalid paste ID'
            ),
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
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'errormessage',
                'content' => 'Paste does not exist'
            ),
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
        $expiredPaste = helper::getPaste(array('expire_date' => 1344803344));
        $this->_model->create(helper::getPasteId(), $expiredPaste);
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'errormessage',
                'content' => 'Paste does not exist'
            ),
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
        $burnPaste = helper::getPaste(array('burnafterreading' => true));
        $this->_model->create(helper::getPasteId(), $burnPaste);
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'cipherdata',
                'content' => htmlspecialchars(helper::getPasteAsJson($burnPaste['meta']), ENT_NOQUOTES)
            ),
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
        $paste = helper::getPaste();
        $this->_model->create(helper::getPasteId(), $paste);
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs success status');
        $this->assertEquals(helper::getPasteId(), $response['id'], 'outputs data correctly');
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
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadOldSyntax()
    {
        $this->reset();
        $oldPaste = helper::getPaste();
        $meta = array(
            'syntaxcoloring' => true,
            'postdate' => $oldPaste['meta']['postdate'],
            'opendiscussion' => $oldPaste['meta']['opendiscussion'],
        );
        $oldPaste['meta'] = $meta;
        $this->_model->create(helper::getPasteId(), $oldPaste);
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $meta['formatter'] = 'syntaxhighlighting';
        $this->assertTag(
            array(
                'id' => 'cipherdata',
                'content' => htmlspecialchars(helper::getPasteAsJson($meta), ENT_NOQUOTES)
            ),
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
        $oldPaste = helper::getPaste();
        unset($oldPaste['meta']['formatter']);
        $this->_model->create(helper::getPasteId(), $oldPaste);
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $oldPaste['meta']['formatter'] = 'plaintext';
        $this->assertTag(
            array(
                'id' => 'cipherdata',
                'content' => htmlspecialchars(helper::getPasteAsJson($oldPaste['meta']), ENT_NOQUOTES)
            ),
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
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists before deleting data');
        $_GET['pasteid'] = helper::getPasteId();
        $_GET['deletetoken'] = hash_hmac('sha1', helper::getPasteId(), serversalt::get());
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'status',
                'content' => 'Paste was properly deleted'
            ),
            $content,
            'outputs deleted status correctly'
        );
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste successfully deleted');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteInvalidId()
    {
        $this->reset();
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        $_GET['pasteid'] = 'foo';
        $_GET['deletetoken'] = 'bar';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'errormessage',
                'content' => 'Invalid paste ID'
            ),
            $content,
            'outputs delete error correctly'
        );
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists after failing to delete data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteInexistantId()
    {
        $this->reset();
        $_GET['pasteid'] = helper::getPasteId();
        $_GET['deletetoken'] = 'bar';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'errormessage',
                'content' => 'Paste does not exist'
            ),
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
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        $_GET['pasteid'] = helper::getPasteId();
        $_GET['deletetoken'] = 'bar';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'errormessage',
                'content' => 'Wrong deletion token'
            ),
            $content,
            'outputs delete error correctly'
        );
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists after failing to delete data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteBurnAfterReading()
    {
        $this->reset();
        $burnPaste = helper::getPaste(array('burnafterreading' => true));
        $this->_model->create(helper::getPasteId(), $burnPaste);
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists before deleting data');
        $_POST['deletetoken'] = 'burnafterreading';
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste successfully deleted');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteInvalidBurnAfterReading()
    {
        $this->reset();
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists before deleting data');
        $_POST['deletetoken'] = 'burnafterreading';
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste successfully deleted');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteExpired()
    {
        $this->reset();
        $expiredPaste = helper::getPaste(array('expire_date' => 1000));
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste does not exist before being created');
        $this->_model->create(helper::getPasteId(), $expiredPaste);
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists before deleting data');
        $_GET['pasteid'] = helper::getPasteId();
        $_GET['deletetoken'] = 'does not matter in this context, but has to be set';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'errormessage',
                'content' => 'Paste does not exist'
            ),
            $content,
            'outputs error correctly'
        );
        $this->assertFalse($this->_model->exists(helper::getPasteId()), 'paste successfully deleted');
    }
}