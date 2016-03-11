<?php
namespace Tartana\Host;
use GuzzleHttp\Psr7\Request;
use Tartana\Entity\Download;
use Tartana\Host\Common\Http;

class Uploadednet extends Http
{

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
