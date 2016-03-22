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
use Tartana\Host\Rapidgatornet;

class RapidgatornetTest extends \PHPUnit_Framework_TestCase
{

	public function testFetchDownloadInfo ()
	{
		$mock = new MockHandler(
				[
						new Response(200, [],
								json_encode(
										[
												'response' => [
														'filename' => 'hello.txt',
														'size' => 123,
														'hash' => 1234
												],
												'response_status' => 200
										]))
				]);

		$client = new Client([
				'handler' => HandlerStack::create($mock),
				'cookies' => $this->getCookie()
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://foo.bar/asdf/file/ldlsls/kagsd');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Rapidgatornet(new Registry(), $client);
		$downloader->fetchDownloadInfo([
				$download
		]);

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
		$this->assertEquals('hello.txt', $download->getFileName());
		$this->assertEquals(123, $download->getSize());
		$this->assertEquals(1234, $download->getHash());
	}

	public function testFetchDownloadInfoInvalidResponseStatus ()
	{
		$mock = new MockHandler([
				new Response(200, [], json_encode([
						'response' => [],
						'response_status' => 404
				]))
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock),
				'cookies' => $this->getCookie()
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://foo.bar/asdf/file/ldlsls/kagsd');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Rapidgatornet(new Registry(), $client);
		$downloader->fetchDownloadInfo([
				$download
		]);

		$this->assertNotEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
	}

	public function testFetchDownloadInfoException ()
	{
		$mock = new MockHandler([
				new RequestException('Failed on unit test', new Request('GET', 'test'))
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock),
				'cookies' => $this->getCookie()
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://foo.bar/asdf/file/ldlsls/kagsd');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Rapidgatornet(new Registry(), $client);
		$downloader->fetchDownloadInfo([
				$download
		]);

		$this->assertNotEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
	}

	public function testFetchDownloadInfoWrongLogin ()
	{
		$mock = new MockHandler([
				new Response(200, [], json_encode([
						'response' => [],
						'response_status' => 404
				]))
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://foo.bar/asdf/file/ldlsls/kagsd');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Rapidgatornet(new Registry(), $client);
		$downloader->fetchDownloadInfo([
				$download
		]);

		$this->assertNotEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
	}

	public function testLoginAndDownload ()
	{
		$mock = new MockHandler(
				[
						new Response(200, [], json_encode([
								'response_status' => 200
						])),
						new Response(200, [],
								json_encode(
										[
												'response' => [
														'url' => 'foo.bar/hdhdhdh'
												],
												'response_status' => 200
										])),
						new Response(200, [
								'Content-Disposition' => [
										0 => 'filename="hello.txt"'
								]
						], 'hello')
				]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://foo.bar/file/ldlsls/asd');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Rapidgatornet(new Registry([
				'rapidgatornet' => [
						'username' => 'hello',
						'password' => 'hello'
				]
		]), $client);
		Promise\unwrap($downloader->download([
				$download
		]));

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $download->getState());

		$this->assertCount(1, $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $download->getFileName());
		}
	}

	public function testDownloadInvalidDownloadUrl ()
	{
		$mock = new MockHandler([
				new Response(200, [], json_encode([
						'response_status' => 404
				]))
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock),
				'cookies' => $this->getCookie()
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://foo.bar/file/ldlsls/asd');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Rapidgatornet(new Registry([
				'rapidgatornet' => [
						'username' => 'hello',
						'password' => 'hello'
				]
		]), $client);
		Promise\unwrap($downloader->download([
				$download
		]));

		$this->assertNotEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
	}

	protected function setUp ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}

	private function getCookie ()
	{
		return new CookieJar(true,
				[
						[
								'Name' => 'PHPSESSID',
								'Expires' => 0,
								'Value' => '123',
								'Domain' => '.rapidgator.net'
						]
				]);
	}
}