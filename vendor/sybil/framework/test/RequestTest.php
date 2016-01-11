<?php
use Sybil\Request;

class RequestTest extends PHPUnit_Framework_TestCase
{
	public function setUp() {
		require_once('../src/Request.php');
	}
	
    public function testLoadAppRoutes()
    {
    	$request = new Request('');
        $this->assertTrue($request->loadAppRoutes());
    }
}
?>