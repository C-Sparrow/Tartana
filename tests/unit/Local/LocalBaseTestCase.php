<?php
namespace Tests\Unit\Local;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Tartana\Entity\Download;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class LocalBaseTestCase extends TartanaBaseTestCase
{

	protected function getMockEntityManager ($callbacks = [], $flushCount = 1, $downloads = [])
	{
		foreach ($callbacks as $key => $callback)
		{
			$callbacks[$key] = [
					$callback
			];
		}

		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
		$entityManager->method('merge')->willReturnCallback(function  (Download $e) {
			return $e;
		});

		$method = $entityManager->expects($this->exactly(count($callbacks)))
			->method('persist');
		$this->callWithConsecutive($method, $callbacks);

		$entityManager->expects($this->exactly($flushCount))
			->method('flush');

		$repository = $this->getMockBuilder(ObjectRepository::class)->getMock();
		$repository->method('findBy')->willReturn($downloads);
		$entityManager->method('getRepository')->willReturn($repository);
		return $entityManager;
	}
}