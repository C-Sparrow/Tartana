<?php
namespace Tests\Unit\Tartana\Host;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Response;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Shareonlinebiz;

class ShareonlinebizTest extends \PHPUnit_Framework_TestCase
{

	public function testLogin ()
	{
		$mock = new MockHandler(
				[
						new Response(200, [], 'hello'),
						new Response(200, [], ';var dl="' . base64_encode(123) . '";s'),
						new Response(200),
						new Response(200, [
								'Content-Disposition' => [
										0 => 'filename="hello.txt"'
								]
						], 'hello')
				]);

		$client = new Client([
				'handler' => HandlerStack::create($mock),
				'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Shareonlinebiz(new Registry([
				'shareonlinebiz' => [
						'username' => 'hello',
						'password' => 'hello'
				]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $downloads[0]->getFileName());
		}
	}

	public function testInvalidLogin ()
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
		$download = new Download();
		$download->setLink('http://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Shareonlinebiz(new Registry([
				'shareonlinebiz' => [
						'username' => 'hello',
						'password' => 'hello'
				]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertEmpty($dest->listContents());
	}

	public function testEmptyLogin ()
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
		$download = new Download();
		$download->setLink('http://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Shareonlinebiz(new Registry(), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertEmpty($dest->listContents());
	}

	public function testInvalidRedirect ()
	{
		$mock = new MockHandler([
				new Response(200, [], 'hello'),
				new Response(200, [], 'wrong body')
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock),
				'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Shareonlinebiz(new Registry([
				'shareonlinebiz' => [
						'username' => 'hello',
						'password' => 'hello'
				]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertEmpty($dest->listContents());
	}

	public function testInvalidEmptyRedirect ()
	{
		$mock = new MockHandler([
				new Response(200, [], 'hello'),
				new Response(200, [], ';var dl="";s')
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock),
				'cookies' => []
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Shareonlinebiz(new Registry([
				'shareonlinebiz' => [
						'username' => 'hello',
						'password' => 'hello'
				]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertEmpty($dest->listContents());
	}

	public function testCookie ()
	{
		$mock = new MockHandler(
				[
						new Response(200, [], ';var dl="' . base64_encode(123) . '";s'),
						new Response(200),
						new Response(200, [
								'Content-Disposition' => [
										0 => 'filename="hello.txt"'
								]
						], 'hello')
				]);

		$client = new Client(
				[
						'handler' => HandlerStack::create($mock),
						'cookies' => new CookieJar(true,
								[
										[
												'Name' => 'a',
												'Expires' => time() + 1000,
												'Value' => '123',
												'Domain' => '.share-online.biz'
										]
								])
				]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Shareonlinebiz(new Registry([
				'shareonlinebiz' => [
						'username' => 'hello',
						'password' => 'hello'
				]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $downloads[0]->getFileName());
		}
	}

	public function testExpiredCookie ()
	{
		$mock = new MockHandler(
				[
						new Response(200, [], ';var dl="' . base64_encode(123) . '";s'),
						new Response(200),
						new Response(200, [
								'Content-Disposition' => [
										0 => 'filename="hello.txt"'
								]
						], 'hello')
				]);

		$client = new Client(
				[
						'handler' => HandlerStack::create($mock),
						'cookies' => new CookieJar(true,
								[
										[
												'Name' => 'a',
												'Expires' => time() - 1000,
												'Value' => '123',
												'Domain' => '.share-online.biz'
										]
								])
				]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Shareonlinebiz(new Registry([
				'shareonlinebiz' => [
						'username' => 'hello',
						'password' => 'hello'
				]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertEmpty($dest->listContents());
	}

	public function testNotRequiredCookie ()
	{
		$mock = new MockHandler(
				[
						new Response(200, [], ';var dl="' . base64_encode(123) . '";s'),
						new Response(200),
						new Response(200, [
								'Content-Disposition' => [
										0 => 'filename="hello.txt"'
								]
						], 'hello')
				]);

		$client = new Client(
				[
						'handler' => HandlerStack::create($mock),
						'cookies' => new CookieJar(true,
								[
										[
												'Name' => 'ab',
												'Expires' => time() + 1000,
												'Value' => '123',
												'Domain' => '.share-online.biz'
										]
								])
				]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Shareonlinebiz(new Registry([
				'shareonlinebiz' => [
						'username' => 'hello',
						'password' => 'hello'
				]
		]), $client);
		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage(), $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertEmpty($dest->listContents());
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
}