<?php
namespace Tartana\Handler;
use League\Flysystem\Adapter\Local;
use Tartana\Component\Command\Command;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;
use Tartana\Mixins\LoggerAwareTrait;
use SimpleBus\Message\Bus\MessageBus;

class ChangeDownloadStateHandler
{
	use LoggerAwareTrait;

	private $commandBus = null;

	public function __construct (MessageBus $commandBus)
	{
		$this->commandBus = $commandBus;
	}

	public function handle (ChangeDownloadState $command)
	{
		$downloads = $command->getRepository()->findDownloads($command->getFromState());
		if (empty($downloads))
		{
			return;
		}
		foreach ($downloads as $download)
		{
			if ($command->getToState() == Download::STATE_DOWNLOADING_NOT_STARTED)
			{
				// Full reset here
				$download = Download::reset($download);

				if ($download->getFileName() && file_exists($download->getDestination()))
				{
					// If the downloaded file already exists, it will be deleted
					$fs = new Local($download->getDestination());
					if ($fs->has($download->getFileName()))
					{
						$fs->delete($download->getFileName());
					}

					// When there is no more files in the folder, it will be
					// deleted
					if (empty($fs->listContents()))
					{
						$fs->deleteDir('');
					}
				}
			}
			else
			{
				$download->setMessage('');
				$download->setState($command->getToState());
			}
		}
		$this->commandBus->handle(new SaveDownloads($downloads));
	}
}