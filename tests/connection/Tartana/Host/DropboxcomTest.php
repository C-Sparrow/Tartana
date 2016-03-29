<?php
namespace Tests\Connection\Tartana\Host;
use GuzzleHttp\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Entity\Download;
use Tartana\Host\Dropboxcom;
use GuzzleHttp\Exception\RequestException;

class DropboxcomTest extends \PHPUnit_Framework_TestCase
{

	public function testDownloadLinksUnauthorized ()
	{
		$downloader = new Dropboxcom(new Registry());

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('https://www.dropbox.com/s/81x9v3synhamu1o/symfony.png?dl=0');
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

	public function testDownloadLinksAuthorized ()
	{
		if (! file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml'))
		{
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$downloader = new Dropboxcom($config);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('https://www.dropbox.com/s/81x9v3synhamu1o/symfony.png?dl=0');
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

	public function testDownloadInvalidLinksAuthorized ()
	{
		if (! file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml'))
		{
			$this->markTestSkipped('No credentials found for host');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		$downloader = new Dropboxcom($config);

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('https://www.dropbox.com/s/123/invalid.txt?dl=0');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$this->setExpectedException(RequestException::class);
		Promise\unwrap($downloader->download($downloads));
	}

	public function testDownloadWrongAccessToken ()
	{
		$downloader = new Dropboxcom(new Registry([
				'dropboxcom' => [
						'token' => '123'
				]
		]));

		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('https://www.dropbox.com/s/81x9v3synhamu1o/symfony.png?dl=0');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$this->setExpectedException(RequestException::class);
		Promise\unwrap($downloader->download($downloads));
	}

	protected function setUp ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		@$fs->deleteDir('test');
	}
}