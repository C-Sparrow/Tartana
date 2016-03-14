<?php
namespace Tests\Unit\Tartana\Host;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use Tartana\Entity\Download;
use Tartana\Host\Localhost;

class LocalhostTest extends \PHPUnit_Framework_TestCase
{

	public function testFetchDownloadInfo ()
	{
		$src = new Local(__DIR__ . '/test');
		$dest = new Local(__DIR__ . '/test1');

		$downloads = [];
		foreach ($src->listContents() as $file)
		{
			$download = new Download();
			$download->setLink('file://localhost' . $src->applyPathPrefix($file['path']));
			$download->setDestination($dest->getPathPrefix());
			$downloads[] = $download;
		}

		$downloader = new Localhost(new Registry());
		$downloader->fetchDownloadInfo($downloads);

		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $src->listContents());
		foreach ($src->listContents() as $file)
		{
			$contains = false;
			foreach ($downloads as $d)
			{
				if ($file['path'] == $d->getFileName())
				{
					$contains = true;
				}
			}
			$this->assertTrue($contains);
		}
	}

	public function testFetchDownloadInfoInvalidLink ()
	{
		$download = new Download();
		$download->setLink('file://localhost/invalid');
		$download->setDestination(__DIR__ . '/test');

		$downloader = new Localhost(new Registry());
		$downloader->fetchDownloadInfo([
				$download
		]);

		$this->assertNotEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
	}

	public function testFetchDownloadInfoFileNameSet ()
	{
		$download = new Download();
		$download->setLink('file://localhost/invalid');
		$download->setDestination(__DIR__ . '/test');
		$download->setFileName('hello.txt');

		$downloader = new Localhost(new Registry());
		$downloader->fetchDownloadInfo([
				$download
		]);

		$this->assertEquals('hello.txt', $download->getFileName());
		$this->assertEmpty($download->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
	}

	public function testDownloadLinks ()
	{
		$src = new Local(__DIR__ . '/test');
		$dest = new Local(__DIR__ . '/test1');

		$downloads = [];
		foreach ($src->listContents() as $file)
		{
			$download = new Download();
			$download->setLink('file://localhost' . $src->applyPathPrefix($file['path']));
			$download->setDestination($dest->getPathPrefix());
			$downloads[] = $download;
		}

		$downloader = new Localhost(new Registry());
		$downloader->download($downloads);

		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$contains = false;
			foreach ($downloads as $d)
			{
				if ('file://localhost' . str_replace('/test1/', '/test/', $dest->applyPathPrefix($file['path'])) == $d->getLink())
				{
					$contains = true;
				}
			}
			$this->assertTrue($contains);
		}
	}

	public function testDownloadLinksNewFileName ()
	{
		$src = new Local(__DIR__ . '/test');
		$dest = new Local(__DIR__ . '/test1');

		$downloads = [];
		foreach ($src->listContents() as $file)
		{
			$download = new Download();
			$download->setLink('file://localhost' . $src->applyPathPrefix($file['path']));
			$download->setDestination($dest->getPathPrefix());
			$download->setFileName('test-' . $file['path']);
			$downloads[] = $download;
		}

		$downloader = new Localhost(new Registry());
		$downloader->download($downloads);

		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$contains = false;
			foreach ($downloads as $d)
			{
				if ($file['path'] == $d->getFileName())
				{
					$contains = true;
				}
			}
			$this->assertTrue($contains);
		}
	}

	public function testDownloadRelativeLinks ()
	{
		$src = new Local(__DIR__ . '/test');
		$dest = new Local(__DIR__ . '/test1');

		$downloads = [];
		foreach ($src->listContents() as $file)
		{
			$download = new Download();
			$download->setLink('file://localhost' . str_replace(TARTANA_PATH_ROOT, '', $src->applyPathPrefix($file['path'])));
			$download->setDestination($dest->getPathPrefix());
			$downloads[] = $download;
		}

		$downloader = new Localhost(new Registry());
		$downloader->download($downloads);

		$this->assertEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());

		$this->assertCount(count($downloads), $dest->listContents());
		foreach ($dest->listContents() as $file)
		{
			$contains = false;
			foreach ($downloads as $d)
			{
				if ('file://localhost' . str_replace('/test1/', '/test/', str_replace(TARTANA_PATH_ROOT, '', $dest->applyPathPrefix($file['path']))) ==
						 $d->getLink())
				{
					$contains = true;
				}
			}
			$this->assertTrue($contains);
		}
	}

	public function testDownloadInvalidLinks ()
	{
		$src = new Local(__DIR__ . '/test');
		$dest = new Local(__DIR__ . '/test1');

		$downloads = [];
		foreach ($src->listContents() as $file)
		{
			$download = new Download();
			$download->setLink('file://localhost' . $src->applyPathPrefix($file['path']));
			$download->setDestination($dest->getPathPrefix());
			$downloads[] = $download;
		}
		$download = new Download();
		$download->setLink('file://localhost' . $src->applyPathPrefix('invalid.txt'));
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Localhost(new Registry());
		$downloader->download($downloads);

		$this->assertFalse($dest->has('invalid.txt'));

		$failedDownload = $downloads[count($downloads) - 1];
		$this->assertNotEmpty($failedDownload->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $failedDownload->getState());
	}

	public function testDownloadEmpty ()
	{
		$downloader = new Localhost();
		$downloader->download([]);

		$fs = new Local(__DIR__ . '/test1');
		$this->assertEmpty($fs->listContents());
	}

	public function testDownloadInvalidDestination ()
	{
		$src = new Local(__DIR__ . '/test');
		$dest = new Local(__DIR__ . '/test1');

		$downloads = [];
		foreach ($src->listContents() as $file)
		{
			$download = new Download();
			$download->setLink('file://localhost' . $src->applyPathPrefix($file['path']));
			$download->setDestination('/invalid');
			$downloads[] = $download;
		}

		$downloader = new Localhost(new Registry());
		$downloader->download($downloads);

		foreach ($downloads as $download)
		{
			$this->assertNotEmpty($download->getMessage());
			$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
		}
		$this->assertEmpty($dest->listContents());
	}

	public function testDownloadNoPermission ()
	{
		$src = new Local(__DIR__ . '/test');
		$dest = new Local(__DIR__ . '/test1', LOCK_EX, Local::DISALLOW_LINKS, [
				'dir' => [
						'private' => 0400
				]
		]);
		$dest->setVisibility('', AdapterInterface::VISIBILITY_PRIVATE);

		$downloads = [];
		foreach ($src->listContents() as $file)
		{
			$download = new Download();
			$download->setLink('file://localhost' . $src->applyPathPrefix($file['path']));
			$download->setDestination($dest->getPathPrefix());
			$downloads[] = $download;
		}

		$downloader = new Localhost(new Registry());
		$downloader->download($downloads);

		foreach ($downloads as $download)
		{
			$this->assertNotEmpty($download->getMessage());
			$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
		}
		$this->assertEmpty($dest->listContents());
	}

	public function testDownloadNoPath ()
	{
		$src = new Local(__DIR__ . '/test');
		$dest = new Local(__DIR__ . '/test1');

		$downloads = [];
		$download = new Download();
		$download->setLink('file://localhost');
		$download->setDestination($dest->getPathPrefix());
		$downloads[] = $download;

		$downloader = new Localhost(new Registry());
		$downloader->download($downloads);

		foreach ($downloads as $download)
		{
			$this->assertNotEmpty($download->getMessage());
			$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
		}
		$this->assertEmpty($dest->listContents());
	}

	protected function setUp ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
		$fs->deleteDir('test1');
		foreach ($fs->listContents('files', false) as $rar)
		{
			$fs->copy($rar['path'], str_replace('files/', 'test/', $rar['path']));
		}
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
		$fs->deleteDir('test1');
	}
}