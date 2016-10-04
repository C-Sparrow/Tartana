<?php
namespace Local\Handler;

use League\Flysystem\Adapter\Local;
use Tartana\Component\Command\Command;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Mixins\LoggerAwareTrait;

class LocalSaveDownloadsHandler extends EntityManagerHandler
{
	use LoggerAwareTrait;

	public function handle(SaveDownloads $command)
	{
		$downloads = $command->getDownloads();
		if (empty($downloads)) {
			return;
		}

		foreach ($downloads as $key => $download) {
			if ($download->getDestination()) {
				// Trimming the right directory separator
				$download->setDestination(rtrim($download->getDestination(), DIRECTORY_SEPARATOR));
			}
			$this->persistEntity($download);
		}
		$this->flushEntities();
	}
}
