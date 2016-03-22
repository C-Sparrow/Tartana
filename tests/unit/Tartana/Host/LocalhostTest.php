<?php
namespace Tests\Unit\Tartana\Host;
use GuzzleHttp\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
use Tartana\Entity\Download;
use Tartana\Host\Localhost;
use Tests\Unit\Tartana\TartanaBaseTestCase;
use League\Flysystem\MountManager;
use League\Flysystem\Filesystem;

class LocalhostTest extends TartanaBaseTestCase
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
		$this->assertEquals(md5_file(str_replace('file://localhost', '', $downloads[0]->getLink())), $downloads[0]->getHash());

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
			$download->setHash(md5_file($src->applyPathPrefix($file['path'])));
			$downloads[] = $download;
		}

		$downloader = new Localhost(new Registry());
		Promise\unwrap($downloader->download($downloads));

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

	public function testDownloadLinkInvalidHash ()
	{
		$src = new Local(__DIR__ . '/test');
		$dest = new Local(__DIR__ . '/test1');

		$downloads = [];
		foreach ($src->listContents() as $file)
		{
			$download = new Download();
			$download->setLink('file://localhost' . $src->applyPathPrefix($file['path']));
			$download->setDestination($dest->getPathPrefix());
			$download->setHash(123);
			$downloads[] = $download;
		}

		$downloader = new Localhost(new Registry());
		Promise\unwrap($downloader->download($downloads));

		$this->assertNotEmpty($downloads[0]->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[0]->getState());

		$this->assertEmpty($dest->listContents());
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
		Promise\unwrap($downloader->download($downloads));

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
		Promise\unwrap($downloader->download($downloads));

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
		Promise\unwrap($downloader->download($downloads));

		$this->assertFalse($dest->has('invalid.txt'));

		$failedDownload = $downloads[count($downloads) - 1];
		$this->assertNotEmpty($failedDownload->getMessage());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $failedDownload->getState());
	}

	public function testDownloadEmpty ()
	{
		$downloader = new Localhost();
		Promise\unwrap($downloader->download([]));

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
		Promise\unwrap($downloader->download($downloads));

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
		Promise\unwrap($downloader->download($downloads));

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
		Promise\unwrap($downloader->download($downloads));

		foreach ($downloads as $download)
		{
			$this->assertNotEmpty($download->getMessage());
			$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
		}
		$this->assertEmpty($dest->listContents());
	}

	public function testMountManager ()
	{
		$src = new Local(__DIR__ . '/test');
		$dest = new Local(__DIR__ . '/test1');

		$downloads = [];
		foreach ($src->listContents() as $key => $file)
		{
			$download = new Download();
			$download->setId($key);
			$download->setLink('file://localhost' . $src->applyPathPrefix($file['path']));
			$download->setDestination($dest->getPathPrefix());
			$downloads[] = $download;
		}

		$tests = [];
		foreach ($downloads as $d)
		{
			$tests[] = [
					$this->equalTo('src-' . $d->getId()),
					$this->callback(function  (Filesystem $f) {
						return $f->getAdapter() instanceof Local;
					})
			];
			$tests[] = [
					$this->equalTo('dst-' . $d->getId()),
					$this->callback(function  (Filesystem $f) {
						return $f->getAdapter() instanceof Local;
					})
			];
		}

		$manager = $this->getMockBuilder(MountManager::class)->getMock();
		$this->callWithConsecutive($manager->expects($this->exactly(2))
			->method('mountFilesystem'), $tests);
		$manager->expects($this->once())
			->method('copy')
			->willReturn(true);

		$downloader = new Localhost(new Registry(), $manager);
		Promise\unwrap($downloader->download($downloads));
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