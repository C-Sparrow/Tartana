<?php
namespace Tartana\Event\Listener;

use Monolog\Logger;
use Tartana\Component\Command\Command;
use Tartana\Mixins\LoggerAwareTrait;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;

class ConsoleExceptionListener
{

	use LoggerAwareTrait;

	public function onConsoleException(ConsoleExceptionEvent $event)
	{
		$command = $event->getCommand();
		$exception = $event->getException();

		$message = sprintf(
			'%s: %s (uncaught exception) at %s line %s while running console command `%s`',
			get_class($exception),
			$exception->getMessage(),
			$exception->getFile(),
			$exception->getLine(),
			$command->getName()
		);

		$this->log($message, Logger::ERROR);
	}
}
