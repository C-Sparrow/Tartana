<?php
namespace Tartana\Event\Listener;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Event\ProcessingCompletedEvent;
use Tartana\Event\ProcessingProgressEvent;
use Tartana\Mixins\LoggerAwareTrait;
use SimpleBus\Message\Bus\MessageBus;

class UpdateExtractStateListener
{

	use LoggerAwareTrait;

	private $repository = null;

	private $commandBus = null;

	public function __construct (DownloadRepository $repository, MessageBus $commandBus)
	{
		$this->repository = $repository;
		$this->commandBus = $commandBus;
	}

	public function onExtractProgress (ProcessingProgressEvent $event)
	{
		$downloads = $this->repository->findDownloadsByDestination($event->getSource()
			->getPathPrefix());

		$hasChanged = false;
		foreach ($downloads as $download)
		{
			if ($download->getFileName() != $event->getFile())
			{
				continue;
			}
			$download->setProgress($event->getProgress());
			$hasChanged = true;
		}
		if ($hasChanged)
		{
			$this->commandBus->handle(new SaveDownloads($downloads));
		}
	}

	public function onProcessingCompleted (ProcessingCompletedEvent $event)
	{
		$downloads = $this->repository->findDownloadsByDestination($event->getSource()
			->getPathPrefix());
		$hasChanged = false;
		foreach ($downloads as $download)
		{
			if ($event->isSuccess())
			{
				$download->setState(Download::STATE_PROCESSING_COMPLETED);
			}
			else
			{
				$download->setState(Download::STATE_PROCESSING_ERROR);
				$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_EXTRACT_FAILED');
			}
			$download->setProgress(100);
			$hasChanged = true;
		}
		if ($hasChanged)
		{
			$this->commandBus->handle(new SaveDownloads($downloads));
		}
	}
}
