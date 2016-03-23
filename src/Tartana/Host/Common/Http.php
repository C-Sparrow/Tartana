<?php
namespace Tartana\Host\Common;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;
use Tartana\Host\HostInterface;
use Tartana\Mixins\CommandBusAwareTrait;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;

class Http implements HostInterface
{
	use LoggerAwareTrait;
	use CommandBusAwareTrait;

	private $configuration = null;

	private $client = null;

	public function __construct (Registry $configuration, ClientInterface $client = null)
	{
		$this->configuration = $configuration;
		$this->setClient($client);
	}

	public function fetchDownloadInfo (array $downloads)
	{
		foreach ($downloads as $download)
		{
			// Connection check
			try
			{
				$originalName = $this->parseFileName($this->getClient()
					->head($download->getLink()));
				if (! empty($originalName) && empty($download->getFileName()))
				{
					$download->setFileName($originalName);
				}
			}
			catch (\Exception $e)
			{
				$this->log('Exception fetching head for connection test: ' . $e->getMessage());
				$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_URL');
				$download->setState(Download::STATE_DOWNLOADING_ERROR);
			}
		}
	}

	public function download (array $downloads)
	{
		if (empty($downloads))
		{
			return [];
		}

		try
		{
			if (! $this->login())
			{
				foreach ($downloads as $download)
				{
					$download->setState(Download::STATE_DOWNLOADING_ERROR);
					$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_LOGIN');
				}
				$this->handleCommand(new SaveDownloads($downloads));
				return [];
			}
		}
		catch (\Exception $e)
		{
			foreach ($downloads as $download)
			{
				$download->setState(Download::STATE_DOWNLOADING_ERROR);
				$download->setMessage($e->getMessage());
			}
			$this->handleCommand(new SaveDownloads($downloads));
			return [];
		}

		$promises = [];
		foreach ($downloads as $download)
		{
			$download->setStartedAt(new \DateTime());
			try
			{
				$promises[] = $this->createPremise($download);
			}
			catch (\Exception $e)
			{
				$download->setState(Download::STATE_DOWNLOADING_ERROR);
				$download->setMessage($e->getMessage());
				$download->setFinishedAt(new \DateTime());
				$this->handleCommand(new SaveDownloads([
						$download
				]));
				continue;
			}
		}

		return $promises;
	}

	/**
	 * If none is internaly configured a new instance will be created.
	 *
	 * @return \GuzzleHttp\ClientInterface
	 */
	public function getClient ()
	{
		if (! $this->client)
		{
			$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp/');
			$name = strtolower((new \ReflectionClass($this))->getShortName()) . '.cookie';
			if (! $fs->has($name) || $this->getConfiguration()->get('clearSession', false))
			{
				$fs->write($name, '', new Config());
			}
			$this->client = new Client([
					'cookies' => new FileCookieJar($fs->applyPathPrefix($name), true)
			]);
		}
		return $this->client;
	}

	public function setClient (ClientInterface $client = null)
	{
		$this->client = $client;
	}

	/**
	 * Returns the configuration.
	 *
	 * @return \Joomla\Registry\Registry
	 */
	protected function getConfiguration ()
	{
		return $this->configuration;
	}

	/**
	 * Returns the real url to download, subclasses can do here some
	 * preprocessing of the given download.
	 * The download will be saved after that operation. If null is returned, the
	 * download will not be performed.
	 *
	 * @param Download $download
	 * @return string
	 */
	protected function getUrlToDownload (Download $download)
	{
		return $download->getLink();
	}

	/**
	 * Login function which can be used on subclasses to authenticate before the
	 * download is done.
	 *
	 * @return boolean
	 */
	protected function login ()
	{
		return true;
	}

	/**
	 * Returns if the local client has a cookie with the given name and is not
	 * expired.
	 *
	 * @param string $name
	 * @return \GuzzleHttp\Cookie\SetCookie
	 */
	protected function getCookie ($name)
	{
		$cookies = $this->getClient()->getConfig('cookies');

		if (! $cookies instanceof \Traversable)
		{
			return null;
		}

		foreach ($cookies as $cookie)
		{
			/** @var \GuzzleHttp\Cookie\SetCookie $cookie */
			if ($cookie->getName() != $name)
			{
				continue;
			}
			if (! $cookie->getExpires() || $cookie->getExpires() > time())
			{
				return $cookie;
			}
		}

		return null;
	}

	/**
	 * Subclasses can define here the headers before the file is downloaded.
	 * It must return an array of headers.
	 *
	 * @param Download $download
	 * @return array
	 */
	protected function getHeadersForDownload (Download $download)
	{
		return [];
	}

	/**
	 * Parses the file name from a response.
	 * Mainly it tryes to analyze the headers.
	 *
	 * @param Response $response
	 * @return string|NULL
	 */
	protected function parseFileName (Response $response)
	{
		$dispHeader = $response->getHeader('Content-Disposition');
		if ($dispHeader && preg_match('/.*filename=([^ ]+)/', $dispHeader[0], $matches))
		{
			return trim($matches[1], '";');
		}
		return null;
	}

	private function createPremise (Download $download)
	{
		$url = $this->getUrlToDownload($download);
		if (! $url)
		{
			if (! $download->getMessage())
			{
				$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_FAILED_REAL_URL');
			}
			throw new \Exception($download->getMessage());
		}

		$tmpFileName = 'tmp-' . $download->getId() . '.bin';

		$me = $this;
		$fs = new Local($download->getDestination());

		// @codeCoverageIgnoreStart
		$options = [
				RequestOptions::SINK => $fs->applyPathPrefix($tmpFileName),
				RequestOptions::PROGRESS => function  ($totalSize, $downloadedSize) use ( $download, $me) {
					if (! $downloadedSize || ! $totalSize)
					{
						return;
					}
					$progress = (100 / $totalSize) * $downloadedSize;
					$me->log(
							'Progress of ' . $download->getFileName() . ' is ' . $progress . '. Downloaded already ' .
									 Util::readableSize($downloadedSize) . ' of ' . Util::readableSize($totalSize));

					if ($progress < $download->getProgress() + (rand(100, 700) / 1000))
					{
						// Reducing write transactions on the
						// repository
						return;
					}

					$download->setProgress($progress);
					$download->setSize($totalSize);
					$me->handleCommand(new SaveDownloads([
							$download
					]));
				}
		];
		// @codeCoverageIgnoreEnd

		if ($this->getConfiguration()->get('speedlimit') > 0)
		{
			$options['curl'] = [
					CURLOPT_MAX_RECV_SPEED_LARGE => $this->getConfiguration()->get('speedlimit') * 1000
			];
		}

		$options[RequestOptions::HEADERS] = $this->getHeadersForDownload($download);

		$request = new Request('get', $url);
		$promise = $this->getClient()->sendAsync($request, $options);

		$promise->then(
				function  (Response $resp) use ( $fs, $tmpFileName, $download, $me) {
					$originalFileName = $this->parseFileName($resp);
					if (empty($download->getFileName()) && ! empty($originalFileName))
					{
						$download->setFileName($originalFileName);
					}

					if (! empty($download->getFileName()))
					{
						$fs->rename($tmpFileName, $download->getFileName());
					}
					else
					{
						$download->setFileName($tmpFileName);
					}

					// Hash check
					if (! empty($download->getHash()))
					{
						$hash = md5_file($fs->applyPathPrefix($download->getFileName()));
						if ($download->getHash() != $hash)
						{
							$fs->delete($download->getFileName());
							$download->setState(Download::STATE_DOWNLOADING_ERROR);
							$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_HASH');
							$download->setFinishedAt(new \DateTime());
							$me->handleCommand(new SaveDownloads([
									$download
							]));
							return;
						}
					}

					$download->setState(Download::STATE_DOWNLOADING_COMPLETED);
					$download->setProgress(100);
					$download->setFinishedAt(new \DateTime());
					$me->handleCommand(new SaveDownloads([
							$download
					]));
				},
				function  (RequestException $e) use ( $download, $me) {
					$download->setState(Download::STATE_DOWNLOADING_ERROR);
					$download->setMessage($e->getMessage());
					$download->setFinishedAt(new \DateTime());
					$me->handleCommand(new SaveDownloads([
							$download
					]));
				});
		return $promise;
	}
}
