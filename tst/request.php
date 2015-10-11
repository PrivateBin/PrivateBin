<?php
class requestTest extends PHPUnit_Framework_TestCase
{
    private $_request;

    public function setUp()
    {
        /* Setup Routine */
    }

    public function tearDown()
    {
        /* Tear Down Routine */
    }

    public function reset()
    {
        $_SERVER = array();
        $_GET = array();
        $_POST = array();
    }

    public function testView()
    {
        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new request;
        $this->assertFalse($request->isJsonApiCall(), 'is HTML call');
        $this->assertEquals('view', $request->getOperation());
    }

    public function testRead()
    {
        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = 'foo';
        $request = new request;
        $this->assertFalse($request->isJsonApiCall(), 'is HTML call');
        $this->assertEquals('foo', $request->getParam('pasteid'));
        $this->assertEquals('read', $request->getOperation());
    }

    public function testDelete()
    {
        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['pasteid'] = 'foo';
        $_GET['deletetoken'] = 'bar';
        $request = new request;
        $this->assertFalse($request->isJsonApiCall(), 'is HTML call');
        $this->assertEquals('delete', $request->getOperation());
        $this->assertEquals('foo', $request->getParam('pasteid'));
        $this->assertEquals('bar', $request->getParam('deletetoken'));
    }

    public function testApiCreate()
    {
        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $file = tempnam(sys_get_temp_dir(), 'FOO');
        file_put_contents($file, 'data=foo');
        request::setInputStream($file);
        $request = new request;
        $this->assertTrue($request->isJsonApiCall(), 'is JSON Api call');
        $this->assertEquals('create', $request->getOperation());
        $this->assertEquals('foo', $request->getParam('data'));
    }

    public function testApiCreateAlternative()
    {
        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_ACCEPT'] = 'application/json, text/javascript, */*; q=0.01';
        $_POST['attachment'] = 'foo';
        $request = new request;
        $this->assertTrue($request->isJsonApiCall(), 'is JSON Api call');
        $this->assertEquals('create', $request->getOperation());
        $this->assertEquals('foo', $request->getParam('attachment'));
    }

    public function testApiRead()
    {
        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCEPT'] = 'application/json, text/javascript, */*; q=0.01';
        $_SERVER['QUERY_STRING'] = 'foo';
        $request = new request;
        $this->assertTrue($request->isJsonApiCall(), 'is JSON Api call');
        $this->assertEquals('foo', $request->getParam('pasteid'));
        $this->assertEquals('read', $request->getOperation());
    }

    public function testApiDelete()
    {
        $this->reset();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'JSONHttpRequest';
        $_SERVER['QUERY_STRING'] = 'foo';
        $_POST['deletetoken'] = 'bar';
        $request = new request;
        $this->assertTrue($request->isJsonApiCall(), 'is JSON Api call');
        $this->assertEquals('delete', $request->getOperation());
        $this->assertEquals('foo', $request->getParam('pasteid'));
        $this->assertEquals('bar', $request->getParam('deletetoken'));
    }
}