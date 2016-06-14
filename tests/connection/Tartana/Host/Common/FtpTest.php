<?php
namespace Tests\Connection\Tartana\Host\Common;
use GuzzleHttp\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Common\Ftp;

class FtpTest extends \PHPUnit_Framework_TestCase
{

	public function testDownloadLinks ()
	{
		$downloader = new Ftp(new Registry());

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('ftp://mirrors.kernel.org/debian-cd/ls-lR.gz');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		Promise\unwrap($downloader->download($downloads));

		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertNotEmpty($dest->listContents());
		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$this->assertEquals('ls-lR.gz', $file['path']);
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