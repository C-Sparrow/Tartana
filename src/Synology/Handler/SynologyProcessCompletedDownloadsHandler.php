<?php
namespace Synology\Handler;

use Monolog\Logger;
use Tartana\Domain\Command\ProcessCompletedDownloads;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Mixins\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SynologyProcessCompletedDownloadsHandler
{

	use LoggerAwareTrait;

	private $dispatcher = null;

	public function __construct(EventDispatcherInterface $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Processes the completed downloads.
	 *
	 * @param ProcessCompletedDownloads $command
	 */
	public function handle(ProcessCompletedDownloads $command)
	{
		if (! $command->getDownloads()) {
			return;
		}

		$path = $command->getDownloads()[0]->getDestination();
		$this->log('Sending downloads finished event with folder: ' . $path . '.', Logger::INFO);
		$this->dispatcher->dispatch('downloads.completed', new DownloadsCompletedEvent($command->getRepository(), $command->getDownloads()));
	}
}
