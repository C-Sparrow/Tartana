<?php
namespace Tests\Unit\Tartana\Host;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Response;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Dropboxcom;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class DropboxcomTest extends TartanaBaseTestCase
{

	public function testDownloadToken()
	{
		$mock = new MockHandler(
			[
						new Response(
							200,
							[
										'dropbox-api-result' => [
												0 => json_encode([
														'name' => 'hello.txt'
												])
										]
							],
							'hello'
						)
			]
		);

		$client = new Client([
				'handler' => HandlerStack::create($mock),
				'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://dropbox.com/ldlsls');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Dropboxcom(new Registry([
				'dropboxcom' => [
						'token' => 'hello'
				]
		]), $client);
		Promise\unwrap($downloader->download([
				$download
		]));

		$this->assertNotEmpty($mock->getLastRequest()
			->getHeaders());
		$this->assertEquals($mock->getLastRequest()
			->getHeaders()['Authorization'][0], 'Bearer hello');
		$this->assertEquals($mock->getLastRequest()
			->getHeaders()['Dropbox-API-Arg'][0], '{"url": "http://dropbox.com/ldlsls?dl=1"}');

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $download->getState());

		$this->assertCount(1, $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $download->getFileName());
		}
	}

	public function testDownloadNoToken()
	{
		$mock = new MockHandler(
			[
						new Response(200, [
								'Content-Disposition' => [
										0 => 'filename="hello.txt"'
								]
						], 'hello')
			]
		);

		$client = new Client([
				'handler' => HandlerStack::create($mock),
				'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://dropbox.com/ldlsls');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Dropboxcom(new Registry([]), $client);
		Promise\unwrap($downloader->download([
				$download
		]));

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $download->getState());

		$this->assertCount(1, $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $download->getFileName());
		}
	}

	protected function setUp()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}
}
