<?php
namespace Tests\Functional\Tartana\Controller;
use Liip\FunctionalTestBundle\Test\WebTestCase;

class ApiUserControllerTest extends WebTestCase
{

	private $client = null;

	public function testV1FindUser ()
	{
		$crawler = $this->client->request('GET', '/api/v1/user/find', [
				'username' => 'admin'
		]);

		$this->assertEquals(200, $this->client->getResponse()
			->getStatusCode());
		$resp = json_decode($this->client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);
		$this->assertNotEmpty($resp->data);
		$this->assertCount(1, $resp->data);

		$this->assertNotEmpty($resp->data[0]->salt);
		$this->assertNotEmpty($resp->data[0]->username);
		$this->assertFalse(isset($resp->data[0]->password));
	}

	public function testV1FindUserNotExist ()
	{
		$crawler = $this->client->request('GET', '/api/v1/user/find', [
				'username' => 'notexisting'
		]);

		$this->assertEquals(200, $this->client->getResponse()
			->getStatusCode());
		$resp = json_decode($this->client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(false, $resp->success);
		$this->assertNotEmpty($resp->message);
		$this->assertFalse(isset($resp->data));
	}

	public function testV1FindUserEmpty ()
	{
		$crawler = $this->client->request('GET', '/api/v1/user/find');

		$this->assertEquals(200, $this->client->getResponse()
			->getStatusCode());
		$resp = json_decode($this->client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(false, $resp->success);
		$this->assertNotEmpty($resp->message);
		$this->assertFalse(isset($resp->data));
	}

	public function testV1Salt ()
	{
		$crawler = $this->client->request('GET', '/api/v1/user/salt', [
				'username' => 'admin'
		]);

		$this->assertEquals(200, $this->client->getResponse()
			->getStatusCode());
		$resp = json_decode($this->client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);
		$this->assertNotEmpty($resp->data->salt);
		$this->assertFalse(isset($resp->data->username));
	}

	protected function setUp ()
	{
		$this->loadFixtures([
				'Tartana\DataFixtures\ORM\LoadUserData'
		]);

		$client = static::createClient();

		$this->client = $client;
	}
}
