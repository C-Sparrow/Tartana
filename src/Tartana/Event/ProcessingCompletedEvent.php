<?php
namespace Tartana\Event;

use League\Flysystem\Adapter\AbstractAdapter;
use Symfony\Component\EventDispatcher\Event;

class ProcessingCompletedEvent extends Event
{

	private $source = null;

	private $destination = null;

	private $sucess = null;

	public function __construct(AbstractAdapter $source, AbstractAdapter $destination, $sucess)
	{
		$this->source = $source;
		$this->destination = $destination;
		$this->sucess = $sucess;
	}

	public function getSource()
	{
		return $this->source;
	}

	public function getDestination()
	{
		return $this->destination;
	}

	public function isSuccess()
	{
		return $this->sucess;
	}
}
