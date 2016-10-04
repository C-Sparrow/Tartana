<?php
namespace Local\Handler;

use League\Flysystem\Adapter\Local;
use Tartana\Component\Command\Command;
use Tartana\Domain\Command\DeleteDownloads;
use Tartana\Mixins\LoggerAwareTrait;

class LocalDeleteDownloadsHandler extends EntityManagerHandler
{
	use LoggerAwareTrait;

	public function handle(DeleteDownloads $command)
	{
		if (empty($command->getDownloads())) {
			return;
		}
		foreach ($command->getDownloads() as $download) {
			$this->removeEntity($download);
		}
		$this->flushEntities();
	}
}
