<?php
namespace Tartana\Event;
use Tartana\Domain\DownloadRepository;
use Symfony\Component\EventDispatcher\Event;

class DownloadsCompletedEvent extends Event
{

	private $repository = null;

	private $downloads = null;

	public function __construct (DownloadRepository $repository, array $downloads)
	{
		$this->repository = $repository;
		$this->downloads = $downloads;
	}

	public function getRepository ()
	{
		return $this->repository;
	}

	/**
	 *
	 * @return \Tartana\Entity\Download[]
	 */
	public function getDownloads ()
	{
		return $this->downloads;
	}
}