<?php
namespace Tests\Connection\Tartana\Host;
use GuzzleHttp\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Uploadednet;

class UploadednetTest extends \PHPUnit_Framework_TestCase
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
		$downloader = new Uploadednet($config);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://uploaded.net/file/s8xowf0p');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		Promise\unwrap($downloader->download($downloads));
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