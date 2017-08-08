<?php
namespace Test\Unit\Tartana\Handler;

use Tartana\Domain\Command\ProcessCompletedDownloads;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Handler\ProcessCompletedDownloadsHandler;
use SimpleBus\Message\Bus\MessageBus;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProcessCompletedDownloadsHandlerTest extends \PHPUnit_Framework_TestCase
{

	public function testWithDownloads()
	{
		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();
		$commandBus->expects($this->exactly(2))
			->method('handle')
			->withConsecutive(
				[
					$this->callback(
						function (SaveDownloads $command) {
							return $command->getDownloads()[0]->getState() == Download::STATE_PROCESSING_NOT_STARTED;
						}
					)
				],
				[
					$this->callback(
						function (SaveDownloads $command) {
							return $command->getDownloads()[0]->getState() == Download::STATE_PROCESSING_COMPLETED;
						}
					)
				]
			);

		$dispatcherMock = $this->getMockDispatcher();
		$dispatcherMock->expects($this->once())
			->method('dispatch')
			->with($this->equalTo('downloads.completed'));

		$downloads = [
			new Download()
		];
		$downloads[0]->setState(Download::STATE_DOWNLOADING_COMPLETED);
		$handler = new ProcessCompletedDownloadsHandler($dispatcherMock, $commandBus);
		$handler->handle(new ProcessCompletedDownloads($this->getMockRepository(), $downloads));
	}

	public function testWithDownloadsChangedState()
	{
		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();
		$commandBus->expects($this->exactly(2))
			->method('handle')
			->withConsecutive(
				[
					$this->callback(
						function (SaveDownloads $command) {
							return $command->getDownloads()[0]->getState() == Download::STATE_PROCESSING_NOT_STARTED;
						}
					)
				],
				[
					$this->callback(
						function (SaveDownloads $command) {
							return $command->getDownloads()[0]->getState() == Download::STATE_PROCESSING_STARTED;
						}
					)
				]
			);
		$dispatcherMock = $this->getMockDispatcher();
		$dispatcherMock->expects($this->once())
			->method('dispatch')
			->with($this->equalTo('downloads.completed'))
			->willReturnCallback(
				function ($eventName, DownloadsCompletedEvent $event) {
					foreach ($event->getDownloads() as $download) {
						$download->setState(Download::STATE_PROCESSING_STARTED);
					}
				}
			);

		$downloads = [new Download()];
		$downloads[0]->setState(Download::STATE_DOWNLOADING_COMPLETED);
		$handler = new ProcessCompletedDownloadsHandler($dispatcherMock, $commandBus);
		$handler->handle(new ProcessCompletedDownloads($this->getMockRepository(), $downloads));
	}

	public function testEmptyDownloads()
	{
		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();
		$commandBus->expects($this->never())
			->method('handle');

		$dispatcherMock = $this->getMockDispatcher();
		$dispatcherMock->expects($this->never())
			->method('dispatch');

		$handler = new ProcessCompletedDownloadsHandler($dispatcherMock, $commandBus);
		$handler->handle(new ProcessCompletedDownloads($this->getMockRepository(), []));
	}

	private function getMockDispatcher()
	{
		$dispatcherMock = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
		$dispatcherMock->method('dispatch')->willReturn(true);

		return $dispatcherMock;
	}

	private function getMockRepository()
	{
		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();

		return $repositoryMock;
	}
}
