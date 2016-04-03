<?php
namespace Tartana\Event\Listener;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Monolog\Logger;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Entity\Download;
use Tartana\Event\CommandEvent;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;
use League\Flysystem\Config;
use Tartana\Mixins\CommandBusAwareTrait;
use Tartana\Domain\Command\SaveDownloads;

/**
 * Converts downloads which are mp4 to mp3.
 */
class ConvertSoundListener
{

	use LoggerAwareTrait;
	use CommandBusAwareTrait;

	private $runner = null;

	private $configuration = null;

	public function __construct (Runner $runner, Registry $configuration)
	{
		$this->runner = $runner;
		$this->configuration = $configuration;
	}

	public function onConvertDownloads (DownloadsCompletedEvent $event)
	{
		$this->log('Started sound conversion', Logger::INFO);

		$destination = Util::realPath($this->configuration->get('sound.destination'));
		if (! empty($destination))
		{
			$destination = new Local($destination);
		}
		if (! $event->getDownloads() || ! $destination)
		{
			return;
		}

		if (! $this->runner->execute(new Command('which ffmpeg')))
		{
			$this->log("FFmpeg is not on the path, can't convert");
			return;
		}

		$soundHostFilter = $this->configuration->get('sound.hostFilter');
		$this->log('Host filter is: ' . $soundHostFilter);

		foreach ($event->getDownloads() as $download)
		{
			if (! Util::endsWith($download->getFileName(), '.mp4'))
			{
				continue;
			}

			if ($soundHostFilter && ! preg_match("/" . $soundHostFilter . "/", $download->getLink()))
			{
				continue;
			}

			$destName = basename($download->getDestination());
			if (! $destination->has($destName))
			{
				$destination->createDir($destName, new Config());
			}

			$this->log('Starting to convert the file ' . $download->getDestination() . '/' . $download->getFileName() . ' to mp3');

			$command = new Command('ffmpeg');
			$command->setAsync(false);
			$command->addArgument('-i');
			$command->addArgument($download->getDestination() . '/' . $download->getFileName());
			$command->addArgument('-f mp3', false);
			$command->addArgument('-ab 192k', false);
			$command->addArgument('-y', false);
			$command->addArgument('-vn');
			$command->addArgument(str_replace('.mp4', '.mp3', $destination->applyPathPrefix($destName) . '/' . $download->getFileName()));

			$download->setState(Download::STATE_PROCESSING_STARTED);
			$this->handleCommand(new SaveDownloads([
					$download
			]));

			$this->runner->execute($command);

			$download->setState(Download::STATE_PROCESSING_COMPLETED);
			$this->handleCommand(new SaveDownloads([
					$download
			]));

			$this->log('Finished to convert the file ' . $download->getDestination() . '/' . $download->getFileName() . ' to mp3');
		}
		$this->log('Finished sound conversion', Logger::INFO);
	}

	public function onChangeDownloadStateAfter (CommandEvent $event)
	{
		if (! $event->getCommand() instanceof ChangeDownloadState)
		{
			return;
		}

		if ($event->getCommand()->getToState() != Download::STATE_DOWNLOADING_NOT_STARTED &&
				 $event->getCommand()->getToState() != Download::STATE_DOWNLOADING_COMPLETED)
		{
			return;
		}

		$destination = Util::realPath($this->configuration->get('sound.destination'));
		if (empty($destination))
		{
			return;
		}
		else
		{
			$destination = new Local($destination);
		}

		$this->log('Cleaning up the destination ' . $destination->getPathPrefix() . ' for sound conversion', Logger::INFO);

		$directories = [];
		foreach ($event->getCommand()->getDownloads() as $download)
		{
			$directories[basename($download->getDestination())] = basename($download->getDestination());
		}

		foreach ($directories as $dirName)
		{
			if (! $destination->has($dirName))
			{
				continue;
			}
			$this->log('Deleting the directory ' . $destination->applyPathPrefix($dirName));
			$destination->deleteDir($dirName);
		}
	}
}
