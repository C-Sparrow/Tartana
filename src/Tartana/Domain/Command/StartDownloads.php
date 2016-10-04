<?php
namespace Tartana\Domain\Command;

use Tartana\Domain\DownloadRepository;

class StartDownloads
{

	private $repository;

	public function __construct(DownloadRepository $repository)
	{
		$this->repository = $repository;
	}

	public function getRepository()
	{
		return $this->repository;
	}
}
