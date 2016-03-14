<?php
namespace Tests\Unit\Local\Handler;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Local\Handler\LocalProcessLinksHandler;
use Tartana\Domain\Command\ProcessLinks;
use Tartana\Entity\Download;
use Tartana\Host\HostInterface;
use Tartana\Util;
use Tests\Unit\Local\LocalBaseTestCase;

class LocalProcessLinksHandlerTest extends LocalBaseTestCase
{

	public function testProcessLinks ()
	{
		$entityManager = $this->getMockEntityManager(
				[
						$this->callback(function  (Download $download) {
							return $download->getLink() == 'http://foo.bar/kjashd';
						}),
						$this->callback(function  (Download $download) {
							return $download->getLink() == 'http://bar.foo/uzwhka';
						})
				]);

		$fs = new Local(__DIR__ . '/test');
		$handler = new LocalProcessLinksHandler(new Registry([
				'downloads' => $fs->getPathPrefix()
		]), $entityManager);
		$handler->handle(new ProcessLinks([
				'http://foo.bar/kjashd',
				'http://bar.foo/uzwhka'
		]));

		$this->assertNotEmpty($fs->listContents());
		$this->assertCount(1, $fs->listContents());
		$this->assertStringStartsWith('job-', $fs->listContents()[0]['path']);
	}

	public function testProcessLinksExisting ()
	{
		$download = new Download();
		$download->setLink('http://bar.foo/uzwhka');
		$entityManager = $this->getMockEntityManager(
				[
						$this->callback(function  (Download $download) {
							return $download->getLink() == 'http://foo.bar/kjashd';
						})
				], 1, [
						$download
				]);

		$fs = new Local(__DIR__ . '/test');
		$handler = new LocalProcessLinksHandler(new Registry([
				'downloads' => $fs->getPathPrefix()
		]), $entityManager);
		$handler->handle(new ProcessLinks([
				'http://foo.bar/kjashd',
				'http://bar.foo/uzwhka'
		]));

		$this->assertNotEmpty($fs->listContents());
		$this->assertCount(1, $fs->listContents());
		$this->assertStringStartsWith('job-', $fs->listContents()[0]['path']);
	}

	public function testProcessLinksAllExisting ()
	{
		$download1 = new Download();
		$download1->setLink('http://foo.bar/kjashd');
		$download2 = new Download();
		$download2->setLink('http://bar.foo/uzwhka');
		$entityManager = $this->getMockEntityManager([], 0, [
				$download1,
				$download2
		]);

		$fs = new Local(__DIR__ . '/test');
		$handler = new LocalProcessLinksHandler(new Registry([
				'downloads' => $fs->getPathPrefix()
		]), $entityManager);
		$handler->handle(new ProcessLinks([
				'http://foo.bar/kjashd',
				'http://bar.foo/uzwhka'
		]));

		$this->assertEmpty($fs->listContents());
	}

	public function testProcessLinksDirectoryExists ()
	{
		$entityManager = $this->getMockEntityManager(
				[
						$this->callback(function  (Download $download) {
							return $download->getLink() == 'http://foo.bar/kjashd';
						}),
						$this->callback(function  (Download $download) {
							return $download->getLink() == 'http://bar.foo/uzwhka';
						})
				]);

		$fs = new Local(__DIR__ . '/test');
		$handler = new LocalProcessLinksHandler(new Registry([

				'downloads' => $fs->getPathPrefix()
		]), $entityManager);
		for ($i = 0; $i < 5; $i ++)
		{
			$fs->createDir('job-' . date('YmdHis', time() + $i) . '-1', new Config());
		}

		$existingDirectories = $fs->listContents();

		$handler->handle(new ProcessLinks([
				'http://foo.bar/kjashd',
				'http://bar.foo/uzwhka'
		]));

		$this->assertNotEmpty($fs->listContents());
		$this->assertCount(count($existingDirectories) + 1, $fs->listContents());

		$hasNewNumber = false;
		foreach ($fs->listContents() as $dir)
		{
			if (Util::endsWith($dir['path'], '-2'))
			{
				$hasNewNumber = true;
				break;
			}
		}
		$this->assertTrue($hasNewNumber);
	}

	public function testProcessLinksInvalidDirectory ()
	{
		$entityManager = $this->getMockEntityManager([], 0);

		$handler = new LocalProcessLinksHandler(new Registry([
				'downloads' => __DIR__ . '/invalid-dir'
		]), $entityManager);
		$handler->handle(new ProcessLinks([
				'http://foo.bar/kjashd',
				'http://bar.foo/uzwhka'
		]));

		$fs = new Local(__DIR__);
		foreach ($fs->listContents() as $file)
		{
			$this->assertNotEquals('dir', $file['type']);
		}
	}

	public function testProcessLinksEmptyDirectory ()
	{
		$entityManager = $this->getMockEntityManager([], 0);

		$handler = new LocalProcessLinksHandler(new Registry([
				'downloads' => ''
		]), $entityManager);
		$handler->handle(new ProcessLinks([
				'http://foo.bar/kjashd',
				'http://bar.foo/uzwhka'
		]));

		$fs = new Local(__DIR__);
		foreach ($fs->listContents() as $file)
		{
			$this->assertNotEquals('dir', $file['type']);
		}
	}

	public function testProcessLinksWithFactory ()
	{
		$entityManager = $this->getMockEntityManager(
				[
						$this->callback(function  (Download $download) {
							return ! $download->getFileName();
						})
				]);

		$fs = new Local(__DIR__ . '/test');
		$handler = new LocalProcessLinksHandler(new Registry([
				'downloads' => $fs->getPathPrefix()
		]), $entityManager);
		$handler->setHostFactory($this->getMockHostFactory(null));
		$handler->handle(new ProcessLinks([
				'http://bar.foo/uzwhka'
		]));

		$this->assertNotEmpty($fs->listContents());
		$this->assertCount(1, $fs->listContents());
		$this->assertStringStartsWith('job-', $fs->listContents()[0]['path']);
	}

	public function testProcessLinksWithFactoryWithHost ()
	{
		$entityManager = $this->getMockEntityManager(
				[
						$this->callback(function  (Download $download) {
							return ! $download->getFileName();
						})
				]);
		$host = $this->getMockBuilder(HostInterface::class)->getMock();
		$host->expects($this->once())
			->method('fetchDownloadInfo');

		$fs = new Local(__DIR__ . '/test');
		$handler = new LocalProcessLinksHandler(new Registry([
				'downloads' => $fs->getPathPrefix()
		]), $entityManager);
		$handler->setHostFactory($this->getMockHostFactory($host));
		$handler->handle(new ProcessLinks([
				'http://bar.foo/uzwhka'
		]));

		$this->assertNotEmpty($fs->listContents());
		$this->assertCount(1, $fs->listContents());
		$this->assertStringStartsWith('job-', $fs->listContents()[0]['path']);
	}

	public function testCreateJobDirFullPath ()
	{
		$fs = new Local(__DIR__ . '/test');
		$dir = LocalProcessLinksHandler::createJobDir($fs->getPathPrefix(), true);

		$this->assertContains($fs->getPathPrefix(), $dir);
		$this->assertStringStartsWith('job-', $fs->removePathPrefix($dir));
	}

	public function testCreateJobDirRelativePath ()
	{
		$fs = new Local(__DIR__ . '/test');
		$dir = LocalProcessLinksHandler::createJobDir($fs->getPathPrefix(), false);

		$this->assertTrue($fs->has($dir));
		$this->assertStringStartsWith('job-', $dir);
	}

	public function testCreateJobDirInvalidPath ()
	{
		$dir = LocalProcessLinksHandler::createJobDir(__DIR__ . '/invalid', false);

		$fs = new Local(__DIR__);

		$this->assertEmpty($dir);
		$this->assertFalse($fs->has('invalid'));
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test/');
	}
}