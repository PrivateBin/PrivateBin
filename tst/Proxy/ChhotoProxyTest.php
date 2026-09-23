<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Configuration;
use PrivateBin\Proxy\ChhotoProxy;

class ChhotoProxyTest extends TestCase
{
    private $_conf;

    private $_path;

    private $_mock_chhoto_service;

    public function setUp(): void
    {
        /* Setup Routine */
        $this->_path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'privatebin_data';
        if (!is_dir($this->_path)) {
            mkdir($this->_path);
        }
        $this->_mock_chhoto_service           = $this->_path . DIRECTORY_SEPARATOR . 'chhoto.json';
        $options                              = parse_ini_file(CONF_SAMPLE, true);
        $options['main']['basepath']          = 'https://example.com/';
        $options['main']['urlshortener']      = 'https://example.com/shortenviachhoto?link=';
        $options['chhoto']['apiurl']          = $this->_mock_chhoto_service;
        $options['chhoto']['apikey']          = 'test_api_key';
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

    public function testChhotoProxy()
    {
        // Chhoto usually returns the full short URL in the "shorturl" field.
        file_put_contents($this->_mock_chhoto_service, '{"shorturl":"https:\/\/lix.sk\/abc123"}');

        $chhoto = new ChhotoProxy($this->_conf, 'https://example.com/?foo#bar');
        $this->assertFalse($chhoto->isError());
        $this->assertEquals($chhoto->getUrl(), 'https://lix.sk/abc123');

        // A URL that embeds a foreign host in the user-info part must still be
        // accepted, because the host of the link is our own instance.
        $chhoto = new ChhotoProxy($this->_conf, 'https://example.com/?@foreign.malicious.example?foo#bar');
        $this->assertFalse($chhoto->isError());
        $this->assertEquals($chhoto->getUrl(), 'https://lix.sk/abc123');
    }

    public function testChhotoProxyWithSlugFallback()
    {
        // Older Chhoto versions may return only the slug in "shortlink" instead
        // of a full "shorturl". The proxy then prepends the configured apiurl.
        file_put_contents($this->_mock_chhoto_service, '{"shortlink":"abc123"}');

        $chhoto = new ChhotoProxy($this->_conf, 'https://example.com/?foo#bar');
        $this->assertFalse($chhoto->isError());
        // apiurl (the mock file path) is prepended to the ltrimmed slug.
        $this->assertEquals($chhoto->getUrl(), $this->_mock_chhoto_service . 'abc123');
    }

    public function testChhotoProxyWithLeadingSlashSlug()
    {
        // A slug that already starts with a slash must not produce a double slash.
        file_put_contents($this->_mock_chhoto_service, '{"shortlink":"/abc123"}');

        $chhoto = new ChhotoProxy($this->_conf, 'https://example.com/?foo#bar');
        $this->assertFalse($chhoto->isError());
        $this->assertEquals($chhoto->getUrl(), $this->_mock_chhoto_service . 'abc123');
    }

    /**
     * @dataProvider providerInvalidUrl
     */
    public function testInvalidUrl($url): void
    {
        $chhoto = new ChhotoProxy($this->_conf, $url);
        $this->assertTrue($chhoto->isError());
        $this->assertEquals($chhoto->getError(), 'Invalid URL given.');
    }

    public function providerInvalidUrl(): array
    {
        return [
            [''],
            [' '],
            ['foo'],
            ['https://'],
            ['https://example.com'], // missing path and query parameter,
            ['https://example.com/'], // missing query parameter
            ['https://example.com?paste=something'], // missing path parameter
            ['https://example.com@foreign.malicious.example?foo#bar'], // missing path parameter
        ];
    }

    /**
     * This tests for a trick using username of an URI, see:
     * {@see https://cloud.google.com/blog/topics/threat-intelligence/url-obfuscation-schema-abuse/?hl=en}
     *
     * @dataProvider providerForeignUrlUsernameTrick
     */
    public function testForeignUrlUsingUsernameTrick($url): void
    {
        $chhoto = new ChhotoProxy($this->_conf, $url);
        $this->assertTrue($chhoto->isError());
        $this->assertEquals($chhoto->getError(), 'Trying to shorten a URL that isn\'t pointing at our instance.');
    }

    public function providerForeignUrlUsernameTrick(): array
    {
        return [
            ['https://example.com@foreign.malicious.example/?foo#bar'],
            ['https://example.com/@foreign.malicious.example?foo#bar'],
        ];
    }

    /**
     * @dataProvider providerForeignUrl
     */
    public function testForeignUrl($url): void
    {
        $chhoto = new ChhotoProxy($this->_conf, $url);
        $this->assertTrue($chhoto->isError());
        $this->assertEquals($chhoto->getError(), 'Trying to shorten a URL that isn\'t pointing at our instance.');
    }

    public function providerForeignUrl(): array
    {
        return [
            ['ftp://example.com/?n=np'], // wrong protocol
            ['https://other.example.com/?foo#bar'], // wrong domain
            ['https://other.example.com/?q=https://example.com/?foo#bar'], // domain included inside string
        ];
    }

    public function testChhotoError()
    {
        // Chhoto may reply with a body that contains neither "shorturl" nor
        // "shortlink"; this must be handled gracefully as an error instead of
        // raising a TypeError (the method is declared to return ?string).
        file_put_contents($this->_mock_chhoto_service, '{"message":"error"}');

        $chhoto = new ChhotoProxy($this->_conf, 'https://example.com/?foo#bar');
        $this->assertTrue($chhoto->isError());
        $this->assertEquals($chhoto->getError(), 'Proxy error: Error parsing proxy response. This can be a configuration issue, like wrong or missing config keys.');
    }

    public function testChhotoSuccessWithoutShortUrl()
    {
        // A 200-style reply that omits the short URL fields must be treated as
        // an error, not as a successful (empty) shortening.
        file_put_contents($this->_mock_chhoto_service, '{"status":"ok"}');

        $chhoto = new ChhotoProxy($this->_conf, 'https://example.com/?foo#bar');
        $this->assertTrue($chhoto->isError());
        $this->assertEquals($chhoto->getError(), 'Proxy error: Error parsing proxy response. This can be a configuration issue, like wrong or missing config keys.');
    }

    public function testServerError()
    {
        // simulate some other server error that results in a non-JSON reply
        file_put_contents($this->_mock_chhoto_service, '500 Internal Server Error');

        $chhoto = new ChhotoProxy($this->_conf, 'https://example.com/?foo#bar');
        $this->assertTrue($chhoto->isError());
        $this->assertEquals($chhoto->getError(), 'Proxy error: Error parsing proxy response. This can be a configuration issue, like wrong or missing config keys.');
    }
}
