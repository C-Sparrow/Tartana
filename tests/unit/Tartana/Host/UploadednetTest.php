<?php
namespace Tests\Unit\Tartana\Host;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Uploadednet;

class UploadednetTest extends \PHPUnit_Framework_TestCase
{

	public function testFetchDownloadInfo()
	{
		$mock = new MockHandler([
			new Response(200, [], 'online,nfndcvz3,1049624000,fjkhsd72ukbd7j3,hello.txt')
		]);

		$client = new Client([
			'handler' => HandlerStack::create($mock),
			'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://foo.bar/asdf/file/ldlsls/kagsd');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Uploadednet(new Registry(), $client);
		$downloader->fetchDownloadInfo([
			$download
		]);

		$this->assertEquals('hello.txt', $download->getFileName());
		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
	}

	public function testFetchDownloadInfoWrongUrl()
	{
		$mock = new MockHandler(
			[
				new Response(200, [
					'Content-Disposition' => [
						0 => 'filename="hello.txt"'
					]
				])
			]
		);

		$client = new Client([
			'handler' => HandlerStack::create($mock),
			'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://foo.bar/asdf/ldlsls/kagsd');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Uploadednet(new Registry(), $client);
		$downloader->fetchDownloadInfo([
			$download
		]);

		$this->assertEquals('hello.txt', $download->getFileName());
		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
	}

	public function testFetchDownloadInfoNoOkStatus()
	{
		$mock = new MockHandler([
			new Response(200, [], 'deleted,nfndcvz3,1049624000,fjkhsd72ukbd7j3,hello.txt')
		]);

		$client = new Client([
			'handler' => HandlerStack::create($mock),
			'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://foo.bar/asdf/file/ldlsls/kagsd');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Uploadednet(new Registry(), $client);
		$downloader->fetchDownloadInfo([
			$download
		]);

		$this->assertEquals('deleted', $download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
	}

	public function testFetchDownloadInfoWrongInfo()
	{
		$mock = new MockHandler([
			new Response(200, [], 'no csv content'),
			new Response(200)
		]);

		$client = new Client([
			'handler' => HandlerStack::create($mock),
			'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://foo.bar/asdf/file/ldlsls/kagsd');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Uploadednet(new Registry(), $client);
		$downloader->fetchDownloadInfo([
			$download
		]);

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
	}

	public function testFetchDownloadInfoException()
	{
		$mock = new MockHandler([
			new RequestException('Failed on unit test', new Request('GET', 'test'))
		]);

		$client = new Client([
			'handler' => HandlerStack::create($mock),
			'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://foo.bar/asdf/file/ldlsls/kagsd');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Uploadednet(new Registry(), $client);
		$downloader->fetchDownloadInfo([
			$download
		]);

		$this->assertNotEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
	}

	public function testLogin()
	{
		$mock = new MockHandler(
			[
				new Response(200, [], 'hello'),
				new Response(200, [], 'some html http://am4-r1f6-stor06.uploaded.net/dl/234 around the link'),
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

		$downloads = [];
		$download  = new Download();
		$download->setLink('http://foo.bar/file/ldlsls/asd');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Uploadednet(new Registry([
			'uploadednet' => [
				'username' => 'hello',
				'password' => 'hello'
			]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $downloads[0]->getFileName());
		}
	}

	public function testInvalidLogin()
	{
		$mock = new MockHandler([
			new Response(200, [], 'hell')
		]);

		$client = new Client([
			'handler' => HandlerStack::create($mock),
			'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink('http://foo.bar/file/ldlsls/asd');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Uploadednet(new Registry([
			'uploadednet' => [
				'username' => 'hello',
				'password' => 'hello'
			]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertEmpty($dest->listContents());
	}

	public function testEmptyLogin()
	{
		$mock = new MockHandler([
			new Response(200, [], 'hello')
		]);

		$client = new Client([
			'handler' => HandlerStack::create($mock),
			'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink('http://foo.bar/file/ldlsls/asd');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Uploadednet(new Registry(), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertEmpty($dest->listContents());
	}

	public function testInvalidRedirect()
	{
		$mock = new MockHandler(
			[
				new Response(200, [], 'hello'),
				new Response(200, [], 'online,nfndcvz3,1049624000,fjkhsd72ukbd7j3,hello.txt'),
				new Response(200, [], 'wrong body')
			]
		);

		$client = new Client([
			'handler' => HandlerStack::create($mock),
			'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink('http://foo.bar/file/ldlsls/asd');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Uploadednet(new Registry([
			'uploadednet' => [
				'username' => 'hello',
				'password' => 'hello'
			]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertEmpty($dest->listContents());
	}

	public function testCookie()
	{
		$mock = new MockHandler(
			[
				new Response(200, [], 'some html http://am4-r1f6-stor06.uploaded.net/dl/234 around the link'),
				new Response(200, [
					'Content-Disposition' => [
						0 => 'filename="hello.txt"'
					]
				], 'hello')
			]
		);

		$client = new Client(
			[
				'handler' => HandlerStack::create($mock),
				'cookies' => new CookieJar(
					true,
					[
						[
							'Name' => 'login',
							'Expires' => time() + 1000,
							'Value' => '123',
							'Domain' => '.uploaded.net'
						]
					]
				)
			]
		);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink('http://foo.bar/file/ldlsls/asd');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Uploadednet(new Registry([
			'uploadednet' => [
				'username' => 'hello',
				'password' => 'hello'
			]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $downloads[0]->getFileName());
		}
	}

	public function testExpiredCookie()
	{
		$mock = new MockHandler(
			[
				new Response(200, [], 'hello'),
				new Response(200, [], 'some html http://am4-r1f6-stor06.uploaded.net/dl/234 around the link'),
				new Response(200, [
					'Content-Disposition' => [
						0 => 'filename="hello.txt"'
					]
				], 'hello')
			]
		);

		$client = new Client(
			[
				'handler' => HandlerStack::create($mock),
				'cookies' => new CookieJar(
					true,
					[
						[
							'Name' => 'login',
							'Expires' => time() - 1000,
							'Value' => '123',
							'Domain' => '.uploaded.net'
						]
					]
				)
			]
		);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink('http://foo.bar/file/ldlsls/asd');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Uploadednet(new Registry([
			'uploadednet' => [
				'username' => 'hello',
				'password' => 'hello'
			]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $downloads[0]->getFileName());
		}
	}

	public function testNotRequiredCookie()
	{
		$mock = new MockHandler(
			[
				new Response(200, [], 'hello'),
				new Response(200, [], 'some html http://am4-r1f6-stor06.uploaded.net/dl/234 around the link'),
				new Response(200, [
					'Content-Disposition' => [
						0 => 'filename="hello.txt"'
					]
				], 'hello')
			]
		);

		$client = new Client(
			[
				'handler' => HandlerStack::create($mock),
				'cookies' => new CookieJar(
					true,
					[
						[
							'Name' => 'sss',
							'Expires' => time() + 1000,
							'Value' => '123',
							'Domain' => '.uploaded.net'
						]
					]
				)
			]
		);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink('http://foo.bar/file/ldlsls/asd');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Uploadednet(new Registry([
			'uploadednet' => [
				'username' => 'hello',
				'password' => 'hello'
			]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $downloads[0]->getFileName());
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
