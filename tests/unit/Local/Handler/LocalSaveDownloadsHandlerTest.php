<?php
namespace Tests\Unit\Local\Handler;
use Doctrine\ORM\EntityManagerInterface;
use Local\Handler\LocalSaveDownloadsHandler;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;
use Tartana\Util;

class LocalSaveDownloadsHandlerTest extends \PHPUnit_Framework_TestCase
{

	public function testSaveDownloads ()
	{
		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
		$entityManager->expects($this->once())
			->method('merge')
			->willReturnCallback(function  (Download $e) {
			return $e;
		});
		$entityManager->expects($this->once())
			->method('persist')
			->with($this->callback(function  (Download $download) {
			return $download->getId() == 1;
		}));
		$entityManager->expects($this->once())
			->method('flush');

		$downloads = [
				new Download()
		];
		$downloads[0]->setId(1);

		$handler = new LocalSaveDownloadsHandler($entityManager);
		$handler->handle(new SaveDownloads($downloads));
	}

	public function testSetDownloadWithTrailingSlash ()
	{
		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
		$entityManager->expects($this->once())
			->method('merge')
			->willReturnCallback(function  (Download $e) {
			return $e;
		});
		$entityManager->expects($this->once())
			->method('persist')
			->with($this->callback(function  (Download $download) {
			return ! Util::endsWith($download->getDestination(), '/');
		}));
		$entityManager->expects($this->once())
			->method('flush');

		$download = new Download();
		$download->setDestination(__DIR__ . '/');

		$handler = new LocalSaveDownloadsHandler($entityManager);
		$handler->handle(new SaveDownloads([
				$download
		]));
	}

	public function testDeleteDownloadsNoDownloads ()
	{
		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
		$entityManager->expects($this->never())
			->method('persist');
		$entityManager->expects($this->never())
			->method('flush');

		$handler = new LocalSaveDownloadsHandler($entityManager);
		$handler->handle(new SaveDownloads([]));
	}
}