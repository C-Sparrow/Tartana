<?php
namespace Tests\Unit\Synology\Mixins;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;

class SynologyApiTraitTest extends \PHPUnit_Framework_TestCase
{

	public function testGetSetClient()
	{
		$client = $this->getMockBuilder(ClientInterface::class)->getMock();
		$api    = $this->getObjectForTrait('Synology\Mixins\SynologyApiTrait');

		$this->assertEmpty($api->getClient());

		$api->setClient($client);

		$this->assertEquals($client, $api->getClient());
	}

	public function testGetSetUrl()
	{
		$url = 'https://localhost';
		$api = $this->getObjectForTrait('Synology\Mixins\SynologyApiTrait');

		$this->assertEquals('https://localhost:5001/webapi/', $api->getUrl());

		$api->setUrl($url);

		$this->assertEquals($url, $api->getUrl());
	}

	public function testGetSetUsername()
	{
		$username = 'unit-test';
		$api      = $this->getObjectForTrait('Synology\Mixins\SynologyApiTrait');

		$this->assertEquals('admin', $api->getUsername());

		$api->setUsername($username);

		$this->assertEquals($username, $api->getUsername());
	}

	public function testGetSetPassword()
	{
		$pw  = 'unit-test';
		$api = $this->getObjectForTrait('Synology\Mixins\SynologyApiTrait');

		$this->assertEquals('admin', $api->getPassword());

		$api->setPassword($pw);

		$this->assertEquals($pw, $api->getPassword());
	}

	public function testSynologyApiCallNoArguments()
	{
		$api = $this->getObjectForTrait('Synology\Mixins\SynologyApiTrait');
		$api->setClient($this->getMockClient());

		$response = $api->synologyApiCall([]);

		$this->assertNotEmpty($response);
		$this->assertNotEmpty($response->success);
		$this->assertTrue($response->success);
		$this->assertEmpty($response->data);
	}

	public function testSynologyApiCallWithArguments()
	{
		$api = $this->getObjectForTrait('Synology\Mixins\SynologyApiTrait');
		$api->setClient($this->getMockClient());

		$response = $api->synologyApiCall([
			'method' => 'list'
		]);

		$this->assertNotEmpty($response);
		$this->assertNotEmpty($response->success);
		$this->assertTrue($response->success);
		$this->assertNotEmpty($response->data);
		$this->assertNotEmpty($response->data->tasks);
		$this->assertCount(2, $response->data->tasks);
		$this->assertEquals(1, $response->data->tasks[0]->id);
		$this->assertEquals('finished', $response->data->tasks[0]->status);
		$this->assertEquals(2, $response->data->tasks[1]->id);
		$this->assertEquals('downloading', $response->data->tasks[1]->status);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testSynologyApiCallNoClient()
	{
		$api = $this->getObjectForTrait('Synology\Mixins\SynologyApiTrait');

		$response = $api->synologyApiCall([]);

		$this->assertNotEmpty($response);
		$this->assertNotEmpty($response->success);
		$this->assertTrue($response->success);
		$this->assertEmpty($response->data);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testSynologyApiCallEmptyResponse()
	{
		$client = $this->getMockBuilder(ClientInterface::class)->getMock();
		$client->method('request')->will($this->returnValue(new Response(200, [
			'Content-Type' => 'application/json'
		], '')));

		$api = $this->getObjectForTrait('Synology\Mixins\SynologyApiTrait');
		$api->setClient($client);

		$api->synologyApiCall([]);
	}

	public function testSynologyApiLogin()
	{
		$client = $this->getMockBuilder(ClientInterface::class)->getMock();
		$client->method('request')->will(
			$this->returnCallback(
				function ($method, $url, $arguments) {
					$content = [
						'success' => true,
						'data' => [
							'sid' => 1234
						]
					];

					return new Response(200, [
						'Content-Type' => 'application/json'
					], json_encode($content));
				}
			)
		);

		$api = $this->getObjectForTrait('Synology\Mixins\SynologyApiTrait');
		$api->setClient($client);

		$sid = $api->synologyApiLogin();

		$this->assertNotEmpty($sid);
		$this->assertEquals(1234, $sid);
	}

	public function testSynologyApiLoginInvalidResponse()
	{
		$client = $this->getMockBuilder(ClientInterface::class)->getMock();
		$client->method('request')->will(
			$this->returnCallback(
				function ($method, $url, $arguments) {
					$content = [
						'success' => true
					];
					return new Response(200, [
						'Content-Type' => 'application/json'
					], json_encode($content));
				}
			)
		);

		$api = $this->getObjectForTrait('Synology\Mixins\SynologyApiTrait');
		$api->setClient($client);

		$sid = $api->synologyApiLogin();

		$this->assertFalse($sid);
	}

	/**
	 * @expectedException RuntimeException
	 */
	public function testSynologyApiLoginEmptyResponse()
	{
		$client = $this->getMockBuilder(ClientInterface::class)->getMock();
		$client->method('request')->will(
			$this->returnCallback(
				function ($method, $url, $arguments) {
					return new Response(200, [
						'Content-Type' => 'application/json'
					], json_encode(''));
				}
			)
		);

		$api = $this->getObjectForTrait('Synology\Mixins\SynologyApiTrait');
		$api->setClient($client);

		$api->synologyApiLogin();
	}

	private function getMockClient()
	{
		$client = $this->getMockBuilder(ClientInterface::class)->getMock();
		$client->method('request')->will(
			$this->returnCallback(
				function ($method, $url, $arguments) {
					$content = [
						'success' => true,
						'data' => []
					];

					parse_str($arguments['body'], $arguments);
					if (key_exists('method', $arguments)) {
						switch ($arguments['method']) {
							case 'login':
								$content['data']['sid'] = 1234;
								break;
							case 'list':
								$content['data']['tasks'] = [
									(object)array(
										'id' => 1,
										'status' => 'finished'
									),
									(object)array(
										'id' => 2,
										'status' => 'downloading'
									)
								];
						}
					}
					return new Response(200, [
						'Content-Type' => 'application/json'
					], json_encode($content));
				}
			)
		);
		return $client;
	}
}
