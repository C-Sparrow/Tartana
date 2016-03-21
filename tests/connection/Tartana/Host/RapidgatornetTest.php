<?php
namespace Tests\Connection\Tartana\Host;
use GuzzleHttp\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Rapidgatornet;

class RapidgatornetTest extends \PHPUnit_Framework_TestCase
{

	public function itestFileInfo ()
	{
		if (! file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml'))
		{
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$downloader = new Rapidgatornet($config);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://rapidgator.net/file/0892171b3bebb4ab4fb5bfd26a33e2fb/symfony.png.html');
		$download->setDestination($dest->getPathPrefix());

		$downloader->fetchDownloadInfo([
				$download
		]);

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
		$this->assertEquals('symfony.png', $download->getFileName());
	}

	public function testDownload ()
	{
		if (! file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml'))
		{
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$downloader = new Rapidgatornet($config);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://rapidgator.net/file/0892171b3bebb4ab4fb5bfd26a33e2fb/symfony.png.html');
		$download->setDestination($dest->getPathPrefix());

		Promise\unwrap($downloader->download([
				$download
		]));

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $download->getState());

		$this->assertNotEmpty($dest->listContents());
		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('symfony.png', $file['path']);
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