<?php
namespace Tartana\Host;
use GuzzleHttp\Promise\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AdapterInterface;
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

	private $manager = null;

	public function __construct (Registry $configuration = null, MountManager $manager = null)
	{
		$this->configuration = $configuration;

		if (empty($manager))
		{
			$manager = new MountManager();
		}
		$this->manager = $manager;
	}

	public function fetchDownloadInfo (array $downloads)
	{
		foreach ($downloads as $download)
		{
			if ($download->getFileName())
			{
				continue;
			}

			$fs = $this->getSourceAdapter($download);
			if (! empty($fs))
			{
				$download->setFileName(basename($download->getLink()));
			}
			else
			{
				$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_PATH');
				$download->setState(Download::STATE_DOWNLOADING_ERROR);
			}
		}
	}

	public function download (array $downloads)
	{
		$promises = [];
		foreach ($downloads as $download)
		{
			$destination = Util::realPath($download->getDestination());
			if (empty($destination))
			{
				$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_DESTINATION');
				$download->setState(Download::STATE_DOWNLOADING_ERROR);
				$this->handleCommand(new SaveDownloads([
						$download
				]));

				continue;
			}

			$src = $this->getSourceAdapter($download);
			if (empty($src))
			{
				$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_PATH');
				$download->setState(Download::STATE_DOWNLOADING_ERROR);
				$this->handleCommand(new SaveDownloads([
						$download
				]));
				continue;
			}

			$src = new Filesystem($src);
			$dest = new Filesystem(new Local($destination));

			$manager = $this->manager;
			$manager->mountFilesystem('src-' . $download->getId(), $src);
			$manager->mountFilesystem('dst-' . $download->getId(), $dest);

			$promise = new Promise(
					function  () use ( &$promise, $download, $manager) {
						$fileName = basename($download->getLink());
						$id = $download->getId();
						if (! @$manager->copy('src-' . $id . '://' . $fileName,
								'dst-' . $id . '://' . ($download->getFileName() ? $download->getFileName() : $fileName)))
						{
							$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_COPY_FAILED');
							$download->setState(Download::STATE_DOWNLOADING_ERROR);
						}
						else
						{
							$download->setState(Download::STATE_DOWNLOADING_COMPLETED);
							$download->setFinishedAt(new \DateTime());
						}

						$this->handleCommand(new SaveDownloads([
								$download
						]));

						$promise->resolve(true);
					});
			$promises[] = $promise;
		}

		return $promises;
	}

	/**
	 * Returns the source adapter to copy the download from.
	 * If none can be created null is returned.
	 *
	 * @param Download $download
	 * @return null|AdapterInterface
	 */
	protected function getSourceAdapter (Download $download)
	{
		$uri = Util::parseUrl($download->getLink());

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

		return new Local(dirname($path));
	}

	protected function getConfiguration ()
	{
		return $this->configuration;
	}
}