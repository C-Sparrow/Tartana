<?php
namespace Tartana\Host;
use GuzzleHttp\Psr7\Response;
use Pdp\Parser;
use Pdp\PublicSuffixListManager;
use Tartana\Entity\Download;
use Tartana\Host\Common\Http;

class Dropboxcom extends Http
{

	protected function getUrlToDownload (Download $download)
	{
		$token = trim($this->getConfiguration()->get('dropboxcom.token'));
		if (empty($token))
		{
			return $this->fixUrl($download->getLink());
		}
		return 'https://content.dropboxapi.com/2/sharing/get_shared_link_file';
	}

	protected function getHeadersForDownload (Download $download)
	{
		$token = trim($this->getConfiguration()->get('dropboxcom.token'));
		if (empty($token))
		{
			return parent::getHeadersForDownload($download);
		}

		$headers = [];
		$headers['Authorization'] = 'Bearer ' . $token;
		$headers['Dropbox-API-Arg'] = '{"url": "' . $this->fixUrl($download->getLink()) . '"}';

		return $headers;
	}

	protected function parseFileName (Response $response)
	{
		$dispHeader = $response->getHeader('dropbox-api-result');
		if ($dispHeader && $dispHeader = json_decode($dispHeader[0]))
		{
			return isset($dispHeader->name) ? $dispHeader->name : null;
		}
		return parent::parseFileName($response);
	}

	private function fixUrl ($url)
	{
		$pslManager = new PublicSuffixListManager();
		$parser = new Parser($pslManager->getList());

		$uri = null;
		$uri = $parser->parseUrl($url)->toArray();
		return $uri['scheme'] . '://' . $uri['host'] . $uri['path'] . '?dl=1';
	}
}
