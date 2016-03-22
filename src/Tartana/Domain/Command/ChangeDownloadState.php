<?php
namespace Tartana\Domain\Command;

class ChangeDownloadState
{

	private $downloads = null;

	private $fromState = null;

	private $toState = null;

	public function __construct (array $downloads, $fromState, $toState)
	{
		$this->downloads = $downloads;
		$this->fromState = $fromState;
		$this->toState = $toState;
	}

	/**
	 *
	 * @return \Tartana\Entity\Download[]
	 */
	public function getDownloads ()
	{
		return $this->downloads;
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