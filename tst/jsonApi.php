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
        $file = tempnam(sys_get_temp_dir(), 'FOO');
        $paste = helper::getPaste();
        unset($paste['meta']);
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

}