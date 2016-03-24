<?php
namespace Tartana\Event\Listener;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Monolog\Logger;
use Tartana\Component\Archive\Extract;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Entity\Download;
use Tartana\Event\CommandEvent;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Event\ExtractCompletedEvent;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;

/**
 * The extract listener handles the download completed events.
 * It extracts them to the destination with the same folder name as the files
 * are within.
 */
class ExtractListener
{

	use LoggerAwareTrait;

	private $runner = null;

	private $configuration = null;

	public function __construct (Runner $runner, Registry $configuration)
	{
		$this->runner = $runner;
		$this->configuration = $configuration;
	}

	/**
	 * Handles the download completed event.
	 *
	 * @param DownloadsCompletedEvent $event
	 */
	public function onExtractDownloads (DownloadsCompletedEvent $event)
	{
		$destination = Util::realPath($this->configuration->get('extract.destination'));
		if (! empty($destination))
		{
			$destination = new Local($destination);
		}
		if (! $event->getDownloads() || ! $destination)
		{
			return;
		}

		$path = $event->getDownloads()[0]->getDestination();

		$this->log('Handling the download completed event of the folder: ' . $path, Logger::INFO);

		$dirName = basename($path);
		$this->log('Checking directory: ' . $destination->getPathPrefix() . ' if it has a folder ' . $dirName);

		$hasSource = file_exists($path);
		$hasDestination = $destination->has($dirName);
		$processed = false;
		if (! $hasDestination && $hasSource)
		{
			$this->log('No directory found, starting to process files on: ' . $destination->applyPathPrefix($dirName));

			// Out file doesn't exist, we can start processing it
			$destination->createDir($dirName, new Config());

			$fs = new Local(dirname($path));
			$files = $fs->listContents('', true);
			foreach ($files as $file)
			{
				if (! Util::endsWith($file['path'], '.rar'))
				{
					continue;
				}

				$this->runCommand('unrar', $path, $destination->applyPathPrefix($dirName));

				$processed = true;
				break;
			}

			foreach ($files as $file)
			{
				if (! Util::endsWith($file['path'], '.zip'))
				{
					continue;
				}

				$this->runCommand('unzip', $path, $destination->applyPathPrefix($dirName));

				$processed = true;
				break;
			}

			foreach ($files as $file)
			{
				if (! Util::endsWith($file['path'], '.7z'))
				{
					continue;
				}

				$this->runCommand('7z', $path, $destination->applyPathPrefix($dirName));

				$processed = true;
				break;
			}
		}
		foreach ($event->getDownloads() as $download)
		{
			if ($hasDestination)
			{
				$download->setMessage('TARTANA_EXTRACT_MESSAGE_DESTINATION_EXISTS');
				$download->setState(Download::STATE_PROCESSING_ERROR);
			}
			else if (! $hasSource)
			{
				$download->setMessage('TARTANA_EXTRACT_MESSAGE_SOURCE_NOT_EXIST');
				$download->setState(Download::STATE_PROCESSING_ERROR);
			}
			else if ($processed)
			{
				$download->setState(Download::STATE_PROCESSING_STARTED);
			}
		}

		$this->log('Finished handling the download completed event of the folder: ' . $path, Logger::INFO);
	}

	/**
	 * Handles the extract completed event.
	 *
	 * @param ExtractCompletedEvent $event
	 */
	public function onExtractCompleted (ExtractCompletedEvent $event)
	{
		$destination = $event->getDestination();
		$this->log('Extracting files finished at folder: ' . $destination->getPathPrefix(), Logger::INFO);

		if ($event->isSuccess())
		{
			$this->log('Successfull extracted files in folder ' . $destination->getPathPrefix(), Logger::INFO);

			$this->log('Searching for more rar files to extract in folder: ' . $destination->getPathPrefix());

			// Extract encapsulated archives on the destination
			$extractedFolders = array();
			foreach ($destination->listContents('', true) as $extractedFile)
			{
				$dir = dirname($extractedFile['path']);
				if (Util::endsWith($extractedFile['path'], '.rar') && ! key_exists($dir, $extractedFolders))
				{
					$extractedFolders[$dir] = $dir;

					$this->log('Extracting again files in folder: ' . $destination->applyPathPrefix($dir));

					$this->runCommand('unrar', $destination->applyPathPrefix($dir), $destination->applyPathPrefix($dir));
				}
			}
		}
		else
		{
			$this->log('Error extracting files in folder ' . $destination->getPathPrefix(), Logger::ERROR);
		}
	}

	/**
	 * Cleans up the extract directory when a reset is sent.
	 *
	 * @param CommandEvent $event
	 */
	public function onChangeDownloadStateAfter (CommandEvent $event)
	{
		if (! $event->getCommand() instanceof ChangeDownloadState)
		{
			return;
		}

		if ($event->getCommand()->getToState() != Download::STATE_DOWNLOADING_COMPLETED &&
				 $event->getCommand()->getToState() != Download::STATE_DOWNLOADING_NOT_STARTED)
		{
			return;
		}

		$destination = Util::realPath($this->configuration->get('extract.destination'));
		if (! empty($destination))
		{
			$destination = new Local($destination);
		}
		else
		{
			return;
		}

		$this->log('Cleaning up the destination ' . $destination->getPathPrefix() . ' for downloads', Logger::INFO);

		$dirNames = [];
		foreach ($event->getCommand()->getDownloads() as $download)
		{
			$dirNames[basename($download->getDestination())] = basename($download->getDestination());
		}

		foreach ($dirNames as $dirName)
		{
			if (! $destination->has($dirName))
			{
				continue;
			}
			$this->log('Deleting the directory ' . $destination->applyPathPrefix($dirName));
			$destination->deleteDir($dirName);
		}
	}

	private function runCommand ($cmd, $path, $destination)
	{
		$this->log('Starting with ' . $cmd . ' of the folder: ' . $path, Logger::INFO);

		// Because the extract commands can't handle a password file we need to
		// do our own wrapper script
		$command = Command::getAppCommand($cmd);
		$command->setCaptureErrorInOutput(true);
		$command->setAsync($this->configuration->get('async', true));
		$command->addArgument($path);
		$command->addArgument($destination);
		$command->addArgument(Util::realPath($this->configuration->get('extract.passwordFile')));

		$this->log('Running command to extract the files: ' . $command);
		$this->runner->execute($command);
	}
}
