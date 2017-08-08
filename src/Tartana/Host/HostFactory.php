<?php
namespace Tartana\Host;

use Joomla\Registry\Registry;
use Tartana\Mixins\CommandBusAwareTrait;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;

class HostFactory
{
	use LoggerAwareTrait;
	use CommandBusAwareTrait;

	/**
	 *
	 * @param string $link
	 * @param Registry $config
	 * @return \Tartana\Host\HostInterface
	 */
	public function createHostDownloader($link, Registry $config = null)
	{
		if ($config === null) {
			$config = new Registry();
		}
		$uri = Util::parseUrl($link);

		$hostName  = Util::cleanHostName($uri);
		$hostName  = ucfirst(strtolower($hostName));
		$className = 'Tartana\\Host\\' . $hostName;

		// Check if the class exists for the host to download
		if (!class_exists($className)) {
			$className = 'Tartana\\Host\\Common\\' . ucfirst(strtolower($uri['scheme']));
			if (!class_exists($className)) {
				return null;
			}
		}

		$downloader = new $className($config);
		if (!$downloader instanceof HostInterface) {
			return null;
		}

		$downloader->setLogger($this->getLogger());
		$downloader->setCommandBus($this->getCommandBus());

		return $downloader;
	}
}
