<?php
namespace Tests\Unit\Tartana\Event\Listener;
use League\Flysystem\Adapter\Local;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Event\ProcessingCompletedEvent;
use Tartana\Event\ProcessingProgressEvent;
use Tartana\Event\Listener\UpdateExtractStateListener;
use SimpleBus\Message\Bus\MessageBus;

class UpdateExtractStateListenerTest extends \PHPUnit_Framework_TestCase
{

	public function testOnExtractProgress ()
	{
		$download1 = new Download();
		$download1->setDestination(__DIR__);
		$download1->setFileName('test.rar');
		$download2 = new Download();
		$download2->setDestination(__DIR__);
		$download2->setFileName('test1.rar');

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloadsByDestination')->willReturn([
				$download1,
				$download2
		]);

		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();
		$commandBus->expects($this->once())
			->method('handle')
			->with(
				$this->callback(
						function  (SaveDownloads $command) {
							return $command->getDownloads()[0]->getProgress() == 20 && empty($command->getDownloads()[0]->getMessage());
						}));

		$event = new ProcessingProgressEvent(new Local(__DIR__), new Local(__DIR__), 'test.rar', 20);
		$listener = new UpdateExtractStateListener($repositoryMock, $commandBus);
		$listener->onExtractProgress($event);

		$this->assertEmpty($download2->getProgress());
		$this->assertEmpty($download2->getMessage());
	}

	public function testonProcessingCompletedSuccess ()
	{
		$download = new Download();
		$download->setDestination(__DIR__);

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloadsByDestination')->willReturn([
				$download
		]);
		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();
		$commandBus->expects($this->once())
			->method('handle')
			->with(
				$this->callback(
						function  (SaveDownloads $command) {
							return $command->getDownloads()[0]->getState() == Download::STATE_PROCESSING_COMPLETED &&
									 empty($command->getDownloads()[0]->getMessage());
						}));

		$event = new ProcessingCompletedEvent(new Local(__DIR__), new Local(__DIR__), true);
		$listener = new UpdateExtractStateListener($repositoryMock, $commandBus);
		$listener->onProcessingCompleted($event);
	}

	public function testonProcessingCompletedError ()
	{
		$download = new Download();
		$download->setDestination(__DIR__);

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloadsByDestination')->willReturn([
				$download
		]);
		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();
		$commandBus->expects($this->once())
			->method('handle')
			->with(
				$this->callback(
						function  (SaveDownloads $command) {
							return $command->getDownloads()[0]->getState() == Download::STATE_PROCESSING_ERROR &&
									 ! empty($command->getDownloads()[0]->getMessage());
						}));

		$event = new ProcessingCompletedEvent(new Local(__DIR__), new Local(__DIR__), false);
		$listener = new UpdateExtractStateListener($repositoryMock, $commandBus);
		$listener->onProcessingCompleted($event);
	}
}
