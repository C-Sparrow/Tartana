<?php
namespace Tests\Unit\Tartana\Host\Common;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
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
use Tartana\Host\Common\Https;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class HttpTest extends TartanaBaseTestCase
{

	protected $scheme = 'http';

	public function testFetchLinkList()
	{
		$downloader = $this->getHttp(new Registry());
		$links      = $downloader->fetchLinkList('http://foo.bar/test');

		$this->assertEquals([
			'http://foo.bar/test'
		], $links);
	}

	public function testFetchDownloadInfo()
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
			'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());

		$downloader = $this->getHttp(new Registry(), $client);
		$downloader->fetchDownloadInfo([
			$download
		]);

		$this->assertEmpty($download->getMessage());
		$this->assertEquals('hello.txt', $download->getFileName());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
	}

	public function testFetchDownloadInfoGetLinkFromDownload()
	{
		$mock = new MockHandler([
			new Response(200)
		]);

		$client = new Client([
			'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls/hello.txt');
		$download->setDestination($dest->getPathPrefix());

		$downloader = $this->getHttp(new Registry(), $client);
		$downloader->fetchDownloadInfo([
			$download
		]);

		$this->assertEmpty($download->getMessage());
		$this->assertEquals('hello.txt', $download->getFileName());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
	}

	public function testFetchDownloadInfoInvalidLink()
	{
		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink($this->scheme . '://kjguwjkasdlocalhostkasjk/jstwghbkaszhsjk');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = $this->getHttp(new Registry([
			'clearSession' => true
		]));

		$downloader->fetchDownloadInfo($downloads);

		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertCount(0, $dest->listContents());
	}

	public function testDownloadLink()
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
			'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$download->setHash(md5('hello'));

		$downloader = $this->getHttp(new Registry(), $client);
		$promises   = $downloader->download([
			$download
		]);
		Promise\unwrap($promises);

		$this->assertNotEmpty($promises);
		$this->assertEmpty($download->getMessage());
		$this->assertNotEmpty($download->getStartedAt());
		$this->assertNotEmpty($download->getFinishedAt());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $download->getState());

		$this->assertCount(1, $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.txt', $download->getFileName());
		}
	}

	public function testDownloadLinkSpeedLimit()
	{
		if (strpos(phpversion(), '-hhvm') !== false) {
			// https://github.com/facebook/hhvm/issues/6935
			$this->markTestSkipped('HHVM is not supporting the curl limit speed option!');
			return;
		}

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
			'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$download->setHash(md5('hello'));

		$downloader = $this->getHttp(new Registry([
			'speedlimit' => 10
		]), $client);
		$promises   = $downloader->download([
			$download
		]);
		Promise\unwrap($promises);

		$this->assertArrayHasKey('curl', (array)$mock->getLastOptions());
		$this->assertArrayHasKey(CURLOPT_MAX_RECV_SPEED_LARGE, (array)$mock->getLastOptions()['curl']);
		$this->assertEquals(10 * 1000, $mock->getLastOptions()['curl'][CURLOPT_MAX_RECV_SPEED_LARGE]);
	}

	public function testDownloadLinkInvalidHash()
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
			'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$download->setHash(md5('hello123'));
		$downloads[] = $download;

		$downloader = $this->getHttp(new Registry(), $client);
		$promises   = $downloader->download($downloads);
		Promise\unwrap($promises);

		$this->assertNotEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertEmpty($dest->listContents());
	}

	public function testDownloadLinkTmpFileName()
	{
		$mock = new MockHandler([
			new Response(200, [], 'hello')
		]);

		$client = new Client([
			'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setId(2);
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = $this->getHttp(new Registry(), $client);
		$promises   = $downloader->download($downloads);
		Promise\unwrap($promises);

		$this->assertNotEmpty($promises);
		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('tmp-' . $download->getId() . '.bin', $downloads[0]->getFileName());
		}
	}

	public function testDownloadLinkExistingFileName()
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
			'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$download->setFileName('unit-test.txt');
		$downloads[] = $download;

		$downloader = $this->getHttp(new Registry(), $client);
		$promises   = $downloader->download($downloads);
		Promise\unwrap($promises);

		$this->assertNotEmpty($promises);
		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('unit-test.txt', $downloads[0]->getFileName());
		}
	}

	public function testDownloadLinkWithCommandBus()
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
			'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = $this->getHttp(new Registry(), $client);
		$downloader->setCommandBus(
			$this->getMockCommandBus(
				[
					$this->callback(
						function (SaveDownloads $download) {
							return $download->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_COMPLETED;
						}
					)
				]
			)
		);
		$promises = $downloader->download($downloads);
		Promise\unwrap($promises);
	}

	public function testEmptyLink()
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
			'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = $this->getHttp(new Registry(), $client);
		$promises   = $downloader->download($downloads);

		$this->assertEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertCount(0, $dest->listContents());
	}

	public function testDownloadFailed()
	{
		$mock = new MockHandler([
			new RequestException('Failed on unit test', new Request('GET', 'test'))
		]);

		$client = new Client([
			'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = $this->getHttp(new Registry(), $client);
		$downloader->setCommandBus(
			$this->getMockCommandBus(
				[
					$this->callback(
						function (SaveDownloads $download) {
							return $download->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_ERROR;
						}
					)
				]
			)
		);
		$promises = $downloader->download($downloads);
		try {
			Promise\unwrap($promises);
		} catch (RequestException $e) {
			// Expected
		}

		$this->assertNotEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals('Failed on unit test', $downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertCount(0, $dest->listContents());
	}

	public function testInvalidDestination()
	{
		$downloads = [];
		$download  = new Download();
		$download->setLink($this->scheme . '://foo.bar/ldlsls');
		$download->setDestination('/invalid');
		$downloads[] = $download;

		$downloader = $this->getHttp(new Registry([
			'clearSession' => true
		]));

		$promises = $downloader->download($downloads);

		$this->assertEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$dest = new Local(__DIR__ . '/test');
		$this->assertCount(0, $dest->listContents());
	}

	public function testEmptyDownloads()
	{
		$client = new Client([
			'handler' => HandlerStack::create(new MockHandler([]))
		]);

		$downloader = $this->getHttp(new Registry(), $client);
		$promises   = $downloader->download([]);

		$this->assertEmpty($promises);

		$dest = new Local(__DIR__ . '/test');
		$this->assertCount(0, $dest->listContents());
	}

	public function testInvalidLogin()
	{
		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink($this->scheme . '://httptestinvalidlogin.org/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Httptestinvalidlogin(false);
		$promises   = $downloader->download($downloads);

		$this->assertEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertCount(0, $dest->listContents());
	}

	public function testExceptionOnLogin()
	{
		$client = new Client([
			'handler' => HandlerStack::create(new MockHandler([]))
		]);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download  = new Download();
		$download->setLink($this->scheme . '://httptestinvalidlogin.org/ldlsls');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Httptestinvalidlogin(true);
		$promises   = $downloader->download($downloads);

		$this->assertEmpty($promises);
		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertCount(0, $dest->listContents());
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

	/**
	 *
	 * @param Registry $config
	 * @param ClientInterface $client
	 * @return Http|Https
	 */
	private function getHttp(Registry $config, ClientInterface $client = null)
	{
		$class      = '\\Tartana\\Host\\Common\\' . ucfirst($this->scheme);
		$downloader = new $class($config, $client);
		return $downloader;
	}
}

class Httptestinvalidlogin extends Http
{

	private $throwException = false;

	public function __construct($throwException)
	{
		parent::__construct(new Registry(), new Client([
			'handler' => HandlerStack::create(new MockHandler([]))
		]));

		$this->throwException = $throwException;
	}

	protected function login()
	{
		if ($this->throwException) {
			throw new \Exception('Login failed');
		}
		return false;
	}
}
