<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Controller;
use PrivateBin\Data\Filesystem;
use PrivateBin\Persistence\ServerSalt;
use PrivateBin\Request;

class JsonApiTest extends TestCase
{
    protected $_model;

    protected $_path;

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_path  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        $this->_model = new Filesystem(array('dir' => $this->_path));
        ServerSalt::setStore($this->_model);

        $_POST   = array();
        $_GET    = array();
        $_SERVER = array();
        if ($this->_model->exists(Helper::getPasteId())) {
            $this->_model->delete(Helper::getPasteId());
        }
        $options                         = parse_ini_file(CONF_SAMPLE, true);
        $options['model_options']['dir'] = $this->_path;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
    }

    public function tearDown(): void
    {
        /* Tear Down Routine */
        unlink(CONF);
        Helper::confRestore();
        Helper::rmDir($this->_path);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCreate()
    {
        $options                     = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::createIniFile(CONF, $options);
        $paste = Helper::getPasteJson();
        $file  = tempnam(sys_get_temp_dir(), 'FOO');
        file_put_contents($file, $paste);
        Request::setInputStream($file);
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD']        = 'POST';
        $_SERVER['REMOTE_ADDR']           = '::1';
        $_SERVER['REQUEST_URI']           = '/';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertStringEndsWith('?' . $response['id'], $response['url'], 'returned URL points to new paste');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $paste = $this->_model->read($response['id']);
        $this->assertEquals(
            hash_hmac('sha256', $response['id'], $paste['meta']['salt']),
            $response['deletetoken'],
            'outputs valid delete token'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testPut()
    {
        $options                     = parse_ini_file(CONF, true);
        $options['traffic']['limit'] = 0;
        Helper::createIniFile(CONF, $options);
        $paste = Helper::getPasteJson();
        $file  = tempnam(sys_get_temp_dir(), 'FOO');
        file_put_contents($file, $paste);
        Request::setInputStream($file);
        $_SERVER['QUERY_STRING']          = Helper::getPasteId();
        $_GET[Helper::getPasteId()]       = '';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD']        = 'PUT';
        $_SERVER['REMOTE_ADDR']           = '::1';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
        unlink($file);
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertEquals(Helper::getPasteId(), $response['id'], 'outputted paste ID matches input');
        $this->assertStringEndsWith('?' . $response['id'], $response['url'], 'returned URL points to new paste');
        $this->assertTrue($this->_model->exists($response['id']), 'paste exists after posting data');
        $paste = $this->_model->read($response['id']);
        $this->assertEquals(
            hash_hmac('sha256', $response['id'], $paste['meta']['salt']),
            $response['deletetoken'],
            'outputs valid delete token'
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testDelete()
    {
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists before deleting data');
        $paste = $this->_model->read(Helper::getPasteId());
        $file  = tempnam(sys_get_temp_dir(), 'FOO');
        file_put_contents($file, json_encode(array(
            'deletetoken' => hash_hmac('sha256', Helper::getPasteId(), $paste['meta']['salt']),
        )));
        Request::setInputStream($file);
        $_SERVER['QUERY_STRING']          = Helper::getPasteId();
        $_GET[Helper::getPasteId()]       = '';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD']        = 'DELETE';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
        unlink($file);
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste successfully deleted');
    }

    /**
     * @runInSeparateProcess
     */
    public function testDeleteWithPost()
    {
        $this->_model->create(Helper::getPasteId(), Helper::getPaste());
        $this->assertTrue($this->_model->exists(Helper::getPasteId()), 'paste exists before deleting data');
        $paste = $this->_model->read(Helper::getPasteId());
        $file  = tempnam(sys_get_temp_dir(), 'FOO');
        file_put_contents($file, json_encode(array(
            'pasteid'     => Helper::getPasteId(),
            'deletetoken' => hash_hmac('sha256', Helper::getPasteId(), $paste['meta']['salt']),
        )));
        Request::setInputStream($file);
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['REQUEST_METHOD']        = 'POST';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs status');
        $this->assertFalse($this->_model->exists(Helper::getPasteId()), 'paste successfully deleted');
    }

    /**
     * @runInSeparateProcess
     */
    public function testRead()
    {
        $paste                           = Helper::getPaste();
        $this->_model->create(Helper::getPasteId(), $paste);
        $_SERVER['QUERY_STRING']          = Helper::getPasteId();
        $_GET[Helper::getPasteId()]       = '';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
        $response = json_decode($content, true);
        $this->assertEquals(0, $response['status'], 'outputs success status');
        $this->assertEquals(Helper::getPasteId(), $response['id'], 'outputs data correctly');
        $this->assertStringEndsWith('?' . $response['id'], $response['url'], 'returned URL points to new paste');
        $this->assertEquals($paste['ct'], $response['ct'], 'outputs data correctly');
        $this->assertFalse(array_key_exists('created', $paste['meta']), 'does not output created');
        $this->assertEquals(0, $response['comment_count'], 'outputs comment_count correctly');
        $this->assertEquals(0, $response['comment_offset'], 'outputs comment_offset correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testJsonLdPaste()
    {
        $_GET['jsonld'] = 'paste';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
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
        $_GET['jsonld'] = 'comment';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
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
        $_GET['jsonld'] = 'pastemeta';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
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
        $_GET['jsonld'] = 'commentmeta';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertEquals(str_replace(
            '?jsonld=',
            '/?jsonld=',
            file_get_contents(PUBLIC_PATH . '/js/commentmeta.jsonld')
        ), $content, 'outputs data correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testJsonLdTypes()
    {
        $_GET['jsonld'] = 'types';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertEquals(str_replace(
            '?jsonld=',
            '/?jsonld=',
            file_get_contents(PUBLIC_PATH . '/js/types.jsonld')
        ), $content, 'outputs data correctly');
    }

    /**
     * @runInSeparateProcess
     */
    public function testJsonLdInvalid()
    {
        $_GET['jsonld'] = CONF;
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertEquals('{}', $content, 'does not output nasty data');
    }

    /**
     * @runInSeparateProcess
     * @dataProvider baseUriProvider
     */
    public function testShortenViaYourls($baseUri)
    {
        $mock_yourls_service                = $this->_path . DIRECTORY_SEPARATOR . 'yourls.json';
        $options                            = parse_ini_file(CONF, true);
        $options['main']['basepath']        = 'https://example.com/path'; // missing slash gets added by Configuration constructor
        $options['main']['urlshortener']    = 'https://example.com' . $baseUri . 'link=';
        $options['yourls']['apiurl']        = $mock_yourls_service;
        Helper::createIniFile(CONF, $options);

        // the real service answer is more complex, but we only look for the shorturl & statusCode
        file_put_contents($mock_yourls_service, '{"shorturl":"https:\/\/example.com\/1","statusCode":200}');

        $_SERVER['REQUEST_URI'] = $baseUri . 'link=https%3A%2F%2Fexample.com%2Fpath%2F%3Ffoo%23bar';
        $_GET['link']           = 'https://example.com/path/?foo#bar';
        if (str_contains($baseUri, '?shortenviayourls')) {
            $_GET['shortenviayourls'] = null;
        }
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertStringContainsString('id="pasteurl" href="https://example.com/1"', $content, "'{$baseUri}' outputs shortened URL correctly");
    }

    public function baseUriProvider()
    {
        return array(
            array('/path/shortenviayourls?'),
            array('/path/index.php/shortenviayourls?'),
            array('/path?shortenviayourls&'),
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testShortenViaYourlsFailure()
    {
        $options                            = parse_ini_file(CONF, true);
        $options['main']['basepath']        = 'https://example.com/path'; // missing slash gets added by Configuration constructor
        Helper::createIniFile(CONF, $options);
        $_SERVER['REQUEST_URI'] = '/path/shortenviayourls?link=https%3A%2F%2Fexample.com%2Fpath%2F%3Ffoo%23bar';
        $_GET['link']           = 'https://example.com/path/?foo#bar';
        ob_start();
        new Controller;
        $content = ob_get_contents();
        ob_end_clean();
        $this->assertStringContainsString('Error calling YOURLS.', $content, 'outputs error correctly');
    }
}
