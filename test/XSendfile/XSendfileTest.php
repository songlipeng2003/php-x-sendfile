<?php
use XSendfile\XSendfile;
use PHPUnit\Framework\TestCase;

class XSendfileTest extends TestCase
{
	private $file;
	private $filename;

	public function setUp() : void
	{
		$this->file = dirname(__FILE__)."/../tmp.png";
		$this->filename = 'tmp.png';
	}

	public function testDectServer()
	{
		$_SERVER['SERVER_SOFTWARE'] = 'Apache 2.4';
		$serverType = XSendfile::detectServer();

		$this->assertEquals(XSendfile::SERVER_TYPE_APACHE, $serverType);


		$_SERVER['SERVER_SOFTWARE'] = 'Nginx 1.0';
		$serverType = XSendfile::detectServer();

		$this->assertEquals(XSendfile::SERVER_TYPE_NGINX, $serverType);


		$_SERVER['SERVER_SOFTWARE'] = 'lighttpd 1.5';
		$serverType = XSendfile::detectServer();

		$this->assertEquals(XSendfile::SERVER_TYPE_LIGHTTPD, $serverType);


		$_SERVER['SERVER_SOFTWARE'] = 'None';
		$serverType = XSendfile::detectServer();

		$this->assertEquals(null, $serverType);
	}

    /**
     * @runInSeparateProcess
     */
	public function testIE()
	{
    	$_SERVER["HTTP_USER_AGENT"] = 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Trident/6.0)';

        XSendfile::xSendfile($this->file, 'tmp.png', XSendfile::SERVER_TYPE_APACHE);

        $headers_list = xdebug_get_headers();

        $this->assertNotEmpty($headers_list);
        $filename = basename($this->filename);
        $this->assertContains("Content-Disposition: attachment; filename=\"{$filename}\"", $headers_list);
	}

    /**
     * @runInSeparateProcess
     */
	public function testChrome()
	{

    	$_SERVER["HTTP_USER_AGENT"] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';

        XSendfile::xSendfile($this->file, 'tmp.png', XSendfile::SERVER_TYPE_APACHE);

        $headers_list = xdebug_get_headers();

        $this->assertNotEmpty($headers_list);
        $this->assertContains("Content-Disposition: attachment; filename=\"{$this->filename}\"", $headers_list);
	}

    /**
     * @runInSeparateProcess
     */
	public function testFirefox()
	{
    	$_SERVER["HTTP_USER_AGENT"] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:56.0) Gecko/20100101 Firefox/56.0';

        XSendfile::xSendfile($this->file, 'tmp.png', XSendfile::SERVER_TYPE_APACHE);

        $headers_list = xdebug_get_headers();

        $this->assertNotEmpty($headers_list);
        $filename = basename($this->filename);
        $this->assertContains("Content-Disposition: attachment; filename*=\"utf8''{$filename}\"", $headers_list);
	}

    /**
     * @runInSeparateProcess
     * @depends testChrome
     */
    public function testApache()
    {
    	// chrome
    	$_SERVER["HTTP_USER_AGENT"] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';

        XSendfile::xSendfile($this->file, $this->filename, XSendfile::SERVER_TYPE_APACHE);

        $headers_list = xdebug_get_headers();

        $this->assertNotEmpty($headers_list);
        $this->assertContains("X-Sendfile: $this->file", $headers_list);
    }

    /**
     * @runInSeparateProcess
     * @depends testChrome
     */
	public function testCache()
	{

    	$_SERVER["HTTP_USER_AGENT"] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';

        XSendfile::xSendfile($this->file, $this->filename, XSendfile::SERVER_TYPE_APACHE, true);

        $headers_list = xdebug_get_headers();

        $this->assertNotEmpty($headers_list);
        $this->assertContains("Cache-Control: max-age=2592000", $headers_list);
	}

    /**
     * @runInSeparateProcess
     * @depends testChrome
     */
	public function testCacheSince()
	{

    	$_SERVER["HTTP_USER_AGENT"] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';
    	$_SERVER['HTTP_IF_MODIFIED_SINCE'] = date('Y-m-d H:i:s', filemtime($this->file));

        XSendfile::xSendfile($this->file, $this->filename, XSendfile::SERVER_TYPE_APACHE, true);

        $this->assertEquals(304, http_response_code());
	}

    /**
     * @runInSeparateProcess
     * @depends testChrome
     */
	public function testCacheMatch()
	{

    	$_SERVER["HTTP_USER_AGENT"] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';
    	$_SERVER['IF-NONE-MATCH'] = md5(filemtime($this->file));

        XSendfile::xSendfile($this->file, $this->filename, XSendfile::SERVER_TYPE_APACHE, true);

        $this->assertEquals(304, http_response_code());
	}

    /**
     * @runInSeparateProcess
     * @depends testChrome
     */
    public function testNginx()
    {
    	// chrome
    	$_SERVER["HTTP_USER_AGENT"] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';

        XSendfile::xSendfile($this->file, $this->filename, XSendfile::SERVER_TYPE_NGINX);

        $headers_list = xdebug_get_headers();

        $this->assertNotEmpty($headers_list);
        $this->assertContains("X-Accel-Redirect: $this->file", $headers_list);
    }

    /**
     * @runInSeparateProcess
     * @depends testChrome
     */
    public function testLighttpd()
    {
    	// chrome
    	$_SERVER["HTTP_USER_AGENT"] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';

        XSendfile::xSendfile($this->file, $this->filename, XSendfile::SERVER_TYPE_LIGHTTPD);

        $headers_list = xdebug_get_headers();

        $this->assertNotEmpty($headers_list);
        $this->assertContains("X-LIGHTTPD-send-file: $this->file", $headers_list);
    }

	/**
     * @runInSeparateProcess
     * @depends testChrome
     */
    public function testImageFile()
    {

    	$_SERVER["HTTP_USER_AGENT"] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.75 Safari/537.36';

        XSendfile::xSendfile($this->file);

        $headers_list = xdebug_get_headers();

        $this->assertNotEmpty($headers_list);
        $this->assertContains("Content-type: image/png", $headers_list);
    }
}
