<?php
namespace Tartana\Host;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;
use Tartana\Mixins\CommandBusAwareTrait;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;

class Localhost implements HostInterface
{
	use LoggerAwareTrait;
	use CommandBusAwareTrait;

	private $configuration = null;

	public function __construct (Registry $configuration = null)
	{
		$this->configuration = $configuration;
	}

	public function fetchDownloadInfo (array $downloads)
	{
		foreach ($downloads as $download)
		{
			if ($download->getFileName())
			{
				continue;
			}

			$path = $this->getFileName($download->getLink());
			if (empty($path))
			{
				$this->updateDownload($download, 'TARTANA_DOWNLOAD_MESSAGE_INVALID_PATH', Download::STATE_DOWNLOADING_ERROR);
				continue;
			}

			$fs = new Local(dirname($path));
			$download->setFileName($fs->removePathPrefix($path));
		}
	}

	public function download (array $downloads)
	{
		foreach ($downloads as $download)
		{
			$destination = Util::realPath($download->getDestination());
			if (empty($destination))
			{
				$this->updateDownload($download, 'TARTANA_DOWNLOAD_MESSAGE_INVALID_DESTINATION');
				continue;
			}

			$path = $this->getFileName($download->getLink());
			if (empty($path))
			{
				$this->updateDownload($download, 'TARTANA_DOWNLOAD_MESSAGE_INVALID_PATH');
				continue;
			}

			$fs = new Local(dirname($path));
			$fileName = $fs->removePathPrefix($path);

			$src = new Filesystem($fs);
			$dest = new Filesystem(new Local($destination));

			$manager = new MountManager([
					'src' => $src,
					'dest' => $dest
			]);

			if (! @$manager->copy('src://' . $fileName, 'dest://' . ($download->getFileName() ? $download->getFileName() : $fileName)))
			{
				$this->updateDownload($download, 'TARTANA_DOWNLOAD_MESSAGE_COPY_FAILED');
			}
			else
			{
				$download->setFinishedAt(new \DateTime());
				$this->updateDownload($download, null, Download::STATE_DOWNLOADING_COMPLETED);
			}
		}

		return [];
	}

	private function getFileName ($link)
	{
		$uri = parse_url($link);
		if (! isset($uri['path']))
		{
			return null;
		}

		$path = Util::realPath($uri['path']);
		if (empty($path))
		{
			// Perhaps relative
			$path = Util::realPath(ltrim($uri['path'], '/'));
			if (empty($path))
			{
				return null;
			}
		}

		return $path;
	}

	private function updateDownload ($download, $message, $state = Download::STATE_DOWNLOADING_ERROR)
	{
		$download->setMessage($message);
		$download->setState($state);
		$this->handleCommand(new SaveDownloads([
				$download
		]));
	}
}