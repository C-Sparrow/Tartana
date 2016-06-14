<?php
namespace Local\Console\Command;

use GuzzleHttp\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Monolog\Logger;
use Tartana\Component\Command\Command;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Host\Common\Http;
use Tartana\Host\HostFactory;
use Tartana\Mixins\CommandBusAwareTrait;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tartana\Console\Command\AbstractDaemonCommand;
use Tartana\Component\Command\Runner;

class DownloadCommand extends AbstractDaemonCommand
{
	use LoggerAwareTrait;
	use CommandBusAwareTrait;

	private $repository = null;

	private $factory = null;

	public function __construct(DownloadRepository $repository, HostFactory $factory, Runner $runner)
	{
		parent::__construct($runner);

		$this->repository = $repository;
		$this->factory = $factory;
	}

	protected function configure()
	{
		parent::configure();

		$this->setName('download');
		$this->setDescription('Downloads links from the database. This command is running in foreground!');

		$this->addOption('force', 'f', InputOption::VALUE_NONE, 'Should all downloads set back to not started.');
	}

	protected function doWork(InputInterface $input, OutputInterface $output)
	{
		$repository = $this->repository;

		$this->log('Started to download links from the database');

		// Loading configuration for the hosters if it exists
		$config = new Registry();
		if (file_exists(TARTANA_PATH_ROOT . '/app/config/parameters.yml'))
		{
			$config->loadFile(TARTANA_PATH_ROOT . '/app/config/parameters.yml', 'yaml');
		}
		if (file_exists(TARTANA_PATH_ROOT . '/app/config/hosters.yml'))
		{
			$config->loadFile(TARTANA_PATH_ROOT . '/app/config/hosters.yml', 'yaml');
		}

		$force = (boolean)$input->getOption('force');
		$this->log('Restarting zombie downloads, error downloads will be ' . ($force ? '' : 'not') . ' restarted');

		$resets = $repository->findDownloads([
				Download::STATE_DOWNLOADING_STARTED,
				Download::STATE_DOWNLOADING_ERROR
		]);
		$hasChanged = false;
		foreach ($resets as $resetDownload)
		{
			if ($resetDownload->getState() != Download::STATE_DOWNLOADING_STARTED && !$force)
			{
				// When not forcing only check for zombie downloads
				continue;
			}
			if ($resetDownload->getState() == Download::STATE_DOWNLOADING_STARTED && $resetDownload->getPid() && Util::isPidRunning(
					$resetDownload->getPid()))
			{
				// There is an active process
				continue;
			}
			$resetDownload = Download::reset($resetDownload);
			$hasChanged = true;
		}
		if ($hasChanged)
		{
			$this->handleCommand(new SaveDownloads($resets));
		}

		while (true)
		{
			$notStartedDownloads = $repository->findDownloads(Download::STATE_DOWNLOADING_NOT_STARTED);
			if (empty($notStartedDownloads))
			{
				// Nothing to do anymore
				break;
			}

			$concurrentDownloads = 5;
			$counter = count($repository->findDownloads(Download::STATE_DOWNLOADING_STARTED));

			// Set download speed limit
			if (isset($config->get('parameters')->{'tartana.local.downloads.speedlimit'}) &&
					 $config->get('parameters')->{'tartana.local.downloads.speedlimit'} > 0)
			{
				$config->set('speedlimit', $config->get('parameters')->{'tartana.local.downloads.speedlimit'} / $concurrentDownloads);
			}

			// Check day limit
			if (isset($config->get('parameters')->{'tartana.local.downloads.daylimit'}) &&
					 $config->get('parameters')->{'tartana.local.downloads.daylimit'} > 0)
			{
				$dayLimit = $config->get('parameters')->{'tartana.local.downloads.daylimit'} * 1000;

				$today = (new \DateTime())->format('D');
				foreach ($repository->findDownloads(Download::STATE_DOWNLOADING_COMPLETED) as $download)
				{
					if ($download->getFinishedAt() && $download->getFinishedAt()->format('D') != $today)
					{
						continue;
					}

					$dayLimit -= $download->getSize();
				}

				if ($dayLimit <= 0)
				{
					$this->log('Reached day limit, not starting any download');
					$counter = $concurrentDownloads;
				}
			}

			$this->log('Found ' . $counter . ' started downloads.');

			// Processing the downloads
			$promises = [];
			$sharedClients = [];
			foreach ($notStartedDownloads as $download)
			{
				if ($counter >= $concurrentDownloads)
				{
					break;
				}

				$downloader = $this->factory->createHostDownloader($download->getLink(), $config);
				if ($downloader == null)
				{
					$this->log('No downloader found for link ' . $download->getLink(), Logger::WARNING);
					continue;
				}

				$this->log('Started to download ' . $download->getLink() . ' with the class ' . get_class($downloader));

				$download = Download::reset($download);
				$download->setState(Download::STATE_DOWNLOADING_STARTED);
				$download->setPid(getmypid());
				$this->handleCommand(new SaveDownloads([
						$download
				]));

				if ($downloader instanceof Http)
				{
					$name = get_class($downloader);
					if (!key_exists($name, $sharedClients))
					{
						$sharedClients[$name] = $downloader->getClient();
					}
					else
					{
						$downloader->setClient($sharedClients[$name]);
					}
				}

				$tmp = $downloader->download([
						clone $download
				]);

				$promises = array_merge($promises, $tmp ? $tmp : []);
				$counter ++;
			}

			$this->log('Downloading ' . count($promises) . ' links');

			Promise\unwrap($promises);

			// We sleep her as we are a daemon to relax the system a bit
			sleep($config->get('sleepTime', 10));
		}
		$this->log('Finished to download links from the database');
	}
}
