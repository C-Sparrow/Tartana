<?php
namespace Local\Event\Listener;

use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;
use Tartana\Event\CommandEvent;
use Tartana\Mixins\CommandBusAwareTrait;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;

class ChangeDownloadStateListener
{
	use LoggerAwareTrait;
	use CommandBusAwareTrait;

	private $configuration = null;

	public function __construct(Registry $configuration)
	{
		$this->configuration = $configuration;
	}

	public function onChangeDownloadStateAfter(CommandEvent $event)
	{
		if (!$event->getCommand() instanceof ChangeDownloadState) {
			return;
		}

		if ($event->getCommand()->getToState() != Download::STATE_DOWNLOADING_NOT_STARTED) {
			return;
		}

		$destination = rtrim(Util::realPath($this->configuration->get('downloads')), '/');
		// Something is wrong
		if (empty($destination)) {
			return;
		}

		$this->log('Checking if all downloads belong to the destination ' . $destination);

		$toSave = [];
		foreach ($event->getCommand()->getDownloads() as $download) {
			$base = rtrim(dirname($download->getDestination()), '/');
			if (strpos($destination, $base) !== false) {
				continue;
			}

			// Destination is different than the download directory
			$download->setDestination(str_replace($base, $destination, $download->getDestination()));
			new Local($download->getDestination());

			$toSave[] = $download;
		}

		if (!empty($toSave)) {
			$this->handleCommand(new SaveDownloads($toSave));
		}
	}
}
