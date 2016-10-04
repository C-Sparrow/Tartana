<?php
namespace Test\Unit\Synology\Handler;

use Local\Domain\LocalDownloadRepository;
use Tartana\Domain\Command\ProcessCompletedDownloads;
use Tartana\Entity\Download;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Synology\Handler\SynologyProcessCompletedDownloadsHandler;

class SynologyProcessCompletedDownloadsHandlerTest extends \PHPUnit_Framework_TestCase
{

	public function testWithDownloads()
	{
		$dispatcherMock = $this->getMockDispatcher();
		$dispatcherMock->expects($this->once())
			->method('dispatch')
			->with($this->equalTo('downloads.completed'));

		$downloads = [
				new Download()
		];
		$downloads[0]->setState(Download::STATE_DOWNLOADING_COMPLETED);
		$handler = new SynologyProcessCompletedDownloadsHandler($dispatcherMock);
		$handler->handle(new ProcessCompletedDownloads($this->getMockRepository(), $downloads));
	}

	public function testEmptyDownloads()
	{
		$dispatcherMock = $this->getMockDispatcher();
		$dispatcherMock->expects($this->never())
			->method('dispatch');

		$handler = new SynologyProcessCompletedDownloadsHandler($dispatcherMock);
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
		$repositoryMock = $this->getMockBuilder(LocalDownloadRepository::class)
			->disableOriginalConstructor()
			->getMock();

		return $repositoryMock;
	}
}
