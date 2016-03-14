<?php
namespace Tests\Connection\Tartana\Host;
use GuzzleHttp\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Shareonlinebiz;

class ShareonlinebizTest extends \PHPUnit_Framework_TestCase
{

	public function testDownloadLinks ()
	{
		if (! file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml'))
		{
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$downloader = new Shareonlinebiz($config);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://www.share-online.biz/dl/EG5BWT3OO27');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		Promise\unwrap($downloader->download($downloads));

		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertNotEmpty($dest->listContents());
		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('symfony.png', $file['path']);
		}
	}

	public function testDownloadInvalidLinks ()
	{
		if (! file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml'))
		{
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$downloader = new Shareonlinebiz($config);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://www.share-online.biz/dl/invalid');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());
		$this->assertEmpty($dest->listContents());
	}

	public function testDownloadEmpty ()
	{
		if (! file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml'))
		{
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$downloader = new Shareonlinebiz($config);

		$dest = new Local(__DIR__ . '/test');
		$promises = $downloader->download([]);

		$this->assertEmpty($failed);
		$this->assertEmpty($dest->listContents());
	}

	public function testDownloadInvalidCredentials ()
	{
		if (! file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml'))
		{
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$config->set('shareonlinebiz.username', 'invalid');
		$config->set('clearSession', true);
		$downloader = new Shareonlinebiz($config);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://www.share-online.biz/dl/O43A391OMJ');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());
		$this->assertEmpty($dest->listContents());
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