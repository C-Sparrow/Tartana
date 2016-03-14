<?php
namespace Tartana\Host;
use GuzzleHttp\Psr7\Request;
use Tartana\Entity\Download;
use Tartana\Host\Common\Http;

class Uploadednet extends Http
{

	public function fetchDownloadInfo (array $downloads)
	{
		foreach ($downloads as $download)
		{
			try
			{
				if (preg_match("/file\/(.*?)\//", $download->getLink(), $matches))
				{
					// Getting the link information
					$res = $this->getClient()->request('get',
							'https://uploaded.net/api/filemultiple?apikey=lhF2IeeprweDfu9ccWlxXVVypA5nA3EL&id_0=' . $matches[1]);
					$csv = explode(',', $res->getBody()->getContents(), 5);
					if (count($csv) >= 5)
					{
						if ($csv[0] != 'online')
						{
							$download->setState(Download::STATE_DOWNLOADING_ERROR);
							$download->setMessage($csv[0]);
						}
						else
						{
							$download->setFileName($csv[4]);
							$download->setSize($csv[2]);
						}
					}
					else
					{
						parent::fetchDownloadInfo([
								$download
						]);
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
				$this->log('Exception fetching head for connection test: ' . $e->getMessage());
				$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_URL');
				$download->setState(Download::STATE_DOWNLOADING_ERROR);
			}
		}
	}

	protected function getUrlToDownload (Download $download)
	{
		$res = $this->getClient()->request('get', $download->getLink());
		$html = $res->getBody()->getContents();

		if (! preg_match("#https?://[0-9a-z\-]*stor\d+.uploaded.net/dl/([0-9a-z\-]+)#mi", $html, $matches))
		{
			$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_MD5');
			return null;
		}

		$this->log('Uploaded net real url: ' . $matches[0]);

		return $matches[0];
	}

	protected function login ()
	{
		if ($this->hasCookie('login'))
		{
			return true;
		}

		$args = [
				'id' => trim($this->getConfiguration()->get('uploadednet.username')),
				'pw' => trim($this->getConfiguration()->get('uploadednet.password'))
		];

		if (! $args['id'])
		{
			return false;
		}

		$res = $this->getClient()->request('post', 'https://uploaded.net/io/login', [
				'form_params' => $args
		]);
		$html = $res->getBody()->getContents();
		return ! preg_match('#.' . preg_quote('{"err":') . '#si', $html);
	}
}
