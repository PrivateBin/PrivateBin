<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PrivateBin\Request;

class RequestTest extends TestCase
{
    public function reset()
    {
        $_SERVER = array();
        $_GET    = array();
        $_POST   = array();
    }

    /**
     * Returns random query safe characters.
     *
     * @access public
     * @return string
     */
    public function getRandomQueryChars()
    {
        $queryChars     = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ=';
        $queryCharCount = strlen($queryChars) - 1;
        $resultLength   = random_int(1, 10);
        $result         = '';
        for ($i = 0; $i < $resultLength; ++$i) {
            $result .= $queryChars[random_int(0, $queryCharCount)];
        }
        return $result;
    }

    public function testView()
    {
        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request                   = new Request;
        $this->assertFalse($request->isJsonApiCall(), 'is HTML call');
        $this->assertEquals('view', $request->getOperation());
    }

    public function testRead()
    {
        $this->reset();
        $id                        = Helper::getRandomId();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING']   = $id;
        $_GET[$id]                 = '';
        $request                   = new Request;
        $this->assertFalse($request->isJsonApiCall(), 'is HTML call');
        $this->assertEquals($id, $request->getParam('pasteid'));
        $this->assertEquals('read', $request->getOperation());
    }

    /**
     * paste IDs are 8 bytes hex encoded strings, if unlucky, this turns into
     * a numeric string that PHP will cast to an int, for example in array keys
     * @see https://www.php.net/manual/en/language.types.array.php
     */
    public function testReadNumeric()
    {
        $this->reset();
        $id                        = '1234567812345678';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING']   = $id;
        $_GET[$id]                 = '';
        $request                   = new Request;
        $this->assertFalse($request->isJsonApiCall(), 'is HTML call');
        $this->assertEquals($id, $request->getParam('pasteid'));
        $this->assertEquals('read', $request->getOperation());
    }

    public function testDelete()
    {
        $this->reset();
        $id                        = Helper::getRandomId();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['pasteid']           = $id;
        $_GET['deletetoken']       = 'bar';
        $request                   = new Request;
        $this->assertFalse($request->isJsonApiCall(), 'is HTML call');
        $this->assertEquals('delete', $request->getOperation());
        $this->assertEquals($id, $request->getParam('pasteid'));
        $this->assertEquals('bar', $request->getParam('deletetoken'));
    }

    public function testApiCreate()
    {
        $this->reset();
        $_SERVER['REQUEST_METHOD']        = 'PUT';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $file                             = tempnam(sys_get_temp_dir(), 'FOO');
        file_put_contents($file, '{"ct":"foo"}');
        Request::setInputStream($file);
        $request = new Request;
        unlink($file);
        $this->assertTrue($request->isJsonApiCall(), 'is JSON API call');
        $this->assertEquals('create', $request->getOperation());
        $this->assertEquals('foo', $request->getParam('ct'));
    }

    public function testApiCreateAlternative()
    {
        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ACCEPT']    = 'application/json, text/javascript, */*; q=0.01';
        $file                      = tempnam(sys_get_temp_dir(), 'FOO');
        file_put_contents($file, '{"ct":"foo"}');
        Request::setInputStream($file);
        $request = new Request;
        $this->assertTrue($request->isJsonApiCall(), 'is JSON API call');
        $this->assertEquals('create', $request->getOperation());
        $this->assertEquals('foo', $request->getParam('ct'));
    }

    public function testApiRead()
    {
        $this->reset();
        $id                        = Helper::getRandomId();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT']    = 'application/json, text/javascript, */*; q=0.01';
        $_SERVER['QUERY_STRING']   = $id;
        $_GET[$id]                 = '';
        $request                   = new Request;
        $this->assertTrue($request->isJsonApiCall(), 'is JSON API call');
        $this->assertEquals($id, $request->getParam('pasteid'));
        $this->assertEquals('read', $request->getOperation());
    }

    public function testApiDelete()
    {
        $this->reset();
        $id                               = Helper::getRandomId();
        $_SERVER['REQUEST_METHOD']        = 'POST';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['QUERY_STRING']          = $id;
        $_GET                             = array($id => '');
        $file                             = tempnam(sys_get_temp_dir(), 'FOO');
        file_put_contents($file, '{"deletetoken":"bar"}');
        Request::setInputStream($file);
        $request = new Request;
        $this->assertTrue($request->isJsonApiCall(), 'is JSON API call');
        $this->assertEquals('delete', $request->getOperation());
        $this->assertEquals($id, $request->getParam('pasteid'));
        $this->assertEquals('bar', $request->getParam('deletetoken'));
    }

    public function testPostGarbage()
    {
        $this->reset();
        $_SERVER['REQUEST_METHOD']        = 'POST';
        $file                             = tempnam(sys_get_temp_dir(), 'FOO');
        file_put_contents($file, random_bytes(256));
        Request::setInputStream($file);
        $request = new Request;
        unlink($file);
        $this->assertFalse($request->isJsonApiCall(), 'is HTML call');
        $this->assertEquals('create', $request->getOperation());
    }

    public function testReadWithNegotiation()
    {
        $this->reset();
        $id                        = Helper::getRandomId();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT']    = 'text/html,text/html; charset=UTF-8,application/xhtml+xml, application/xml;q=0.9,*/*;q=0.8, text/csv,application/json';
        $_SERVER['QUERY_STRING']   = $id;
        $_GET[$id]                 = '';
        $request                   = new Request;
        $this->assertFalse($request->isJsonApiCall(), 'is HTML call');
        $this->assertEquals($id, $request->getParam('pasteid'));
        $this->assertEquals('read', $request->getOperation());
    }

    public function testReadWithXhtmlNegotiation()
    {
        $this->reset();
        $id                        = Helper::getRandomId();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT']    = 'application/xhtml+xml,text/html,text/html; charset=UTF-8, application/xml;q=0.9,*/*;q=0.8, text/csv,application/json';
        $_SERVER['QUERY_STRING']   = $id;
        $_GET[$id]                 = '';
        $request                   = new Request;
        $this->assertFalse($request->isJsonApiCall(), 'is HTML call');
        $this->assertEquals($id, $request->getParam('pasteid'));
        $this->assertEquals('read', $request->getOperation());
    }

    public function testApiReadWithNegotiation()
    {
        $this->reset();
        $id                        = Helper::getRandomId();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT']    = 'text/plain,text/csv, application/xml;q=0.9, application/json, text/html,text/html; charset=UTF-8,application/xhtml+xml, */*;q=0.8';
        $_SERVER['QUERY_STRING']   = $id;
        $_GET[$id]                 = '';
        $request                   = new Request;
        $this->assertTrue($request->isJsonApiCall(), 'is JSON Api call');
        $this->assertEquals($id, $request->getParam('pasteid'));
        $this->assertEquals('read', $request->getOperation());
    }

    public function testReadWithFailedNegotiation()
    {
        $this->reset();
        $id                        = Helper::getRandomId();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT']    = 'text/plain,text/csv, application/xml;q=0.9, */*;q=0.8';
        $_SERVER['QUERY_STRING']   = $id;
        $_GET[$id]                 = '';
        $request                   = new Request;
        $this->assertFalse($request->isJsonApiCall(), 'is HTML call');
        $this->assertEquals($id, $request->getParam('pasteid'));
        $this->assertEquals('read', $request->getOperation());
    }

    public function testPasteIdExtraction()
    {
        $this->reset();
        $id              = Helper::getRandomId();
        $queryParams     = array($id);
        $queryParamCount = random_int(1, 5);
        for ($i = 0; $i < $queryParamCount; ++$i) {
            array_push($queryParams, $this->getRandomQueryChars());
        }
        shuffle($queryParams);
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING']   = implode('&', $queryParams);
        $_GET[$id]                 = '';
        $request                   = new Request;
        $this->assertEquals($id, $request->getParam('pasteid'));
    }
}
