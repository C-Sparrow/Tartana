<?php

namespace Tests\Functional\Tartana\Controller;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ClicknLoadControllerTest extends WebTestCase
{

	private $packageName = 'unit test';

	public function testV1AddCrypted2()
	{
		$client = static::createClient();

		$client->request(
			'GET',
			'/flash/addcrypted2',
			[
				'package' => $this->packageName,
				'jk' => 'function f(){ return \'' . bin2hex('1234567890987654') . '\';}',
				'crypted' => $this->generateLink()
			]
		);

		$this->assertEquals(200, $client->getResponse()->getStatusCode());

		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertNotEmpty($resp->message);
	}

	public function testV1AddCrypted2WrongCryptedContent()
	{
		$client = static::createClient();

		$client->request(
			'GET',
			'/flash/addcrypted2',
			[
				'package' => $this->packageName,
				'jk' => 'function f(){ return \'32353638323637333930313331393530\';}',
				'crypted' => 'wrong'
			]
		);

		$this->assertEquals(200, $client->getResponse()->getStatusCode());

		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(false, $resp->success);
		$this->assertNotEmpty($resp->message);
	}

	protected function tearDown()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/var/data/Links');
		if ($fs->has($this->packageName . '.txt')) {
			$fs->delete($this->packageName . '.txt');
		}

		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}

	private function generateLink()
	{
		$key  = "1234567890987654";
		$link = "http://example.com/dl/test";
		$cp   = @mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
		@mcrypt_generic_init($cp, $key, $key);
		$enc = @mcrypt_generic($cp, $link);
		@mcrypt_generic_deinit($cp);
		@mcrypt_module_close($cp);
		return base64_encode($enc);
	}
}
