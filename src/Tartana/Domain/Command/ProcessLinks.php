<?php
namespace Tartana\Domain\Command;

class ProcessLinks
{

	private $links;

	public function __construct (array $links)
	{
		$this->links = $links;
	}

	public function getLinks ()
	{
		return $this->links;
	}
}