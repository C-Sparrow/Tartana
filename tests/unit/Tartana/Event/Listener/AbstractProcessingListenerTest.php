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
use Tartana\Event\Listener\AbstractProcessingListener;
use Tartana\Event\ProcessingCompletedEvent;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class AbstractProcessingListenerTest extends TartanaBaseTestCase
{

	public function testHasFilesToProcess()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.txt', 'unit test', new Config());
		$fs->createDir('test1', new Config());

		$runner = $this->getMockRunner(
				[
						$this->callback(
								function (Command $command) use ($fs)
								{
									return $command->getCommand() == 'php' && strpos($command, 'unit') !== false &&
											 strpos($command, $fs->applyPathPrefix('test')) !== false &&
											 strpos($command, $fs->applyPathPrefix('test1')) !== false;
								})
				]);

		$download = new Download();
		$download->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = $this->getMockListener($runner);
		$listener->onProcessCompletedDownloads($event);

		$this->assertEquals(Download::STATE_PROCESSING_STARTED, $download->getState());
		$this->assertEmpty($download->getMessage());
	}

	public function testHasFilesToProcessMultipart()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.txt.001', 'unit test 1', new Config());
		$fs->write('test/test.txt.002', 'unit test 2', new Config());
		$fs->createDir('test1', new Config());

		$runner = $this->getMockRunner(
				[
						$this->callback(
								function (Command $command) use ($fs)
								{
									return $command->getCommand() == 'php' && strpos($command, 'unit') !== false &&
											 strpos($command, $fs->applyPathPrefix('test')) !== false &&
											 strpos($command, $fs->applyPathPrefix('test1')) !== false;
								})
				]);

		$download = new Download();
		$download->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = $this->getMockListener($runner);
		$listener->onProcessCompletedDownloads($event);

		$this->assertEquals(Download::STATE_PROCESSING_STARTED, $download->getState());
		$this->assertEmpty($download->getMessage());
	}

	public function testHasMultipleFilesToProcess()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.txt', 'unit test', new Config());
		$fs->write('test/test.csv', 'unit test csv', new Config());
		$fs->createDir('test1', new Config());

		$runner = $this->getMockRunner(
				[
						$this->callback(
								function (Command $command) use ($fs)
								{
									return $command->getCommand() == 'php' && strpos($command, 'foo') !== false &&
											 strpos($command, $fs->applyPathPrefix('test')) !== false &&
											 strpos($command, $fs->applyPathPrefix('test1')) !== false;
								}),
						$this->callback(
								function (Command $command) use ($fs)
								{
									return $command->getCommand() == 'php' && strpos($command, 'bar') !== false &&
											 strpos($command, $fs->applyPathPrefix('test')) !== false &&
											 strpos($command, $fs->applyPathPrefix('test1')) !== false;
								})
				]);

		$download = new Download();
		$download->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = $this->getMockListener($runner, 1, [
				'txt' => 'foo',
				'csv' => 'bar'
		]);
		$listener->onProcessCompletedDownloads($event);

		$this->assertEquals(Download::STATE_PROCESSING_STARTED, $download->getState());
		$this->assertEmpty($download->getMessage());
	}

	public function testHasDestination()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.txt', 'unit test', new Config());
		$fs->createDir('test1/test', new Config());

		$download = new Download();
		$download->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = $this->getMockListener($this->getMockRunner(), 1, []);
		$listener->onProcessCompletedDownloads($event);

		$this->assertEquals(Download::STATE_PROCESSING_ERROR, $download->getState());
		$this->assertNotEmpty($download->getMessage());
	}

	public function testHasWrongDownloadDestination()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.txt', 'unit test', new Config());
		$fs->createDir('test1/test', new Config());

		$download = new Download();
		$download->setDestination(__DIR__ . '/invalid');
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = $this->getMockListener($this->getMockRunner(), 1, []);
		$listener->onProcessCompletedDownloads($event);

		$this->assertEquals(Download::STATE_PROCESSING_ERROR, $download->getState());
		$this->assertNotEmpty($download->getMessage());
	}

	public function testExtractNoDownloads()
	{
		$event = new DownloadsCompletedEvent($this->getMockRepository(), []);
		$listener = $this->getMockListener($this->getMockRunner(), 1, []);
		$listener->onProcessCompletedDownloads($event);
	}

	public function testFinishedToExtract()
	{
		$fs = new Local(__DIR__ . '/test');
		$fs->write('test.txt', 'unit test', new Config());
		$fs->write('test1.txt', 'unit test1', new Config());

		$runner = $this->getMockRunner(
				[
						$this->callback(
								function (Command $command) use ($fs)
								{
									return $command->getCommand() == 'php' && strpos($command, 'unit') !== false &&
											 substr_count($command, $fs->getPathPrefix()) == 2;
								})
				]);

		$listener = $this->getMockListener($runner, 0);
		$listener->onProcessingCompleted(new ProcessingCompletedEvent($fs, $fs, true));
	}

	public function testFinishedToExtractWrongFileExtension()
	{
		$fs = new Local(__DIR__ . '/test');
		$fs->write('test.csv', 'unit test', new Config());

		$listener = $this->getMockListener($this->getMockRunner(), 0);
		$listener->onProcessingCompleted(new ProcessingCompletedEvent($fs, $fs, true));
	}

	public function testFinishedWithError()
	{
		$fs = new Local(__DIR__ . '/test');
		$fs->write('test.txt', 'unit test', new Config());

		$listener = $this->getMockListener($this->getMockRunner(), 0, []);
		$listener->onProcessingCompleted(new ProcessingCompletedEvent($fs, $fs, false));
	}

	public function testCleanUpDirectory()
	{
		$dst = new Local(__DIR__ . '/test1');
		$dst->createDir('test', new Config());

		$listener = $this->getMockListener($this->getMockRunner(), 1, []);

		$download = new Download();
		$download->setDestination(__DIR__ . '/test');
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);

		$listener->onChangeDownloadStateAfter(
				new CommandEvent(
						new ChangeDownloadState([
								$download
						], Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_COMPLETED)));

		$this->assertFalse($dst->has('test'));
	}

	public function testCleanUpDirectoryNorProcessed()
	{
		$dst = new Local(__DIR__ . '/test1');
		$dst->createDir('test', new Config());

		$listener = $this->getMockListener($this->getMockRunner(), 1, []);

		$download = new Download();
		$download->setDestination(__DIR__ . '/test');
		$download->setState(Download::STATE_PROCESSING_ERROR);

		$listener->onChangeDownloadStateAfter(
				new CommandEvent(
						new ChangeDownloadState([
								$download
						], Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_COMPLETED)));

		$this->assertTrue($dst->has('test'));
	}

	public function testCleanUpDirectoryWrongEvent()
	{
		$dst = new Local(__DIR__ . '/test1');
		$dst->createDir('test', new Config());

		$listener = $this->getMockListener($this->getMockRunner(), 0, []);
		$listener->onChangeDownloadStateAfter(new CommandEvent(new ProcessLinks([])));

		$this->assertTrue($dst->has('test'));
	}

	public function testCleanUpDirectoryWrongState()
	{
		$dst = new Local(__DIR__ . '/test1');
		$dst->createDir('test', new Config());

		$listener = $this->getMockListener($this->getMockRunner(), 0, []);

		$download = new Download();
		$download->setDestination(__DIR__ . '/test');
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);

		$listener->onChangeDownloadStateAfter(
				new CommandEvent(
						new ChangeDownloadState([
								$download
						], Download::STATE_DOWNLOADING_ERROR, Download::STATE_PROCESSING_ERROR)));

		$this->assertTrue($dst->has('test'));
	}

	public function testCleanUpDirectoryWrongDestination()
	{
		$listener = $this->getMockListener($this->getMockRunner(), 1, []);

		$download = new Download();
		$download->setDestination(__DIR__ . '/test');
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);

		$listener->onChangeDownloadStateAfter(
				new CommandEvent(
						new ChangeDownloadState([
								$download
						], Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_COMPLETED)));
	}

	public function testCleanUpDirectoryDestinationHasNoDirectory()
	{
		$dst = new Local(__DIR__ . '/test1');

		$listener = $this->getMockListener($this->getMockRunner(), 1, []);

		$download = new Download();
		$download->setDestination(__DIR__ . '/test');
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);

		$listener->onChangeDownloadStateAfter(
				new CommandEvent(
						new ChangeDownloadState([
								$download
						], Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_COMPLETED)));
	}

	protected function setUp()
	{
		$fs = new Local(__DIR__ . '/');
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}

	private function getMockListener(Runner $runner, $keyCount = 1, $extensions = ['txt' => 'unit'])
	{
		$fs = new Local(__DIR__);

		$listener = $this->getMockForAbstractClass(AbstractProcessingListener::class,
				[
						$runner,
						new Registry([
								'unittest' => $fs->applyPathPrefix('test1')
						])
				]);
		$listener->expects($this->exactly($keyCount))
			->method('getConfigurationKey')
			->willReturn('unittest');
		$listener->expects($extensions ? $this->once() : $this->never())
			->method('getFileExtensionsForCommand')
			->willReturn($extensions);

		return $listener;
	}
}
