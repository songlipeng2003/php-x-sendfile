<?php
use XSendfile\XSendfile;

class XSendfileTest extends PHPUnit_Framework_TestCase 
{
    /**
     * @runInSeparateProcess
     */
    public function testXSendFile()
    {
        // apache 
        $file = dirname(__FILE__)."/../tmp.png";
        XSendfile::xSendfile($file, XSendfile::SERVER_TYPE_APACHE);

        $headers_list = xdebug_get_headers();
        
        $this->assertNotEmpty($headers_list);
        $this->assertContains("X-Sendfile: $file", $headers_list);
    }
}
