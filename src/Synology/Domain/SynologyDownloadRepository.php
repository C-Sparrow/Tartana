<?php
namespace Synology\Domain;
use GuzzleHttp\ClientInterface;
use Joomla\Registry\Registry;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Synology\Mixins\SynologyApiTrait;

class SynologyDownloadRepository implements DownloadRepository
{

	use SynologyApiTrait;

	private $configuration = null;

	public function __construct (ClientInterface $client = null, Registry $configuration)
	{
		if ($client !== null)
		{
			$this->setClient($client);
		}
		$this->setUrl($configuration->get('synology.address', 'https://localhost:5001/webapi'));
		$this->setUsername($configuration->get('synology.username', 'admin'));
		$this->setPassword($configuration->get('synology.password', 'admin'));

		$this->configuration = $configuration;
	}

	public function findDownloads ($states = null)
	{
		if ($states)
		{
			$states = (array) $states;
		}

		$args = array(
				'method' => 'list',
				'additional' => 'detail'
		);
		$res = $this->synologyApiCall($args);

		$downloads = [];
		foreach ($res->data->tasks as $task)
		{
			$state = Download::STATE_DOWNLOADING_NOT_STARTED;
			switch ($task->status)
			{
				case 'downloading':
				case 'paused':
				case 'finishing':
				case 'hash_checking':
					$state = Download::STATE_DOWNLOADING_STARTED;
					break;
				case 'finished':
					$state = Download::STATE_DOWNLOADING_COMPLETED;
					break;
				case 'error':
					$state = Download::STATE_DOWNLOADING_ERROR;
					break;
				case 'extracting':
					$state = Download::STATE_PROCESSING_STARTED;
					break;
			}

			if ($states !== null && ! in_array($state, $states))
			{
				continue;
			}

			$download = new Download();
			$download->setId($task->id);
			$download->setLink($task->additional->detail->uri);
			$download->setDestination(trim($this->configuration->get('downloads'), '/') . '/' . basename($task->additional->detail->destination));

			$download->setState($state);

			$downloads[] = $download;
		}
		return $downloads;
	}

	public function findDownloadsByDestination ($destination)
	{
		$destination = rtrim($destination, DIRECTORY_SEPARATOR);

		$downloads = [];
		foreach ($this->findDownloads() as $download)
		{
			if (strpos($download->getDestination(), $destination) !== false)
			{
				$downloads[] = $download;
			}
		}
		return $downloads;
	}
}