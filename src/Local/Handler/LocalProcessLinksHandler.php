<?php
namespace Local\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Monolog\Logger;
use Tartana\Domain\Command\ProcessLinks;
use Tartana\Entity\Download;
use Tartana\Mixins\HostFactoryAwareTrait;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;

class LocalProcessLinksHandler extends EntityManagerHandler
{
	use LoggerAwareTrait;
	use HostFactoryAwareTrait;

	private $configuration = null;

	public function __construct(Registry $configuration, EntityManagerInterface $entityManager)
	{
		parent::__construct($entityManager);

		$this->configuration = $configuration;
	}

	public function handle(ProcessLinks $links)
	{
		$destination = self::createJobDir($this->configuration->get('downloads'), true);
		// Something is wrong
		if (empty($destination)) {
			return;
		}

		$existingDownloads = $this->getEntityManager()
			->getRepository(Download::class)
			->findBy([
				'link' => $links->getLinks()
			]);
		if (count($existingDownloads) >= count($links->getLinks())) {
			$this->log('All links do exist already, no downloads are created at ' . $destination, Logger::INFO);

			$fs = new Local($destination);
			$fs->deleteDir('');
			return;
		}

		$existingLinks = [];
		foreach ($existingDownloads as $d) {
			$existingLinks[$d->getLink()] = $d->getLink();
		}

		$this->log('Adding links for later processing to the queue, downloading to ' . $destination, Logger::INFO);
		foreach ($links->getLinks() as $link) {
			if (key_exists($link, $existingLinks)) {
				continue;
			}
			$download = new Download();
			$download->setLink($link);
			$download->setDestination($destination);
			$download->setState(Download::STATE_DOWNLOADING_NOT_STARTED);

			$downloader = $this->getDownloader($link);
			if (!empty($downloader)) {
				$downloader->fetchDownloadInfo([
					$download
				]);
			}

			$this->persistEntity($download);
		}
		$this->flushEntities();
	}

	public static function createJobDir($path, $fullPath)
	{
		$root = Util::realPath($path);
		// We don't process the root folder
		if (empty($root)) {
			return null;
		}

		$jobName = 'job-' . date('YmdHis');
		$fs      = new Local($root);
		for ($i = 1; $i < 100; $i++) {
			if ($fs->has($jobName . '-' . $i)) {
				continue;
			}
			$jobName = $jobName . '-' . $i;
			$fs->createDir($jobName, new Config());
			break;
		}

		if ($fullPath) {
			return $fs->applyPathPrefix($jobName);
		}
		return $jobName;
	}
}
