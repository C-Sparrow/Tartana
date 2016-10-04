<?php
namespace Local\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Tartana\Entity\Base;

abstract class EntityManagerHandler
{

	private $entityManager = null;

	public function __construct(EntityManagerInterface $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	protected function getEntityManager()
	{
		return $this->entityManager;
	}

	protected function persistEntity(Base $entity)
	{
		// AS we can't be sure the entity is not cloned, we need to merge it
		$merged = $this->entityManager->merge($entity);
		$this->entityManager->persist($merged);
	}

	protected function removeEntity(Base $entity)
	{
		// AS we can't be sure the entity is not cloned, we need to merge it
		$merged = $this->entityManager->merge($entity);
		$this->entityManager->remove($merged);
	}

	protected function flushEntities()
	{
		$this->entityManager->flush();
	}
}
