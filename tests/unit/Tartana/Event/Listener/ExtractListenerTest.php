<?php
namespace Tests\Unit\Tartana\Event\Listener;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\ProcessLinks;
use Tartana\Entity\Download;
use Tartana\Event\CommandEvent;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Event\ExtractCompletedEvent;
use Tartana\Event\Listener\ExtractListener;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class ExtractListenerTest extends TartanaBaseTestCase
{

	public function testHasFilesToProcess ()
	{
		$fs = new Local(__DIR__);
		foreach ($fs->listContents('../../Console/Command/Extract/rars/simple', false) as $rar)
		{
			if ($rar['type'] != 'file')
			{
				continue;
			}
			$fs->copy($rar['path'], str_replace('../../Console/Command/Extract/rars/simple/', 'test/', $rar['path']));
		}

		$dst = new Local(__DIR__ . '/test1');

		$runner = $this->getMockRunner(
				[
						[
								$this->callback(
										function  (Command $command) {
											return $command->getCommand() == 'php' && strpos($command, 'unrar') !== false;
										})
						]
				]);

		$download = new Download();
		$download->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]));
		$listener->onExtractDownloads($event);

		$this->assertEquals(Download::STATE_PROCESSING_STARTED, $download->getState());
		$this->assertEmpty($download->getMessage());
	}

	public function testHasMultipleFilesToProcess ()
	{
		$fs = new Local(__DIR__);
		foreach ($fs->listContents('../../Console/Command/Extract/rars/simple', false) as $rar)
		{
			if ($rar['type'] != 'file')
			{
				continue;
			}
			$fs->copy($rar['path'], str_replace('../../Console/Command/Extract/rars/simple/', 'test/', $rar['path']));
		}
		foreach ($fs->listContents('../../Console/Command/Extract/zips/simple', false) as $rar)
		{
			if ($rar['type'] != 'file')
			{
				continue;
			}
			$fs->copy($rar['path'], str_replace('../../Console/Command/Extract/zips/simple/', 'test/', $rar['path']));
		}

		$dst = new Local(__DIR__ . '/test1');

		$runner = $this->getMockRunner(
				[
						[
								$this->callback(
										function  (Command $command) {
											return $command->getCommand() == 'php' && strpos($command, 'unrar') !== false;
										})
						],
						[
								$this->callback(
										function  (Command $command) {
											return $command->getCommand() == 'php' && strpos($command, 'unzip') !== false;
										})
						]
				]);

		$download = new Download();
		$download->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]));
		$listener->onExtractDownloads($event);

		$this->assertEquals(Download::STATE_PROCESSING_STARTED, $download->getState());
		$this->assertEmpty($download->getMessage());
	}

	public function testHasDestination ()
	{
		$src = new Local(__DIR__ . '/test');
		$dst = new Local(__DIR__ . '/test1');
		$dst->createDir('test', new Config());

		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

		$download = new Download();
		$download->setState(Download::STATE_PROCESSING_NOT_STARTED);
		$download->setDestination(__DIR__ . '/test');
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]));
		$listener->onExtractDownloads($event);

		$this->assertEquals(Download::STATE_PROCESSING_ERROR, $download->getState());
		$this->assertNotEmpty($download->getMessage());
	}

	public function testHasWrongDownloadDestination ()
	{
		$src = new Local(__DIR__ . '/test');
		$src->createDir('test', new Config());
		$dst = new Local(__DIR__ . '/test1');

		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

		$download = new Download();
		$download->setDestination('/invalid');
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]));
		$listener->onExtractDownloads($event);

		$this->assertEquals(Download::STATE_PROCESSING_ERROR, $download->getState());
		$this->assertNotEmpty($download->getMessage());
	}

	public function testExtractEmpty ()
	{
		$dst = new Local(__DIR__ . '/test1');

		$download = new Download();
		$download->setDestination(__DIR__ . '/test');
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = new ExtractListener($this->getMockRunner(),
				new Registry([
						'extract' => [
								'destination' => $dst->getPathPrefix()
						]
				]));
		$listener->onExtractDownloads($event);

		$this->assertFalse($dst->has('test'));

		$this->assertEquals(Download::STATE_PROCESSING_ERROR, $download->getState());
		$this->assertNotEmpty($download->getMessage());
	}

	public function testExtractNoDownloads ()
	{
		$dst = new Local(__DIR__ . '/test1');

		$listener = new ExtractListener($this->getMockRunner(),
				new Registry([
						'extract' => [
								'destination' => $dst->getPathPrefix()
						]
				]));
		$listener->onExtractDownloads(new DownloadsCompletedEvent($this->getMockRepository([]), []));

		$fs = new Local(__DIR__);
		$this->assertFalse($fs->has('test'));
	}

	public function testPasswordFilePath ()
	{
		$fs = new Local(__DIR__);
		foreach ($fs->listContents('../../Console/Command/Extract/rars/password', false) as $rar)
		{
			if ($rar['type'] != 'file')
			{
				continue;
			}
			$fs->copy($rar['path'], str_replace('../../Console/Command/Extract/rars/password/', 'test/', $rar['path']));
		}

		$dst = new Local(__DIR__ . '/test1');

		$runner = $this->getMockRunner(
				[
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'pw.txt') !== false;
								})
						]
				]);

		$download = new Download();
		$download->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = new ExtractListener($runner,
				new Registry(
						[
								'extract' => [
										'destination' => $dst->getPathPrefix(),
										'passwordFile' => $fs->applyPathPrefix('test/pw.txt')
								]
						]));
		$listener->onExtractDownloads($event);
	}

	public function testPasswordFileRelativePath ()
	{
		$fs = new Local(__DIR__);
		foreach ($fs->listContents('../../Console/Command/Extract/rars/password', false) as $rar)
		{
			if ($rar['type'] != 'file')
			{
				continue;
			}
			$fs->copy($rar['path'], str_replace('../../Console/Command/Extract/rars/password/', 'test/', $rar['path']));
		}

		$dst = new Local(__DIR__ . '/test1');

		$runner = $this->getMockRunner(
				[
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'pw.txt') !== false;
								})
						]
				]);

		$download = new Download();
		$download->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = new ExtractListener($runner,
				new Registry(
						[
								'extract' => [
										'destination' => $dst->getPathPrefix(),
										'passwordFile' => str_replace(TARTANA_PATH_ROOT . '/', '', $fs->applyPathPrefix('test/pw.txt'))
								]
						]));
		$listener->onExtractDownloads($event);
	}

	public function testWrongPasswordFilePath ()
	{
		$fs = new Local(__DIR__);
		foreach ($fs->listContents('../../Console/Command/Extract/rars/password', false) as $rar)
		{
			if ($rar['type'] != 'file')
			{
				continue;
			}
			$fs->copy($rar['path'], str_replace('../../Console/Command/Extract/rars/password/', 'test/', $rar['path']));
		}

		$dst = new Local(__DIR__ . '/test1');

		$runner = $this->getMockRunner(
				[
						[
								$this->callback(
										function  (Command $command) {
											return strpos($command, 'invalid-password.txt') === false;
										})
						]
				]);

		$download = new Download();
		$download->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = new ExtractListener($runner,
				new Registry(
						[
								'extract' => [
										'destination' => $dst->getPathPrefix(),
										'passwordFile' => __DIR__ . '/invalid-password.txt'
								]
						]));
		$listener->onExtractDownloads($event);
	}

	public function testFinishedToExtract ()
	{
		$dst = new Local(__DIR__ . '/test');

		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]));
		$listener->onExtractCompleted(new ExtractCompletedEvent($dst, $dst, true));
	}

	public function testHasErrors ()
	{
		$dst = new Local(__DIR__ . '/test1');

		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]));
		$listener->onExtractCompleted(new ExtractCompletedEvent($dst, $dst, false));
	}

	public function testExtractEncapsulatedRarFiles ()
	{
		$fs = new Local(__DIR__);
		foreach ($fs->listContents('../../Console/Command/Extract/rars/simple', false) as $rar)
		{
			if ($rar['type'] != 'file')
			{
				continue;
			}
			$fs->copy($rar['path'], str_replace('../../Console/Command/Extract/rars/simple/', 'test/', $rar['path']));
		}

		$dst = new Local(__DIR__ . '/test');

		$runner = $this->getMockRunner(
				[
						[
								$this->callback(
										function  (Command $command) {
											return $command->getCommand() == 'php' && strpos($command, 'unrar') !== false;
										})
						]
				]);

		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]));
		$listener->onExtractCompleted(new ExtractCompletedEvent($dst, $dst, true));
	}

	public function testCleanUpDirectory ()
	{
		$dst = new Local(__DIR__ . '/test1');
		$dst->createDir('test', new Config());

		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]));

		$downloads = [
				new Download()
		];
		$downloads[0]->setDestination(__DIR__ . '/test');

		$repository = $this->getMockRepository();
		$repository->expects($this->once())
			->method('findDownloads')
			->willReturn($downloads)
			->with($this->equalTo([
				Download::STATE_DOWNLOADING_NOT_STARTED,
				Download::STATE_DOWNLOADING_COMPLETED
		]));
		$listener->onChangeDownloadStateAfter(
				new CommandEvent(new ChangeDownloadState($repository, Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_COMPLETED)));
	}

	public function testCleanUpDirectoryDestinationHasNoDirectory ()
	{
		$dst = new Local(__DIR__ . '/test1');

		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]));

		$downloads = [
				new Download()
		];
		$downloads[0]->setDestination(__DIR__ . '/test');

		$repository = $this->getMockRepository();
		$repository->expects($this->once())
			->method('findDownloads')
			->willReturn($downloads)
			->with($this->equalTo([
				Download::STATE_DOWNLOADING_NOT_STARTED,
				Download::STATE_DOWNLOADING_COMPLETED
		]));
		$listener->onChangeDownloadStateAfter(
				new CommandEvent(new ChangeDownloadState($repository, Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_COMPLETED)));
	}

	public function testCleanUpDirectoryWrongState ()
	{
		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => __DIR__ . '/test'
				]
		]));

		$repository = $this->getMockRepository();
		$repository->expects($this->never())
			->method('findDownloads');
		$listener->onChangeDownloadStateAfter(
				new CommandEvent(new ChangeDownloadState($repository, Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_STARTED)));
	}

	public function testCleanUpDirectoryWrongDestination ()
	{
		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => __DIR__ . '/test'
				]
		]));

		$repository = $this->getMockRepository();
		$repository->expects($this->never())
			->method('findDownloads');
		$listener->onChangeDownloadStateAfter(
				new CommandEvent(new ChangeDownloadState($repository, Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_COMPLETED)));
	}

	public function testCleanUpDirectoryNoDownloads ()
	{
		$dst = new Local(__DIR__ . '/test');

		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]));

		$repository = $this->getMockRepository([]);
		$repository->expects($this->once())
			->method('findDownloads')
			->with($this->equalTo([
				Download::STATE_DOWNLOADING_NOT_STARTED,
				Download::STATE_DOWNLOADING_COMPLETED
		]));
		$listener->onChangeDownloadStateAfter(
				new CommandEvent(new ChangeDownloadState($repository, Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_COMPLETED)));
	}

	public function testCleanUpDirectoryWrongEvent ()
	{
		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

		$listener = new ExtractListener($runner, new Registry([
				'extract' => [
						'destination' => __DIR__ . '/test'
				]
		]));

		$listener->onChangeDownloadStateAfter(new CommandEvent(new ProcessLinks([])));
	}

	protected function setUp ()
	{
		$fs = new Local(__DIR__ . '/');
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}
}
