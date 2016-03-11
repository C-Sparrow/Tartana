<?php
namespace Tests\Functional\Tartana\Controller;
use League\Flysystem\Adapter\Local;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use League\Flysystem\Config;

class ApiParameterControllerTest extends WebTestCase
{

	private $client = null;

	public function testV1FindParameters ()
	{
		$crawler = $this->client->request('GET', '/api/v1/parameter/find');

		$this->assertEquals(200, $this->client->getResponse()
			->getStatusCode());
		$resp = json_decode($this->client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);
		$this->assertNotEmpty($resp->data);
		$this->assertGreaterThan(1, $resp->data);
	}

	public function testV1SetParameters ()
	{
		$crawler = $this->client->request('POST', '/api/v1/parameter/set', [
				'tartana.dateFormat' => 'Y-m-d H:i:s'
		]);

		$this->assertEquals(200, $this->client->getResponse()
			->getStatusCode());
		$resp = json_decode($this->client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertNotEmpty($resp->message);
	}

	protected function setUp ()
	{
		$this->loadFixtures([]);

		$client = static::createClient();
		$this->client = $client;

		$fs = new Local(TARTANA_PATH_ROOT);
		$fs->copy('app/config/parameters.yml', 'app/config/parameters.yml.backup.for.test');
	}

	protected function tearDown ()
	{
		$fs = new Local(TARTANA_PATH_ROOT);
		$fs->write('var/cache/.gitkeep', '', new Config());

		$fs->rename('app/config/parameters.yml.backup.for.test', 'app/config/parameters.yml');
	}
}
