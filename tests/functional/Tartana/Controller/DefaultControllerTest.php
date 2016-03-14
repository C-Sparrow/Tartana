<?php
namespace Tests\Functional\Tartana\Controller;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{

	public function testIndex ()
	{
		$client = static::createClient();

		$crawler = $client->request('GET', '/');

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());

		$this->assertGreaterThan(0, $crawler->filter('.page-header h1')
			->count());
		$this->assertGreaterThan(0, $crawler->filter('.navbar')
			->count());
		$this->assertGreaterThan(0, $crawler->filter('.navbar-nav')
			->count());
	}

	public function testDashboard ()
	{
		$client = static::createClient();

		$crawler = $client->request('GET', '/dashboard');

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());

		$this->assertEquals(1, $crawler->filter('table')
			->count());
		$this->assertEquals(1, $crawler->filter('form')
			->count());
	}

	public function testLogin ()
	{
		$client = static::createClient();

		$crawler = $client->request('GET', '/login');

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());

		$this->assertEquals(1, $crawler->filter('form')
			->count());
	}

	public function testDownloads ()
	{
		$client = static::createClient();

		$crawler = $client->request('GET', '/downloads');

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$this->assertEquals(1, $crawler->filter('table')
			->count());
	}

	public function testParameters ()
	{
		$client = static::createClient();

		$crawler = $client->request('GET', '/parameters');

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$this->assertEquals(1, $crawler->filter('form')
			->count());
	}

	public function testLogs ()
	{
		$client = static::createClient();

		$crawler = $client->request('GET', '/logs');

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$this->assertEquals(1, $crawler->filter('table')
			->count());
	}
}
