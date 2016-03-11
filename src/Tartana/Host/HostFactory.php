<?php
namespace Tartana\Host;
use Joomla\Registry\Registry;
use Pdp\Parser;
use Pdp\PublicSuffixListManager;
use Tartana\Mixins\CommandBusAwareTrait;
use Tartana\Mixins\LoggerAwareTrait;

class HostFactory
{
	use LoggerAwareTrait;
	use CommandBusAwareTrait;

	/**
	 *
	 * @param string $link
	 * @param Registry $config
	 * @return Tartana\Host\HostInterface
	 */
	public function createHostDownloader ($link, Registry $config = null)
	{
		if ($config === null)
		{
			$config = new Registry();
		}
		$pslManager = new PublicSuffixListManager();
		$parser = new Parser($pslManager->getList());

		$uri = null;
		try
		{
			$uri = $parser->parseUrl($link);
		}
		catch (\Exception $e)
		{
			return null;
		}

		$hostName = $uri->host->registerableDomain ? $uri->host->registerableDomain : $uri->host->host;
		$hostName = preg_replace("/[^A-Za-z0-9 ]/", '', $hostName);
		$hostName = ucfirst(strtolower($hostName));
		$className = 'Tartana\\Host\\' . $hostName;

		// Check if the class exists for the host to download
		if (! class_exists($className))
		{
			$className = 'Tartana\\Host\\Common\\' . ucfirst(strtolower($uri->scheme));
			if (! class_exists($className))
			{
				return null;
			}
		}

		$downloader = new $className($config);
		if (! $downloader instanceof HostInterface)
		{
			return null;
		}

		$downloader->setLogger($this->getLogger());
		$downloader->setCommandBus($this->getCommandBus());

		return $downloader;
	}
}