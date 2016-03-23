<?php
namespace Tests\Connection\Tartana\Host\Common;
use GuzzleHttp\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Common\Http;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Tartana\Util;

class HttpTest extends \PHPUnit_Framework_TestCase
{

	public function testDownloadLinks ()
	{
		$downloader = new Http(new Registry());

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://c-sparrow.github.io/Tartana/doc/images/downloads-list.png');
		$download->setFileName('downloads-list.png');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		Promise\unwrap($downloader->download($downloads));

		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertNotEmpty($dest->listContents());
		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('downloads-list.png', $file['path']);
		}
	}

	public function testDownloadLargeLink ()
	{
		$this->markTestSkipped('Uncomment to test large downloads!');

		$downloader = new Http(new Registry([
				'speedlimit' => 10
		]));
		$logger = new Logger('test');
		$logger->setHandlers([
				new EchoHandler()
		]);
		$downloader->setLogger($logger);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://releases.ubuntu.com/14.04.4/ubuntu-14.04.3-desktop-amd64.iso');
		$download->setFileName('desktop.iso');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;
		$download = new Download();
		$download->setLink('http://releases.ubuntu.com/14.04.4/ubuntu-14.04.3-server-i386.iso');
		$download->setFileName('server.iso');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		Promise\unwrap($downloader->download($downloads));

		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertNotEmpty($dest->listContents());
		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('ubuntu-14.04.3-desktop-amd64.iso', $file['path']);
		}
	}

	protected function setUp ()
	{
		$fs = new Local(__DIR__ . '/');
		$fs->deleteDir('test');
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__ . '/');
		$fs->deleteDir('test');
	}
}

class EchoHandler extends AbstractProcessingHandler
{

	protected function write (array $record)
	{
		foreach ($record as $value)
		{
			if (is_string($value) && Util::startsWith($value, 'Progress'))
			{
				fwrite(STDERR, $value . PHP_EOL);
			}
		}
	}
}