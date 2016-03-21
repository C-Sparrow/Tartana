<?php
namespace Tartana\Host;
use GuzzleHttp\Psr7\Request;
use Tartana\Entity\Download;
use Tartana\Host\Common\Http;

class Shareonlinebiz extends Http
{

	public function fetchDownloadInfo (array $downloads)
	{
		foreach ($downloads as $download)
		{
			try
			{
				// Getting the link information
				$res = $this->getClient()->request('get', 'https://api.share-online.biz/linkcheck.php?md5=1&links=' . urlencode($download->getLink()));
				$csv = explode(';', $res->getBody()->getContents());
				if (count($csv) >= 2)
				{
					if ($csv[1] != 'OK')
					{
						$download->setState(Download::STATE_DOWNLOADING_ERROR);
						$download->setMessage($csv[1]);
					}
					else if (count($csv) >= 4)
					{
						$download->setFileName($csv[2]);
						$download->setSize($csv[3]);
					}
				}
				else
				{
					parent::fetchDownloadInfo([
							$download
					]);
				}
			}
			catch (\Exception $e)
			{
				$this->log('Exception fetching file info for connection test: ' . $e->getMessage());
				$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_URL');
				$download->setState(Download::STATE_DOWNLOADING_ERROR);
			}
		}
	}

	protected function getUrlToDownload (Download $download)
	{
		$res = $this->getClient()->request('get', $download->getLink());
		$html = $res->getBody()->getContents();

		if (! preg_match(';var dl="(.+?)";s', $html, $match))
		{
			$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_FAILED_REAL_URL');
			return null;
		}

		$this->log('Share online base64 decoded: ' . base64_decode($match[1]) . ' real url: ' . $match[1]);

		return base64_decode($match[1]);
	}

	protected function login ()
	{
		if ($this->getCookie('a'))
		{
			return true;
		}

		$args = [
				'user' => trim($this->getConfiguration()->get('shareonlinebiz.username')),
				'pass' => trim($this->getConfiguration()->get('shareonlinebiz.password'))
		];

		if (! $args['user'])
		{
			return false;
		}

		$res = $this->getClient()->request('post', 'https://www.share-online.biz/user/login', [
				'form_params' => $args
		]);
		$html = $res->getBody()->getContents();
		return strpos($html, $args['user']) !== false;
	}
}
