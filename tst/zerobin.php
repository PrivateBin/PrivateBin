<?php
class zerobinTest extends PHPUnit_Framework_TestCase
{
    private static $pasteid = '5e9bc25c89fb3bf9';

    private static $paste = array(
        'data' => '{"iv":"EN39/wd5Nk8HAiSG2K5AsQ","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"QKN1DBXe5PI","ct":"8hA83xDdXjD7K2qfmw5NdA"}',
        'meta' => array(
            'postdate' => 1344803344,
            'opendiscussion' => true,
            'formatter' => 'syntaxhighlighting',
        ),
    );

    private static $commentid = '5a52eebf11c4c94b';

    private static $comment = array(
        'data' => '{"iv":"Pd4pOKWkmDTT9uPwVwd5Ag","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"6nOCU3peNDclDDpFtJEBKA"}',
        'meta' => array(
            'nickname' => '{"iv":"76MkAtOGC4oFogX/aSMxRA","v":1,"iter":1000,"ks":128,"ts":64,"mode":"ccm","adata":"","cipher":"aes","salt":"ZIUhFTliVz4","ct":"b6Ae/U1xJdsX/+lATud4sQ"}',
            'vizhash' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAABGUlEQVQokWOsl5/94983CNKQMjnxaOePf98MeKwPfNjkLZ3AgARab6b9+PeNEVnDj3/ff/z7ZiHnzsDA8Pv7H2TVPJw8EAYLAwb48OaVgIgYKycLsrYv378wMDB8//qdCVMDRA9EKSsnCwRBxNsepaLboMFlyMDAICAi9uHNK24GITQ/MDAwoNhgIGMLtwGrzegaLjw5jMz9+vUdnN17uwDCQDhJgk0O07yvX9+teDX1x79v6DYIsIjgcgMaYGFgYOBg4kJx2JejkAiBxAw+PzAwMNz4dp6wDXDw4MdNNOl0rWYsNkD89OLXI/xmo9sgzatJjAYmBgYGDiauD3/ePP18nVgb4MF89+M5ZX6js293wUMpnr8KTQMAxsCJnJ30apMAAAAASUVORK5CYII=',
            'postdate' => 1344803528,
        ),
    );

    private $_conf;

    private $_model;

    public function setUp()
    {
        /* Setup Routine */
        $this->_model = zerobin_data::getInstance(array('dir' => PATH . 'data'));
        serversalt::setPath(PATH . 'data');
        $this->_conf = PATH . 'cfg' . DIRECTORY_SEPARATOR . 'conf.ini';
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
        if (is_file($this->_conf . '.bak'))
            rename($this->_conf . '.bak', $this->_conf);
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
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        file_put_contents($this->_conf, '');
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
        $_POST = self::$paste;
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
        $_POST = self::$paste;
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(self::$pasteid), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidSize()
    {
        $this->reset();
        $options = parse_ini_file($this->_conf, true);
        $options['main']['sizelimit'] = 10;
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $_POST = self::$paste;
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(self::$pasteid), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateDuplicateId()
    {
        $this->reset();
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $this->_model->create(self::$pasteid, self::$paste);
        $_POST = self::$paste;
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertTrue($this->_model->exists(self::$pasteid), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateValidExpire()
    {
        $this->reset();
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $_POST = self::$paste;
        $_POST['expire'] = '5min';
        $_POST['formatter'] = 'foo';
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
    public function testCreateInvalidExpire()
    {
        $this->reset();
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $_POST = self::$paste;
        $_POST['expire'] = 'foo';
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
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $_POST = self::$paste;
        $_POST['burnafterreading'] = 'neither 1 nor 0';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(self::$pasteid), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidOpenDiscussion()
    {
        $this->reset();
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $_POST = self::$paste;
        $_POST['opendiscussion'] = 'neither 1 nor 0';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(self::$pasteid), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateValidNick()
    {
        $this->reset();
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $_POST = self::$paste;
        $_POST['nickname'] = self::$comment['meta']['nickname'];
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
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $_POST = self::$paste;
        $_POST['nickname'] = 'foo';
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->exists(self::$pasteid), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateComment()
    {
        $this->reset();
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $_POST = self::$comment;
        $_POST['pasteid'] = self::$pasteid;
        $_POST['parentid'] = self::$pasteid;
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->_model->create(self::$pasteid, self::$paste);
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->existsComment(self::$pasteid, self::$pasteid, $response['id']), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateInvalidComment()
    {
        $this->reset();
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $_POST = self::$comment;
        $_POST['pasteid'] = self::$pasteid;
        $_POST['parentid'] = 'foo';
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->_model->create(self::$pasteid, self::$paste);
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->existsComment(self::$pasteid, self::$pasteid, self::$commentid), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateCommentDiscussionDisabled()
    {
        $this->reset();
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $_POST = self::$comment;
        $_POST['pasteid'] = self::$pasteid;
        $_POST['parentid'] = self::$pasteid;
        $_SERVER['REMOTE_ADDR'] = '::1';
        $paste = self::$paste;
        $paste['meta']['opendiscussion'] = false;
        $this->_model->create(self::$pasteid, $paste);
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->existsComment(self::$pasteid, self::$pasteid, self::$commentid), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateCommentInvalidPaste()
    {
        $this->reset();
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $_POST = self::$comment;
        $_POST['pasteid'] = self::$pasteid;
        $_POST['parentid'] = self::$pasteid;
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertFalse($this->_model->existsComment(self::$pasteid, self::$pasteid, self::$commentid), 'paste exists after posting data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreateDuplicateComment()
    {
        $this->reset();
        $options = parse_ini_file($this->_conf, true);
        $options['traffic']['limit'] = 0;
        if (!is_file($this->_conf . '.bak') && is_file($this->_conf))
            rename($this->_conf, $this->_conf . '.bak');
        helper::createIniFile($this->_conf, $options);
        $this->_model->create(self::$pasteid, self::$paste);
        $this->_model->createComment(self::$pasteid, self::$pasteid, self::$commentid, self::$comment);
        $this->assertTrue($this->_model->existsComment(self::$pasteid, self::$pasteid, self::$commentid), 'comment exists before posting data');
        $_POST = self::$comment;
        $_POST['pasteid'] = self::$pasteid;
        $_POST['parentid'] = self::$pasteid;
        $_SERVER['REMOTE_ADDR'] = '::1';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
        $this->assertTrue($this->_model->existsComment(self::$pasteid, self::$pasteid, self::$commentid), 'paste exists after posting data');
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
        $_SERVER['QUERY_STRING'] = self::$pasteid;
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
        $expiredPaste = self::$paste;
        $expiredPaste['meta']['expire_date'] = $expiredPaste['meta']['postdate'];
        $this->_model->create(self::$pasteid, $expiredPaste);
        $_SERVER['QUERY_STRING'] = self::$pasteid;
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
        $burnPaste = self::$paste;
        $burnPaste['meta']['burnafterreading'] = true;
        $this->_model->create(self::$pasteid, $burnPaste);
        $_SERVER['QUERY_STRING'] = self::$pasteid;
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $this->assertTag(
            array(
                'id' => 'cipherdata',
                'content' => htmlspecialchars(json_encode($burnPaste), ENT_NOQUOTES)
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
        $this->_model->create(self::$pasteid, self::$paste);
        $_SERVER['QUERY_STRING'] = self::$pasteid . '&json';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs success status');
        $this->assertEquals(array(self::$paste), $response['messages'], 'outputs data correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testReadInvalidJson()
    {
        $this->reset();
        $_SERVER['QUERY_STRING'] = self::$pasteid . '&json';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs error status');
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

    /**
     * @runInSeparateProcess
     */
    public function testDeleteInvalidId()
    {
        $this->reset();
        $this->_model->create(self::$pasteid, self::$paste);
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
        $this->assertTrue($this->_model->exists(self::$pasteid), 'paste exists after failing to delete data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteInexistantId()
    {
        $this->reset();
        $_GET['pasteid'] = self::$pasteid;
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
        $this->_model->create(self::$pasteid, self::$paste);
        $_GET['pasteid'] = self::$pasteid;
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
        $this->assertTrue($this->_model->exists(self::$pasteid), 'paste exists after failing to delete data');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteBurnAfterReading()
    {
        $this->reset();
        $burnPaste = self::$paste;
        $burnPaste['meta']['burnafterreading'] = true;
        $this->_model->create(self::$pasteid, $burnPaste);
        $this->assertTrue($this->_model->exists(self::$pasteid), 'paste exists before deleting data');
        $_GET['pasteid'] = self::$pasteid;
        $_GET['deletetoken'] = 'burnafterreading';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertFalse($this->_model->exists(self::$pasteid), 'paste successfully deleted');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteInvalidBurnAfterReading()
    {
        $this->reset();
        $this->_model->create(self::$pasteid, self::$paste);
        $this->assertTrue($this->_model->exists(self::$pasteid), 'paste exists before deleting data');
        $_GET['pasteid'] = self::$pasteid;
        $_GET['deletetoken'] = 'burnafterreading';
        ob_start();
        new zerobin;
        $content = ob_get_contents();
        $response = json_decode($content, true);
        $this->assertEquals(1, $response['status'], 'outputs status');
        $this->assertTrue($this->_model->exists(self::$pasteid), 'paste successfully deleted');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteExpired()
    {
        $this->reset();
        $expiredPaste = self::$paste;
        $expiredPaste['meta']['expire_date'] = 1000;
        $this->assertFalse($this->_model->exists(self::$pasteid), 'paste does not exist before being created');
        $this->_model->create(self::$pasteid, $expiredPaste);
        $this->assertTrue($this->_model->exists(self::$pasteid), 'paste exists before deleting data');
        $_GET['pasteid'] = self::$pasteid;
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
        $this->assertFalse($this->_model->exists(self::$pasteid), 'paste successfully deleted');
    }
}