<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Configuration;
use PrivateBin\Proxy\YourlsProxy;

class YourlsProxyTest extends TestCase
{
    private $_conf;

    private $_path;

    private $_mock_yourls_service;

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        $this->_mock_yourls_service         = $this->_path . DIRECTORY_SEPARATOR . 'yourls.json';
        $options                            = parse_ini_file(CONF_SAMPLE, true);
        $options['main']['basepath']        = 'https://example.com/';
        $options['main']['urlshortener']    = 'https://example.com/shortenviayourls?link=';
        $options['yourls']['apiurl']        = $this->_mock_yourls_service;
        Helper::confBackup();
        Helper::createIniFile(CONF, $options);
        $this->_conf = new Configuration;
    }

    public function tearDown(): void
    {
        /* Tear Down Routine */
        unlink(CONF);
        Helper::confRestore();
        Helper::rmDir($this->_path);
    }

    public function testYourlsProxy()
    {
        // the real service answer is more complex, but we only look for the shorturl & statusCode
        file_put_contents($this->_mock_yourls_service, '{"shorturl":"https:\/\/example.com\/1","statusCode":200}');

        $yourls = new YourlsProxy($this->_conf, 'https://example.com/?foo#bar');
        $this->assertFalse($yourls->isError());
        $this->assertEquals($yourls->getUrl(), 'https://example.com/1');

        $yourls = new YourlsProxy($this->_conf, 'https://example.com/?@foreign.malicious.example?foo#bar');
        $this->assertFalse($yourls->isError());
        $this->assertEquals($yourls->getUrl(), 'https://example.com/1');
    }

    /**
     * @dataProvider providerInvalidUrl
     */
    public function testImvalidUrl($url): void
    {
        $yourls = new YourlsProxy($this->_conf, $url);
        $this->assertTrue($yourls->isError());
        $this->assertEquals($yourls->getError(), 'Invalid URL given.');
    }

    public function providerInvalidUrl(): array
    {
        return array(
            array(''),
            array(' '),
            array('foo'),
            array('https://'),
            array('https://example.com'), // missing path and query parameter,
            array('https://example.com/'), // missing query parameter
            array('https://example.com?paste=something'), // missing path parameter
            array('https://example.com@foreign.malicious.example?foo#bar'), // missing path parameter
        );
    }

    /**
     * This tests for a trick using username of an URI, see:
     * {@see https://cloud.google.com/blog/topics/threat-intelligence/url-obfuscation-schema-abuse/?hl=en}
     *
     * @dataProvider providerForeignUrlUsernameTrick
     */
    public function testForeignUrlUsingUsernameTrick($url): void
    {
        $yourls = new YourlsProxy($this->_conf, $url);
        $this->assertTrue($yourls->isError());
        $this->assertEquals($yourls->getError(), 'Trying to shorten a URL that isn\'t pointing at our instance.');
    }

    public function providerForeignUrlUsernameTrick(): array
    {
        return array(
            array('https://example.com@foreign.malicious.example/?foo#bar'),
            array('https://example.com/@foreign.malicious.example?foo#bar'),
        );
    }

    /**
     * @dataProvider providerForeignUrl
     */
    public function testForeignUrl($url): void
    {
        $yourls = new YourlsProxy($this->_conf, $url);
        $this->assertTrue($yourls->isError());
        $this->assertEquals($yourls->getError(), 'Trying to shorten a URL that isn\'t pointing at our instance.');
    }

    public function providerForeignUrl(): array
    {
        return array(
            array('ftp://example.com/?n=np'), // wrong protocol
            array('https://other.example.com/?foo#bar'), // wrong domain
            array('https://other.example.com/?q=https://example.com/?foo#bar'), // domain included inside string
        );
    }

    public function testYourlsError()
    {
        // when statusCode is not 200, shorturl may not have been set
        file_put_contents($this->_mock_yourls_service, '{"statusCode":403}');

        $yourls = new YourlsProxy($this->_conf, 'https://example.com/?foo#bar');
        $this->assertTrue($yourls->isError());
        $this->assertEquals($yourls->getError(), 'Proxy error: Error parsing proxy response. This can be a configuration issue, like wrong or missing config keys.');
    }

    public function testServerError()
    {
        // simulate some other server error that results in a non-JSON reply
        file_put_contents($this->_mock_yourls_service, '500 Internal Server Error');

        $yourls = new YourlsProxy($this->_conf, 'https://example.com/?foo#bar');
        $this->assertTrue($yourls->isError());
        $this->assertEquals($yourls->getError(), 'Proxy error: Error parsing proxy response. This can be a configuration issue, like wrong or missing config keys.');
    }
}
