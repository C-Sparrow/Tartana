<?php
namespace Tests\Unit\Local\Handler;
use Doctrine\ORM\EntityManagerInterface;
use Local\Handler\LocalDeleteDownloadsHandler;
use Tartana\Domain\Command\DeleteDownloads;
use Tartana\Entity\Download;

class LocalDeleteDownloadsHandlerTest extends \PHPUnit_Framework_TestCase
{

	public function testDeleteDownloads ()
	{
		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
		$entityManager->expects($this->once())
			->method('merge')
			->willReturnCallback(function  (Download $e) {
			return $e;
		});
		$entityManager->expects($this->once())
			->method('remove')
			->with($this->callback(function  (Download $download) {
			return $download->getId() == 1;
		}));
		$entityManager->expects($this->once())
			->method('flush');

		$downloads = [
				new Download()
		];
		$downloads[0]->setId(1);

		$handler = new LocalDeleteDownloadsHandler($entityManager);
		$handler->handle(new DeleteDownloads($downloads));
	}

	public function testDeleteDownloadsNoDownloads ()
	{
		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
		$entityManager->expects($this->never())
			->method('remove');
		$entityManager->expects($this->never())
			->method('flush');

		$handler = new LocalDeleteDownloadsHandler($entityManager);
		$handler->handle(new DeleteDownloads([]));
	}
}