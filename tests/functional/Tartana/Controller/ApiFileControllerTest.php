<?php
namespace Tests\Functional\Tartana\Controller;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ApiFileControllerTest extends WebTestCase
{

	public function testV1FileAdd()
	{
		$client = static::createClient();

		$fs = new Local(__DIR__ . '/test');
		$fs->write('test.txt', 'http://github.com', new Config());
		$file    = new UploadedFile($fs->applyPathPrefix('test.txt'), 'test.txt');
		$crawler = $client->request('GET', '/api/v1/file/add', [], [
			$file
		]);

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());

		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertNotEmpty($resp->message);
	}

	protected function tearDown()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/var/data/Links');
		$fs->delete('test.txt');

		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}
}
