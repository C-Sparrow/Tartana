<?php
namespace Tartana\Console\Command;

use League\Flysystem\Adapter\Local;
use Monolog\Logger;
use Tartana\Domain\Command\ParseLinks;
use Tartana\Domain\Command\ProcessCompletedDownloads;
use Tartana\Domain\Command\StartDownloads;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;
use SimpleBus\Message\Bus\MessageBus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Joomla\Registry\Registry;

class DefaultCommand extends Command
{

	use LoggerAwareTrait;

	private $repository = null;

	private $commandBus = null;

	private $configuration = null;

	public function __construct(DownloadRepository $repository, MessageBus $commandBus, Registry $configuration)
	{
		parent::__construct('default');

		$this->repository = $repository;
		$this->commandBus = $commandBus;
		$this->configuration = $configuration;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->log('Started with routine', Logger::INFO);

		$this->log('Checking folder for link files');
		$folder = $this->configuration->get('links.folder');
		$folder = Util::realPath($folder);
		if (! empty($folder)) {
			$fs = new Local($folder);
			$this->log('Searching for files containing links in folder: ' . $fs->getPathPrefix(), Logger::INFO);
			foreach ($fs->listContents('', true) as $file) {
				$this->log('Sending parse links command with file: ' . $file['path'], Logger::INFO);
				$this->commandBus->handle(new ParseLinks($fs, $file['path']));
			}
		}

		$this->log('Getting downloads from repository');
		$runningDownloads = $this->repository->findDownloads(
			[
						Download::STATE_DOWNLOADING_NOT_STARTED,
						Download::STATE_DOWNLOADING_STARTED,
						Download::STATE_DOWNLOADING_COMPLETED,
						Download::STATE_DOWNLOADING_ERROR
			]
		);

		// Collecting the directories and the download state
		$downloadDirectories = [];
		$hasNotStarted = false;
		foreach ($runningDownloads as $d) {
			if (! key_exists($d->getDestination(), $downloadDirectories)) {
				$downloadDirectories[$d->getDestination()] = $d->getState();
			} elseif ($d->getState() == Download::STATE_DOWNLOADING_NOT_STARTED || $d->getState() == Download::STATE_DOWNLOADING_STARTED ||
			 $d->getState() == Download::STATE_DOWNLOADING_ERROR) {
				$downloadDirectories[$d->getDestination()] = Download::STATE_DOWNLOADING_NOT_STARTED;
			}

			if ($d->getState() == Download::STATE_DOWNLOADING_NOT_STARTED) {
				$hasNotStarted = true;
			}
		}

		$this->log('Found ' . count($downloadDirectories) . ' directories with not processed downloads');
		foreach ($downloadDirectories as $path => $state) {
			if ($state != Download::STATE_DOWNLOADING_COMPLETED) {
				continue;
			}

			$completedDownloads = [];
			foreach ($runningDownloads as $d) {
				if ($d->getDestination() == $path) {
					$completedDownloads[] = $d;
				}
			}

			if (! empty($completedDownloads)) {
				$this->log('Sending downloads completed event with folder: ' . $path, Logger::INFO);
				$this->commandBus->handle(new ProcessCompletedDownloads($this->repository, $completedDownloads));
			}
		}

		if ($hasNotStarted) {
				$this->log('Sending start downloads command', Logger::INFO);
			$this->commandBus->handle(new StartDownloads($this->repository));
		}

		$this->log('Finished with routine', Logger::INFO);
	}
}
