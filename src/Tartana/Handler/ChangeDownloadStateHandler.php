<?php
namespace Tartana\Handler;

use League\Flysystem\Adapter\Local;
use Tartana\Component\Command\Command;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;
use Tartana\Mixins\CommandBusAwareTrait;
use Tartana\Mixins\LoggerAwareTrait;

class ChangeDownloadStateHandler
{
	use LoggerAwareTrait;
	use CommandBusAwareTrait;

	public function handle(ChangeDownloadState $command)
	{
		if (empty($command->getDownloads())) {
			return;
		}

		$fromStates = (array)$command->getFromState();

		$hasChanged = false;
		foreach ($command->getDownloads() as $download) {
			if (!in_array($download->getState(), $fromStates)) {
				continue;
			}

			$hasChanged = true;

			if ($command->getToState() == Download::STATE_DOWNLOADING_NOT_STARTED) {
				// Full reset here
				$download = Download::reset($download);

				if ($download->getFileName() && file_exists($download->getDestination())) {
					// If the downloaded file already exists, it will be deleted
					$fs = new Local($download->getDestination());
					if ($fs->has($download->getFileName())) {
						$fs->delete($download->getFileName());
					}

					// When there is no more files in the folder, it will be
					// deleted
					if (empty($fs->listContents())) {
						$fs->deleteDir('');
					}
				}
			} else {
				$download->setMessage('');
				$download->setState($command->getToState());
			}
		}
		if ($hasChanged) {
			$this->handleCommand(new SaveDownloads($command->getDownloads()));
		}
	}
}
