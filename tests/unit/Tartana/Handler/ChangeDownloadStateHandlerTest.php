<?php
namespace Test\Unit\Tartana\Handler;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Handler\ChangeDownloadStateHandler;
use SimpleBus\Message\Bus\MessageBus;

class ChangeDownloadStateHandlerTest extends \PHPUnit_Framework_TestCase
{

	public function testChangeState ()
	{
		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();
		$commandBus->expects($this->once())
			->method('handle')
			->with(
				$this->callback(
						function  (SaveDownloads $command) {
							return $command->getDownloads()[0]->getState() == Download::STATE_PROCESSING_COMPLETED;
						}));

		$downloads = [
				new Download()
		];
		$downloads[0]->setState(Download::STATE_DOWNLOADING_COMPLETED);

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->willReturn($downloads);
		$repositoryMock->expects($this->once())
			->method('findDownloads')
			->with($this->callback(function  ($state) {
			return $state == Download::STATE_DOWNLOADING_COMPLETED;
		}));

		$handler = new ChangeDownloadStateHandler($commandBus);
		$handler->handle(new ChangeDownloadState($repositoryMock, Download::STATE_DOWNLOADING_COMPLETED, Download::STATE_PROCESSING_COMPLETED));
	}

	public function testChangeStateNotAvailable ()
	{
		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();
		$commandBus->expects($this->never())
			->method('handle');

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->willReturn([]);

		$handler = new ChangeDownloadStateHandler($commandBus);
		$handler->handle(new ChangeDownloadState($repositoryMock, Download::STATE_DOWNLOADING_ERROR, Download::STATE_PROCESSING_COMPLETED));
	}

	public function testChangeStateNotStartedReset ()
	{
		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();
		$commandBus->expects($this->once())
			->method('handle')
			->with(
				$this->callback(
						function  (SaveDownloads $command) {
							$download = $command->getDownloads()[0];
							return empty($download->getPid()) && $download->getProgress() == 0.00 &&
									 $download->getState() == Download::STATE_DOWNLOADING_NOT_STARTED;
						}));

		$downloads = [
				new Download()
		];
		$downloads[0]->setState(Download::STATE_DOWNLOADING_COMPLETED);
		$downloads[0]->setProgress(44.5);
		$downloads[0]->setPid(12);

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->willReturn($downloads);

		$handler = new ChangeDownloadStateHandler($commandBus);
		$handler->handle(new ChangeDownloadState($repositoryMock, Download::STATE_DOWNLOADING_COMPLETED, Download::STATE_DOWNLOADING_NOT_STARTED));
	}

	public function testChangeStateNotStartedDeleteDirectory ()
	{
		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();
		$commandBus->expects($this->once())
			->method('handle')
			->with(
				$this->callback(
						function  (SaveDownloads $command) {
							$download = $command->getDownloads()[0];
							return $download->getState() == Download::STATE_DOWNLOADING_NOT_STARTED;
						}));

		$fs = new Local(__DIR__ . '/test');
		$fs->createDir('test', new Config());

		$downloads = [
				new Download()
		];
		$downloads[0]->setDestination($fs->getPathPrefix());
		$downloads[0]->setState(Download::STATE_DOWNLOADING_COMPLETED);

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->willReturn($downloads);

		$handler = new ChangeDownloadStateHandler($commandBus);
		$handler->handle(new ChangeDownloadState($repositoryMock, Download::STATE_DOWNLOADING_COMPLETED, Download::STATE_DOWNLOADING_NOT_STARTED));

		$this->assertEmpty($fs->listContents('test'));
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}
}