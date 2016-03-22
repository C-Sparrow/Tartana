<?php
namespace Test\Unit\Tartana\Handler;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;
use Tartana\Handler\ChangeDownloadStateHandler;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class ChangeDownloadStateHandlerTest extends TartanaBaseTestCase
{

	public function testChangeState ()
	{
		$commandBus = $this->getMockCommandBus(
				[
						$this->callback(
								function  (SaveDownloads $command) {
									return $command->getDownloads()[0]->getState() == Download::STATE_PROCESSING_COMPLETED;
								})
				]);

		$download = new Download();
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);

		$handler = new ChangeDownloadStateHandler();
		$handler->setCommandBus($commandBus);
		$handler->handle(
				new ChangeDownloadState([
						$download
				], Download::STATE_DOWNLOADING_COMPLETED, Download::STATE_PROCESSING_COMPLETED));
	}

	public function testChangeStateNotAvailable ()
	{
		$handler = new ChangeDownloadStateHandler();
		$handler->setCommandBus($this->getMockCommandBus());
		$handler->handle(new ChangeDownloadState([], Download::STATE_DOWNLOADING_ERROR, Download::STATE_PROCESSING_COMPLETED));
	}

	public function testChangeStateNotStartedReset ()
	{
		$commandBus = $this->getMockCommandBus(
				[
						$this->callback(
								function  (SaveDownloads $command) {
									$download = $command->getDownloads()[0];
									return empty($download->getPid()) && $download->getProgress() == 0.00 && $download->getFileName() == 'hello.txt' &&
											 $download->getSize() == 123 && $download->getState() == Download::STATE_DOWNLOADING_NOT_STARTED;
								})
				]);

		$download = new Download();
		$download->setFileName('hello.txt');
		$download->setSize(123);
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);
		$download->setProgress(44.5);
		$download->setPid(12);

		$handler = new ChangeDownloadStateHandler();
		$handler->setCommandBus($commandBus);
		$handler->handle(
				new ChangeDownloadState([
						$download
				], Download::STATE_DOWNLOADING_COMPLETED, Download::STATE_DOWNLOADING_NOT_STARTED));
	}

	public function testChangeStateNotStartedDeleteFileAndFolder ()
	{
		$commandBus = $this->getMockCommandBus(
				[
						$this->callback(
								function  (SaveDownloads $command) {
									$download = $command->getDownloads()[0];
									return $download->getState() == Download::STATE_DOWNLOADING_NOT_STARTED;
								})
				]);

		$fs = new Local(__DIR__ . '/test');
		$fs->write('test.txt', 'unit test', new Config());

		$download = new Download();
		$download->setFileName('test.txt');
		$download->setDestination($fs->getPathPrefix());
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);

		$handler = new ChangeDownloadStateHandler();
		$handler->setCommandBus($commandBus);
		$handler->handle(
				new ChangeDownloadState([
						$download
				], Download::STATE_DOWNLOADING_COMPLETED, Download::STATE_DOWNLOADING_NOT_STARTED));

		$this->assertFalse($fs->has(''));
	}

	public function testChangeStateNotStartedDeleteFile ()
	{
		$commandBus = $this->getMockCommandBus(
				[
						$this->callback(
								function  (SaveDownloads $command) {
									$download = $command->getDownloads()[0];
									return $download->getState() == Download::STATE_DOWNLOADING_NOT_STARTED;
								})
				]);

		$fs = new Local(__DIR__ . '/test');
		$fs->write('test.txt', 'unit test', new Config());
		$fs->write('test1.txt', 'unit test 1', new Config());

		$download = new Download();
		$download->setFileName('test.txt');
		$download->setDestination($fs->getPathPrefix());
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);

		$handler = new ChangeDownloadStateHandler();
		$handler->setCommandBus($commandBus);
		$handler->handle(
				new ChangeDownloadState([
						$download
				], Download::STATE_DOWNLOADING_COMPLETED, Download::STATE_DOWNLOADING_NOT_STARTED));

		$this->assertFalse($fs->has('test.txt'));
		$this->assertTrue($fs->has('test1.txt'));
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}
}