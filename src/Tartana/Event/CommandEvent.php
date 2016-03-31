<?php
namespace Tartana\Event;
use Symfony\Component\EventDispatcher\Event;

class CommandEvent extends Event
{

	private $command = null;

	public function __construct ($command)
	{
		$this->command = $command;
	}

	public function getCommand ()
	{
		return $this->command;
	}

	public function setCommand ($command)
	{
		$this->command = $command;
	}
}
