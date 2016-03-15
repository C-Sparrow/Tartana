<?php
namespace Tartana\Host\Common;
use League\Flysystem\Adapter\Ftp as FtpClient;
use Tartana\Entity\Download;
use Tartana\Host\Localhost;
use Tartana\Util;

class Ftp extends Localhost
{

	protected function getSourceAdapter (Download $download)
	{
		$uri = Util::parseUrl($download->getLink());
		if (empty($uri['host']))
		{
			return null;
		}

		if (empty($uri['port']))
		{
			$uri['port'] = 21;
		}
		if (empty($uri['pass']))
		{
			$hostName = Util::cleanHostName($uri['host']);
			$uri['user'] = $this->getConfiguration()->get('ftp.' . $hostName . '.username');
			$uri['pass'] = $this->getConfiguration()->get('ftp.' . $hostName . '.password');
			if (empty($uri['pass']))
			{
				$hostName = Util::cleanHostName($uri['registerableDomain']);
				$uri['user'] = $this->getConfiguration()->get('ftp.' . $hostName . '.username');
				$uri['pass'] = $this->getConfiguration()->get('ftp.' . $hostName . '.password');
			}
		}
		$ftp = new FtpClient(
				[
						'host' => $uri['host'],
						'username' => $uri['user'],
						'password' => $uri['pass'],
						'port' => $uri['port'],
						'root' => dirname($uri['path']),
						'ssl' => $uri['scheme'] != 'ftp'
				]);
		return $ftp;
	}
}
