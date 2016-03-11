<?php
namespace Tests\Unit\Tartana\Host\Common;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;
use Tartana\Host\Common;
use Tartana\Host\Common\Http;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class HttpTest extends TartanaBaseTestCase
{

	protected $scheme = 'http';

	public function testDownloadLink ()
	{
		$mock = new MockHandler(
				[
						new Response(200),
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

		$downloads = [];
		$download = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Http(new Registry(), $client);
		$promises = $downloader->download($downloads);
		Promise\unwrap($promises);

		$this->assertNotEmpty($promises);
		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $downloads[0]->getFileName());
		}
	}

	public function testDownloadLinkTmpFileName ()
	{
		$mock = new MockHandler([
				new Response(200),
				new Response(200, [], 'hello')
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setId(2);
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Http(new Registry(), $client);
		$promises = $downloader->download($downloads);
		Promise\unwrap($promises);

		$this->assertNotEmpty($promises);
		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('tmp-' . $download->getId() . '.bin', $downloads[0]->getFileName());
		}
	}

	public function testDownloadLinkExistingFileName ()
	{
		$mock = new MockHandler(
				[
						new Response(200),
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

		$downloads = [];
		$download = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$download->setFileName('unit-test.txt');
		$downloads[] = $download;

		$downloader = new Http(new Registry(), $client);
		$promises = $downloader->download($downloads);
		Promise\unwrap($promises);

		$this->assertNotEmpty($promises);
		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('unit-test.txt', $downloads[0]->getFileName());
		}
	}

	public function testDownloadLinkWithCommandBus ()
	{
		$mock = new MockHandler(
				[
						new Response(200),
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

		$downloads = [];
		$download = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Http(new Registry(), $client);
		$downloader->setCommandBus(
				$this->getMockCommandBus(
						[
								$this->callback(
										function  (SaveDownloads $download) {
											return $download->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_COMPLETED;
										})
						]));
		$promises = $downloader->download($downloads);
		Promise\unwrap($promises);
	}

	public function testGetFilenameFromHeader ()
	{
		$mock = new MockHandler(
				[
						new Response(200, [
								'Content-Disposition' => [
										0 => 'filename="hello.txt"'
								]
						]),
						new Response(200, [], 'hello')
				]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Http(new Registry(), $client);
		$promises = $downloader->download($downloads);
		Promise\unwrap($promises);

		$this->assertNotEmpty($promises);
		$this->assertEmpty($downloads[0]->getMessage());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $downloads[0]->getFileName());
		}
	}

	public function testEmptyLink ()
	{
		$mock = new MockHandler(
				[
						new Response(200),
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

		$downloads = [];
		$download = new Download();
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Http(new Registry(), $client);
		$promises = $downloader->download($downloads);

		$this->assertEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertCount(0, $dest->listContents());
	}

	public function testDownloadFailed ()
	{
		$mock = new MockHandler([
				new Response(200),
				new RequestException('Failed on unit test', new Request('GET', 'test'))
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Http(new Registry(), $client);
		$downloader->setCommandBus(
				$this->getMockCommandBus(
						[
								$this->callback(
										function  (SaveDownloads $download) {
											return $download->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_ERROR;
										})
						]));
		$promises = $downloader->download($downloads);
		try
		{
			Promise\unwrap($promises);
		}
		catch (RequestException $e)
		{
			// Expected
		}

		$this->assertNotEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals('Failed on unit test', $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertCount(0, $dest->listContents());
	}

	public function testInvalidLink ()
	{
		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink($this->scheme . '://kjguwjkasdlocalhostkasjk/jstwghbkaszhsjk');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Http(new Registry([
				'clearSession' => true
		]));

		$promises = $downloader->download($downloads);

		$this->assertEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertCount(0, $dest->listContents());
	}

	public function testInvalidDestination ()
	{
		$downloads = [];
		$download = new Download();
		$download->setLink($this->scheme . '://kjguwjkasdlocalhostkasjk/jstwghbkaszhsjk');
		$download->setDestination('/invalid');
		$downloads[] = $download;

		$downloader = new Http(new Registry([
				'clearSession' => true
		]));

		$promises = $downloader->download($downloads);

		$this->assertEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$dest = new Local(__DIR__ . '/test');
		$this->assertCount(0, $dest->listContents());
	}

	public function testEmptyDownloads ()
	{
		$client = new Client([
				'handler' => HandlerStack::create(new MockHandler([]))
		]);

		$downloader = new Http(new Registry(), $client);
		$promises = $downloader->download([]);

		$this->assertEmpty($promises);

		$dest = new Local(__DIR__ . '/test');
		$this->assertCount(0, $dest->listContents());
	}

	public function testInvalidLogin ()
	{
		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink($this->scheme . '://httptestinvalidlogin.org/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Httptestinvalidlogin(false);
		$promises = $downloader->download($downloads);

		$this->assertEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertCount(0, $dest->listContents());
	}

	public function testExceptionOnLogin ()
	{
		$client = new Client([
				'handler' => HandlerStack::create(new MockHandler([]))
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink($this->scheme . '://httptestinvalidlogin.org/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Httptestinvalidlogin(true);
		$promises = $downloader->download($downloads);

		$this->assertEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertCount(0, $dest->listContents());
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

class Httptestinvalidlogin extends Http
{

	private $throwException = false;

	public function __construct ($throwException)
	{
		parent::__construct(new Registry(), new Client([
				'handler' => HandlerStack::create(new MockHandler([]))
		]));

		$this->throwException = $throwException;
	}

	protected function login ()
	{
		if ($this->throwException)
		{
			throw new \Exception('Login failed');
		}
		return false;
	}
}