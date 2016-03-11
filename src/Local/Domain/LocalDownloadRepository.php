<?php
namespace Local\Domain;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\Adapter\Local;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;

class LocalDownloadRepository implements DownloadRepository
{

	private $entityManager = null;

	public function __construct (EntityManagerInterface $entityManager)
	{
		$this->entityManager = $entityManager;
	}

	public function findDownloads ($state = null)
	{
		$repository = $this->entityManager->getRepository('Tartana:Download');

		if ($state !== null)
		{
			return $repository->findBy([
					'state' => $state
			]);
		}

		return $repository->findAll();
	}

	public function findDownloadsByDestination ($destination)
	{
		$destination = rtrim($destination, DIRECTORY_SEPARATOR);
		$repository = $this->entityManager->getRepository('Tartana:Download');
		$builder = $repository->createQueryBuilder('d');
		$builder->where('d.destination LIKE :destination');
		$builder->setParameter('destination', '%' . $destination . '%');
		return $builder->getQuery()->getResult();
	}
}