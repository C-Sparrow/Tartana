<?php
namespace Tartana\Domain\Command;

use Tartana\Domain\DownloadRepository;
use Tartana\Util;

class ProcessCompletedDownloads
{

	private $repository = null;

	private $downloads = null;

	public function __construct(DownloadRepository $repository, array $downloads)
	{
		$this->repository = $repository;
		$this->downloads = Util::cloneObjects($downloads);
	}

	public function getRepository()
	{
		return $this->repository;
	}

	public function getDownloads()
	{
		return $this->downloads;
	}
}
