<?php
class jsonApiTest extends PHPUnit_Framework_TestCase
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
        $this->assertStringEndsWith('?' . $response['id'], $response['url'], 'returned URL points to new paste');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testPut()
    {
        $this->reset();
        $options = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        helper::confBackup();
        helper::createIniFile(CONF, $options);
        $paste = helper::getPaste();
        unset($paste['meta']);
        $file = tempnam(sys_get_temp_dir(), 'FOO');
        file_put_contents($file, http_build_query($paste));
        request::setInputStream($file);
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertEquals(helper::getPasteId(), $response['id'], 'outputted paste ID matches input');
        $this->assertEquals(
            hash_hmac('sha1', $response['id'], serversalt::get()),
            $response['deletetoken'],
            'outputs valid delete token'
        );
        $this->assertStringEndsWith('?' . $response['id'], $response['url'], 'returned URL points to new paste');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDelete()
    {
        $this->reset();
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists before deleting data');
        $file = tempnam(sys_get_temp_dir(), 'FOO');
        file_put_contents($file, http_build_query(array(
            'deletetoken' => hash_hmac('sha1', helper::getPasteId(), serversalt::get()),
        )));
        request::setInputStream($file);
        $_SERVER['QUERY_STRING'] = helper::getPasteId();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
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
    public function testDeleteWithPost()
    {
        $this->reset();
        $this->_model->create(helper::getPasteId(), helper::getPaste());
        $this->assertTrue($this->_model->exists(helper::getPasteId()), 'paste exists before deleting data');
        $_POST = array(
            'action' => 'delete',
            'deletetoken' => hash_hmac('sha1', helper::getPasteId(), serversalt::get()),
        );
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
    public function testRead()
    {
        $this->reset();
        $paste = helper::getPasteWithAttachment();
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
        $this->assertEquals($paste['attachment'], $response['attachment'], 'outputs attachment correctly');
        $this->assertEquals($paste['attachmentname'], $response['attachmentname'], 'outputs attachmentname correctly');
        $this->assertEquals($paste['meta']['formatter'], $response['meta']['formatter'], 'outputs format correctly');
        $this->assertEquals($paste['meta']['postdate'], $response['meta']['postdate'], 'outputs postdate correctly');
        $this->assertEquals($paste['meta']['opendiscussion'], $response['meta']['opendiscussion'], 'outputs opendiscussion correctly');
        $this->assertEquals(0, $response['comment_count'], 'outputs comment_count correctly');
        $this->assertEquals(0, $response['comment_offset'], 'outputs comment_offset correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testJsonLdPaste()
    {
        $this->reset();
        $paste = helper::getPasteWithAttachment();
        $this->_model->create(helper::getPasteId(), $paste);
        $_GET['jsonld'] = 'paste';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertEquals(str_replace(
                '?jsonld=',
                '/?jsonld=',
                file_get_contents(PUBLIC_PATH . '/js/paste.jsonld')
            ), $content, 'outputs data correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testJsonLdComment()
    {
        $this->reset();
        $paste = helper::getPasteWithAttachment();
        $this->_model->create(helper::getPasteId(), $paste);
        $_GET['jsonld'] = 'comment';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertEquals(str_replace(
                '?jsonld=',
                '/?jsonld=',
                file_get_contents(PUBLIC_PATH . '/js/comment.jsonld')
            ), $content, 'outputs data correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testJsonLdPasteMeta()
    {
        $this->reset();
        $paste = helper::getPasteWithAttachment();
        $this->_model->create(helper::getPasteId(), $paste);
        $_GET['jsonld'] = 'pastemeta';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertEquals(str_replace(
                '?jsonld=',
                '/?jsonld=',
                file_get_contents(PUBLIC_PATH . '/js/pastemeta.jsonld')
            ), $content, 'outputs data correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testJsonLdCommentMeta()
    {
        $this->reset();
        $paste = helper::getPasteWithAttachment();
        $this->_model->create(helper::getPasteId(), $paste);
        $_GET['jsonld'] = 'commentmeta';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertEquals(str_replace(
                '?jsonld=',
                '/?jsonld=',
                file_get_contents(PUBLIC_PATH . '/js/commentmeta.jsonld')
            ), $content, 'outputs data correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testJsonLdInvalid()
    {
        $this->reset();
        $paste = helper::getPasteWithAttachment();
        $this->_model->create(helper::getPasteId(), $paste);
        $_GET['jsonld'] = '../cfg/conf.ini';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertEquals('{}', $content, 'does not output nasty data');
    }

}