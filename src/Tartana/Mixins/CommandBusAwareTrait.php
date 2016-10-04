<?php
namespace Tartana\Mixins;

use SimpleBus\Message\Bus\MessageBus;

trait CommandBusAwareTrait
{

	private $commandBus = null;

	public function getCommandBus()
	{
		return $this->commandBus;
	}

	public function setCommandBus(MessageBus $commandBus = null)
	{
		$this->commandBus = $commandBus;
	}

	public function handleCommand($command)
	{
		if ($this->getCommandBus()) {
			$this->getCommandBus()->handle($command);
		}
	}
}
