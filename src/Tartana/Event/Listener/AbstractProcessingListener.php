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
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Event\ProcessingCompletedEvent;

/**
 * The processing listener handles the download completed events.
 * It extracts or convertes them to the destination with the same folder name as
 * the files are within.
 */
abstract class AbstractProcessingListener
{

	use LoggerAwareTrait;

	private $runner = null;

	protected $configuration = null;

	public function __construct (Runner $runner, Registry $configuration)
	{
		$this->runner = $runner;
		$this->configuration = $configuration;
	}

	/**
	 * Returns the configuration key which acts as destination.
	 *
	 * @return string
	 */
	abstract protected function getConfigurationKey ();

	/**
	 * Returns an array of file extensions to process and the corresponding
	 * tartana command to execute.
	 *
	 * @return string[]
	 */
	abstract protected function getFileExtensionsForCommand ();

	/**
	 *
	 * @param Command $command
	 * @return Command
	 */
	protected function prepareCommand (Command $command)
	{
		return $command;
	}

	public function onProcessCompletedDownloads (DownloadsCompletedEvent $event)
	{
		$destination = Util::realPath($this->configuration->get($this->getConfigurationKey()));
		if (! empty($destination))
		{
			$destination = new Local($destination);
		}
		if (! $event->getDownloads() || ! $destination)
		{
			return;
		}

		$path = $event->getDownloads()[0]->getDestination();

		$this->log('Handling the download completed event of the folder ' . $path, Logger::INFO);

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

			foreach ($this->getFileExtensionsForCommand() as $fileExtension => $command)
			{
				foreach ($files as $file)
				{
					if (! Util::endsWith($file['path'], '.' . $fileExtension) && strpos($file['path'], '.' . $fileExtension . '.') === false)
					{
						continue;
					}

					$this->runCommand($command, $path, $destination->applyPathPrefix($dirName));

					$processed = true;
					break;
				}
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
		$this->log('Finished handling the download completed event of the folder ' . $path, Logger::INFO);
	}

	/**
	 * Handles the extract completed event.
	 *
	 * @param ProcessingCompletedEvent $event
	 */
	public function onProcessingCompleted (ProcessingCompletedEvent $event)
	{
		$destination = $event->getDestination();
		$this->log('Checking completed processed files at folder: ' . $destination->getPathPrefix(), Logger::INFO);

		if ($event->isSuccess())
		{
			$this->log('Processing was successfully in folder ' . $destination->getPathPrefix(), Logger::INFO);

			$this->log('Searching for more files to process in folder: ' . $destination->getPathPrefix());

			// Reprocessing processed files
			$folders = [];
			foreach ($destination->listContents('', true) as $file)
			{
				$dir = dirname($file['path']);

				if (key_exists($dir, $folders))
				{
					continue;
				}
				$folders[$dir] = $dir;

				foreach ($this->getFileExtensionsForCommand() as $fileExtension => $command)
				{
					if (! Util::endsWith($file['path'], '.' . $fileExtension))
					{
						continue;
					}

					$this->log('Reprocessing folder: ' . $destination->applyPathPrefix($dir));

					$this->runCommand($command, $destination->applyPathPrefix($dir), $destination->applyPathPrefix($dir));
				}
			}
		}
		else
		{
			$this->log('Error processing files in folder ' . $destination->getPathPrefix(), Logger::ERROR);
		}
	}

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

		$destination = Util::realPath($this->configuration->get($this->getConfigurationKey()));
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

	protected function runCommand ($cmd, $path, $destination)
	{
		$this->log('Starting with ' . $cmd . ' processing of the folder: ' . $path, Logger::INFO);

		$command = Command::getAppCommand($cmd);
		$command->setCaptureErrorInOutput(true);
		$command->setAsync($this->configuration->get('async', true));
		$command->addArgument($path);
		$command->addArgument($destination);

		$command = $this->prepareCommand($command);

		$this->log('Running command to process the files: ' . $command);
		$this->runner->execute($command);
	}
}
