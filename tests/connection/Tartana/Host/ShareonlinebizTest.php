<?php
namespace Tests\Connection\Tartana\Host;

use GuzzleHttp\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Shareonlinebiz;

class ShareonlinebizTest extends \PHPUnit_Framework_TestCase
{

	public function testFetchDownloadInfo()
	{
		if (!file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml')) {
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$downloader = new Shareonlinebiz($config);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://www.share-online.biz/dl/EG5BWT3OO27');
		$download->setDestination($dest->getPathPrefix());

		$downloader->fetchDownloadInfo([
			$download
		]);

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
		$this->assertEquals('symfony.png', $download->getFileName());
	}

	public function testFetchDownloadInfoDeleted()
	{
		if (!file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml')) {
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$downloader = new Shareonlinebiz($config);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://www.share-online.biz/dl/Z2ZG544OHIS');
		$download->setDestination($dest->getPathPrefix());

		$downloader->fetchDownloadInfo([
			$download
		]);

		$this->assertNotEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
	}

	public function testDownloadLinks()
	{
		if (!file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml')) {
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$downloader = new Shareonlinebiz($config);

		$dest = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setLink('http://www.share-online.biz/dl/EG5BWT3OO27');
		$download->setDestination($dest->getPathPrefix());

		$downloader->fetchDownloadInfo([
			$download
		]);

		Promise\unwrap($downloader->download([
			$download
		]));

		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $download->getState());

		$this->assertNotEmpty($dest->listContents());
		$this->assertCount(1, $dest->listContents());
		foreach ($dest->listContents() as $file) {
			$this->assertEquals('symfony.png', $file['path']);
			$this->assertEquals(md5_file($dest->applyPathPrefix($file['path'])), $download->getHash());
		}
	}

	public function testDownloadInvalidLinks()
	{
		if (!file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml')) {
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

	public function testDownloadEmpty()
	{
		if (!file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml')) {
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$downloader = new Shareonlinebiz($config);

		$dest = new Local(__DIR__ . '/test');
		$promises = $downloader->download([]);

		$this->assertEmpty($promises);
		$this->assertEmpty($dest->listContents());
	}

	public function testDownloadInvalidCredentials()
	{
		if (!file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml')) {
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


	public function testDownloadMultiplLinks()
	{
		if (!file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml')) {
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
		$download->setFileName('test1.png');
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test2.png');
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test3.png');
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test4.png');
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test5.png');
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test6.png');
		$downloads[] = $download;

		Promise\unwrap($downloader->download(
			$downloads
		));

		foreach ($downloads as $d) {
			$this->assertEmpty($d->getMessage(), $d->getMessage());
			$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $d->getState());
		}

		$this->assertNotEmpty($dest->listContents());
		$this->assertEquals(count($downloads), count($dest->listContents()));
		foreach ($dest->listContents() as $file) {
			$this->assertContains('test', $file['path']);
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
