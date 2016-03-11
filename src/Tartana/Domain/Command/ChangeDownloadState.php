<?php
namespace Tartana\Domain\Command;
use Tartana\Domain\DownloadRepository;

class ChangeDownloadState
{

	private $repository = null;

	private $fromState = null;

	private $toState = null;

	public function __construct (DownloadRepository $repository, $fromState, $toState)
	{
		$this->repository = $repository;
		$this->fromState = $fromState;
		$this->toState = $toState;
	}

	public function getRepository ()
	{
		return $this->repository;
	}

	public function getFromState ()
	{
		return $this->fromState;
	}

	public function getToState ()
	{
		return $this->toState;
	}
}