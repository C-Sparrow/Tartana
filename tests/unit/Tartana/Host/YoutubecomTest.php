<?php
namespace Tests\Unit\Tartana\Host;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Youtubecom;

class YoutubecomTest extends \PHPUnit_Framework_TestCase
{

	public function testFetchLinkList ()
	{
		$mock = new MockHandler(
				[
						new Response(200, [],
								'<div><a href="/watch?v=123&b=cde" class="pl-video-title-link"></a><a href="/watch?v=678&b=cde"></a></div>')
				]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$downloader = new Youtubecom(new Registry(), $client);
		$links = $downloader->fetchLinkList('https://www.youtube.com/playlist?list=abc');

		$this->assertCount(1, $links);
		$this->assertEquals('https://www.youtube.com/watch?v=123', $links[0]);
	}

	public function testFetchLinkListNoPlaylistLink ()
	{
		$mock = new MockHandler([]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$downloader = new Youtubecom(new Registry(), $client);
		$links = $downloader->fetchLinkList('https://www.youtube.com/watch?v=123');

		$this->assertCount(1, $links);
		$this->assertEquals('https://www.youtube.com/watch?v=123', $links[0]);
	}

	public function testFetchLinkListNoPath ()
	{
		$mock = new MockHandler([
				new Response(200, [], '<div><a href="/foo?v=123&b=cde" class="pl-video-title-link"></a></div>')
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$downloader = new Youtubecom(new Registry(), $client);
		$links = $downloader->fetchLinkList('https://www.youtube.com/playlist?list=abc');

		$this->assertEmpty($links);
	}

	public function testFetchDownloadInfo ()
	{
		$mock = new MockHandler([
				new Response(),
				new Response(200, [], 'title=hello')
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('https://www.youtube.com/watch?v=wXw6znXPfy4');
		$download->setDestination($dest->getPathPrefix());

		$downloader = new Youtubecom(new Registry(), $client);
		$downloader->fetchDownloadInfo([
				$download
		]);

		$this->assertEquals('hello.mp4', $download->getFileName());
		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
	}

	public function testFetchDownloadInfoWrongUrl ()
	{
		$mock = new MockHandler([
				new Response(),
				new Response(200, [], 'title=hello')
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$download = new Download();
		$download->setLink('https://www.youhube.com/watch?v=wXw6znXPfy4');
		$download->setDestination(__DIR__ . '/test');

		$downloader = new Youtubecom(new Registry(), $client);
		$downloader->fetchDownloadInfo([
				$download
		]);

		$this->assertEmpty($download->getFileName());
		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
	}

	public function testFetchDownloadInfoErrorCode ()
	{
		$mock = new MockHandler([
				new Response(),
				new Response(200, [], 'errorcode=150&reason=unit-test')
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$download = new Download();
		$download->setLink('https://www.youtube.com/watch?v=wXw6znXPfy4');
		$download->setDestination(__DIR__ . '/test');

		$downloader = new Youtubecom(new Registry(), $client);
		$downloader->fetchDownloadInfo([
				$download
		]);

		$this->assertEquals('unit-test', $download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
	}

	public function testFetchDownloadInfoException ()
	{
		$mock = new MockHandler([
				new Response(),
				new RequestException('Failed on unit test', new Request('GET', 'test'))
		]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$download = new Download();
		$download->setLink('https://www.youtube.com/watch?v=wXw6znXPfy4');
		$download->setDestination(__DIR__ . '/test');

		$downloader = new Youtubecom(new Registry(), $client);
		$downloader->fetchDownloadInfo([
				$download
		]);

		$this->assertNotEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
	}

	public function testDownloadLinks ()
	{
		$mock = new MockHandler(
				[
						new Response(),
						new Response(200, [], 'url_encoded_fmt_stream_map=' . urlencode('url=test')),
						new Response(200, [], 'hello')
				]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('https://www.youtube.com/watch?v=wXw6znXPfy4');
		$download->setDestination($dest->getPathPrefix());
		$download->setFileName('hello.mp4');

		$downloader = new Youtubecom(new Registry(), $client);
		Promise\unwrap($downloader->download([
				$download
		]));

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $download->getState());

		$this->assertCount(1, $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('hello', $dest->read($file['path'])['contents']);
			$this->assertEquals('hello.mp4', $download->getFileName());
		}
	}

	public function testDownloadLinksWrongResponse ()
	{
		$mock = new MockHandler(
				[
						new Response(),
						new Response(200, [], 'url_encoded_fmt_stream_map=' . urlencode('wrong=test')),
						new Response(200, [], 'hello')
				]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('https://www.youtube.com/watch?v=wXw6znXPfy4');
		$download->setDestination($dest->getPathPrefix());
		$download->setFileName('hello.mp4');

		$downloader = new Youtubecom(new Registry(), $client);
		Promise\unwrap($downloader->download([
				$download
		]));

		$this->assertNotEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());

		$this->assertEmpty($dest->listContents());
	}

	public function testDownloadLinksNoStreamMap ()
	{
		$mock = new MockHandler(
				[
						new Response(),
						new Response(200, [], 'hello=' . urlencode('url=test')),
						new Response(200, [], 'hello')
				]);

		$client = new Client([
				'handler' => HandlerStack::create($mock)
		]);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('https://www.youtube.com/watch?v=wXw6znXPfy4');
		$download->setDestination($dest->getPathPrefix());
		$download->setFileName('hello.mp4');

		$downloader = new Youtubecom(new Registry(), $client);
		Promise\unwrap($downloader->download([
				$download
		]));

		$this->assertNotEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());

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