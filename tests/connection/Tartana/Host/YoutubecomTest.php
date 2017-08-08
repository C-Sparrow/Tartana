<?php
namespace Tests\Connection\Tartana\Host;

use GuzzleHttp\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Youtubecom;

class YoutubecomTest extends \PHPUnit_Framework_TestCase
{

	public function testFetchLinkList()
	{
		$downloader = new Youtubecom(new Registry());
		$links      = $downloader->fetchLinkList('https://www.youtube.com/playlist?list=PL5DF954DB82987243');

		$this->assertCount(19, $links);
		$this->assertStringStartsWith('https://www.youtube.com/watch', $links[0]);
	}

	public function testFetchDownloadInfo()
	{
		$downloader = new Youtubecom(new Registry());

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('https://www.youtube.com/watch?v=wXw6znXPfy4');
		$download->setDestination($dest->getPathPrefix());

		$downloader->fetchDownloadInfo([
			$download
		]);

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
		$this->assertEquals('Senor Chang.mp4', $download->getFileName());
	}

	public function testDownloadLinks()
	{
		$downloader = new Youtubecom(new Registry());

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('https://www.youtube.com/watch?v=wXw6znXPfy4');
		$download->setDestination($dest->getPathPrefix());
		$download->setFileName('test.mp4');

		Promise\unwrap($downloader->download([
			$download
		]));

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $download->getState());

		$this->assertNotEmpty($dest->listContents());
		$this->assertCount(1, $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('test.mp4', $file['path']);
		}
	}

	public function testDownloadLinksEmbed()
	{
		$downloader = new Youtubecom(new Registry());

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('https://www.youtube.com/embed/wXw6znXPfy4');
		$download->setDestination($dest->getPathPrefix());
		$download->setFileName('test.mp4');

		Promise\unwrap($downloader->download([
			$download
		]));

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $download->getState());

		$this->assertNotEmpty($dest->listContents());
		$this->assertCount(1, $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('test.mp4', $file['path']);
		}
	}

	public function testDownloadLinksRestricted()
	{
		$downloader = new Youtubecom(new Registry());

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('https://www.youtube.com/watch?v=LAeUtlcETfc');
		$download->setDestination($dest->getPathPrefix());
		$download->setFileName('test.mp4');

		Promise\unwrap($downloader->download([
			$download
		]));

		$this->assertNotEmpty($dest->listContents());
		$this->assertCount(1, $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('test.mp4', $file['path']);
		}
	}

	protected function setUp()
	{
		$fs = new Local(__DIR__ . '/');
		$fs->deleteDir('test');
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__ . '/');
		$fs->deleteDir('test');
	}
}
