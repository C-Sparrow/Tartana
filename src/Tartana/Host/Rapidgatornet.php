<?php
namespace Tartana\Host;
use GuzzleHttp\Psr7\Request;
use Tartana\Entity\Download;
use Tartana\Host\Common\Http;

class Rapidgatornet extends Http
{

	public function fetchDownloadInfo (array $downloads)
	{
		if (! $this->login())
		{
			foreach ($downloads as $download)
			{
				$download->setState(Download::STATE_DOWNLOADING_ERROR);
				$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_LOGIN');
			}
			return;
		}

		$sid = $this->getCookie('PHPSESSID')->getValue();
		foreach ($downloads as $download)
		{
			try
			{
				// Getting the link information
				$res = $this->getClient()->request('get', 'https://rapidgator.net/api/file/info?sid=' . $sid . '&url=' . $download->getLink());
				$info = json_decode($res->getBody()->getContents());
				if (isset($info->response_status) && $info->response_status == 200)
				{
					$download->setFileName($info->response->filename);
					$download->setSize($info->response->size);
				}
				else
				{
					$download->setMessage('TARTANA_DOWNLOAD_MESSAGE_INVALID_URL');
					$download->setState(Download::STATE_DOWNLOADING_ERROR);
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
		$sid = $this->getCookie('PHPSESSID') ? $this->getCookie('PHPSESSID')->getValue() : '';

		$res = $this->getClient()->request('get', 'https://rapidgator.net/api/file/download?sid=' . $sid . '&url=' . $download->getLink());
		$info = json_decode($res->getBody()->getContents());
		if (isset($info->response_status) && $info->response_status == 200)
		{
			$this->log('Rapidgator net real url: ' . $info->response->url);
			return $info->response->url;
		}

		return null;
	}

	protected function login ()
	{
		if ($this->getCookie('PHPSESSID'))
		{
			return true;
		}

		$args = [
				'username' => trim($this->getConfiguration()->get('rapidgatornet.username')),
				'password' => trim($this->getConfiguration()->get('rapidgatornet.password'))
		];

		if (! $args['username'])
		{
			return false;
		}

		$res = $this->getClient()->request('get',
				'https://rapidgator.net/api/user/login?username=' . $args['username'] . '&password=' . $args['password']);
		$html = $res->getBody()->getContents();
		$response = json_decode($html);
		return isset($response->response_status) && $response->response_status == 200;
	}
}
