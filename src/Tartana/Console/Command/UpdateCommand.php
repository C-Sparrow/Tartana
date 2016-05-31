<?php
namespace Tartana\Console\Command;

use GuzzleHttp\Promise;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Monolog\Logger;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Entity\Download;
use Tartana\Host\HostFactory;
use Tartana\Mixins\LoggerAwareTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends \Symfony\Component\Console\Command\Command
{
	use LoggerAwareTrait;

	const GITHUB_API_URL = 'https://api.github.com/repos/c-sparrow/tartana/releases';

	private $commandRunner = null;

	private $url = null;

	private $factory = null;

	public function __construct(Runner $commandRunner, $url, HostFactory $factory)
	{
		parent::__construct('update');

		$this->url = $url;
		$this->commandRunner = $commandRunner;

		$this->factory = $factory;
	}

	protected function configure()
	{
		$this->setDescription('Updates Tartana!');
		$this->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces the update, even when the version is lower than the downloaded one.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$force = (boolean)$input->getOption('force');

		$this->log('Started with update routine against ' . $this->url, Logger::INFO);

		$url = $this->url;
		if (empty($url))
		{
			$this->log('Update url is empty');
			$this->log('Finished with update routine', Logger::INFO);
			return;
		}

		$this->log('Stopping the server');
		$command = Command::getAppCommand('server');
		$command->setAsync(false);
		$command->addArgument('stop');
		$this->commandRunner->execute($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		if ($url == 'github')
		{
			$this->log('Getting latest release from github');
			$this->download(self::GITHUB_API_URL, $fs->getPathPrefix(), 'github-update-data.json');

			$data = null;
			if ($fs->has('github-update-data.json'))
			{
				$data = json_decode($fs->read('github-update-data.json')['contents']);
			}

			if (!is_array($data) || !isset($data[0]->assets) || !is_array($data[0]->assets) || !isset($data[0]->assets[0]->browser_download_url))
			{
				$this->log("Api response didn't send an asset", Logger::ERROR);
				$this->log('Github API response: ' . print_r($data, true));
				$url = null;
			}
			else
			{
				$url = $data[0]->assets[0]->browser_download_url;
			}
		}

		if (!empty($url))
		{
			$this->log('Fetching Tartana from ' . $url);

			if ($fs->has('tartana.zip'))
			{
				$fs->delete('tartana.zip');
			}

			try
			{
				$this->download($url, $fs->getPathPrefix(), 'tartana.zip');
			}
			catch (\Exception $e)
			{
				$this->log('Exception fetching Tartana update file: ' . $e->getMessage());
			}

			$zip = new \ZipArchive();
			$oldVersion = $fs->read('../../app/config/internal/version.txt')['contents'];
			if (!$fs->has('tartana.zip'))
			{
				$this->log("Zip file to extract doesn't exist, can't update!", Logger::ERROR);
			}
			else if ($zip->open($fs->applyPathPrefix('tartana.zip')) !== true)
			{
				$this->log("Could not read zip file, it is corrupt!", Logger::ERROR);
			}
			else if (!$force && version_compare($oldVersion, $zip->getFromName('app/config/internal/version.txt')) >= 0)
			{
				$this->log(
						"Old version is " . $oldVersion . ", new version is " . $zip->getFromName('app/config/internal/version.txt') .
								 ". No new version found, nothing to update!", Logger::INFO);
			}
			else
			{
				$this->log(
						'Found zip file to update at ' . $fs->applyPathPrefix('tartana.zip') . ' from version ' . $oldVersion . ' to version ' .
								 $zip->getFromName('app/config/internal/version.txt') . ', updating!', Logger::INFO);

				$command = Command::getAppCommand('unzip');
				$command->setAsync(false);
				$command->setCaptureErrorInOutput(true);
				$command->setOutputFile('/dev/null');
				$command->addArgument($fs->getPathPrefix());
				$command->addArgument(TARTANA_PATH_ROOT);
				$output = $this->commandRunner->execute($command);

				$this->log('Output of unzip was: ' . $output);

				if (empty($output))
				{
					if ($fs->has('tartana.zip'))
					{
						$fs->delete('tartana.zip');
					}

					$command = Command::getAppCommand('doctrine:migrations:migrate');
					$command->setCaptureErrorInOutput(true);
					$command->setOutputFile('/dev/null');
					$command->addArgument('--no-interaction');
					$this->commandRunner->execute($command);
				}

				$fs->deleteDir('../cache');
				$fs->createDir('../cache', new Config());
			}
		}

		$this->log('Finished with update routine', Logger::INFO);
	}

	private function download($url, $destination, $fileName)
	{
		$downloader = $this->factory->createHostDownloader($url);
		if (empty($downloader))
		{
			return;
		}

		$downloader->setCommandBus(null);

		$d = new Download();
		$d->setId('tartana');
		$d->setDestination($destination);
		$d->setFileName($fileName);
		$d->setLink($url);

		$tmp = (array)$downloader->download([
				$d
		]);
		Promise\unwrap($tmp);
	}
}
