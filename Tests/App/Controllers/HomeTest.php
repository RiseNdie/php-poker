<?php
namespace Tests\Controllers\App;

class HomepageTest extends \Tests\WebTestCase
{
    public function testGetHomepage() {
        $response = $this->runApp('GET', '/home');
        $this->assertEquals(200, $response->getStatus());
        $this->assertContains('END', (string)$response->getBody());
        $this->assertNotContains('START', (string)$response->getBody());
    }
	
	public function testNotFound() {
        $response = $this->runApp('GET', '/homeNotFound');
        $this->assertEquals(404, $response->getStatus());
	}	
}
