<?php
namespace Tests\Unit\Local\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Local\Handler\EntityManagerHandler;
use Tartana\Entity\Base;

class EntityManagerHandlerTest extends \PHPUnit_Framework_TestCase
{

	public function testGetEntityManager()
	{
		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();

		$handler = $this->getMockForAbstractClass(EntityManagerHandler::class, [
				$entityManager
		]);
		$this->assertEquals($entityManager, $this->invokeMethod($handler, 'getEntityManager'));
	}

	public function testPersist()
	{
		$entity = new Base();

		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
		$entityManager->expects($this->once())
			->method('merge')
			->willReturnCallback(function (Base $e) {
				return $e;
			});
		$entityManager->expects($this->once())
			->method('persist')
			->with($this->callback(function (Base $e) use ($entity) {
				return $e == $entity;
			}));

		$handler = $this->getMockForAbstractClass(EntityManagerHandler::class, [
				$entityManager
		]);
		$this->invokeMethod($handler, 'persistEntity', [
				$entity
		]);
	}

	public function testRemove()
	{
		$entity = new Base();

		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
		$entityManager->expects($this->once())
			->method('merge')
			->willReturnCallback(function (Base $e) {
				return $e;
			});
		$entityManager->expects($this->once())
			->method('remove')
			->with($this->callback(function (Base $e) use ($entity) {
				return $e == $entity;
			}));

		$handler = $this->getMockForAbstractClass(EntityManagerHandler::class, [
				$entityManager
		]);
		$this->invokeMethod($handler, 'removeEntity', [
				$entity
		]);
	}

	public function testFlush()
	{
		$entity = new Base();

		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)->getMock();
		$entityManager->expects($this->once())
			->method('flush');

		$handler = $this->getMockForAbstractClass(EntityManagerHandler::class, [
				$entityManager
		]);
		$this->invokeMethod($handler, 'flushEntities');
	}

	/**
	 *
	 * @see https://jtreminio.com/2013/03/unit-testing-tutorial-part-3-testing-protected-private-methods-coverage-reports-and-crap/
	 */
	private function invokeMethod(&$object, $methodName, array $parameters = array())
	{
		$reflection = new \ReflectionClass(get_class($object));
		$method = $reflection->getMethod($methodName);
		$method->setAccessible(true);

		return $method->invokeArgs($object, $parameters);
	}
}
