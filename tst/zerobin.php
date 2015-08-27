<?php
class zerobinTest extends PHPUnit_Framework_TestCase
{
    private static $pasteid = '501f02e9eeb8bcec';

    private static $paste = array(
        'data' => '{"iv":"EN39/wd5Nk8HAiSG2K5AsQ","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"QKN1DBXe5PI","ct":"8hA83xDdXjD7K2qfmw5NdA"}',
        'meta' => array(
            'postdate' => 1344803344,
            'opendiscussion' => true,
        ),
    );

    private $_model;

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
    }

    public function reset()
    {
        $_POST = array();
        $_GET = array();
        $_SERVER = array();
        if ($this->_model->exists(self::$pasteid))
            $this->_model->delete(self::$pasteid);
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
    public function testCreate()
    {
        $this->reset();
        $_POST = self::$paste;
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals($response['status'], 0, 'outputs status');
        $this->assertEquals(
            $response['deletetoken'],
            hash_hmac('sha1', $response['id'], serversalt::get()),
            'outputs valid delete token'
        );
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testRead()
    {
        $this->reset();
        $this->_model->create(self::$pasteid, self::$paste);
        $_SERVER['QUERY_STRING'] = self::$pasteid;
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'cipherdata',
                'content' => htmlspecialchars(json_encode(self::$paste), ENT_NOQUOTES)
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
        $this->_model->create(self::$pasteid, self::$paste);
        $this->assertTrue($this->_model->exists(self::$pasteid), 'paste exists before deleting data');
        $_GET['pasteid'] = self::$pasteid;
        $_GET['deletetoken'] = hash_hmac('sha1', self::$pasteid, serversalt::get());
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
        $this->assertFalse($this->_model->exists(self::$pasteid), 'paste successfully deleted');
    }
}