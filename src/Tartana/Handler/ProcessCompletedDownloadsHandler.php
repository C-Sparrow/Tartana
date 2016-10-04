<?php
namespace Tartana\Handler;

use Monolog\Logger;
use Tartana\Domain\Command\ProcessCompletedDownloads;
use Tartana\Entity\Download;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Mixins\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use SimpleBus\Message\Bus\MessageBus;
use Tartana\Domain\Command\SaveDownloads;

class ProcessCompletedDownloadsHandler
{

	use LoggerAwareTrait;

	private $dispatcher = null;

	private $commandBus = null;

	public function __construct(EventDispatcherInterface $dispatcher, MessageBus $commandBus)
	{
		$this->dispatcher = $dispatcher;
		$this->commandBus = $commandBus;
	}

	/**
	 * Processes the completed downloads.
	 *
	 * @param ProcessCompletedDownloads $command
	 */
	public function handle(ProcessCompletedDownloads $command)
	{
		$downloads = $command->getDownloads();
		if (empty($downloads)) {
			return;
		}

		$path = $downloads[0]->getDestination();
		$this->log('Handling the completed downloads of the path: ' . $path, Logger::INFO);

		foreach ($downloads as $download) {
			$download->setState(Download::STATE_PROCESSING_NOT_STARTED);
			$download->setProgress(0, true);
		}
		$this->commandBus->handle(new SaveDownloads($downloads));

		$this->log('Sending downloads finished event with folder: ' . $path . '.', Logger::INFO);
		$this->dispatcher->dispatch('downloads.completed', new DownloadsCompletedEvent($command->getRepository(), $downloads));

		foreach ($downloads as $download) {
			if ($download->getState() == Download::STATE_PROCESSING_NOT_STARTED) {
				$download->setState(Download::STATE_PROCESSING_COMPLETED);
			}
		}
		$this->commandBus->handle(new SaveDownloads($downloads));
	}
}
