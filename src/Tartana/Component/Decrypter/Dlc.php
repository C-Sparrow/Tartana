<?php
namespace Tartana\Component\Decrypter;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Monolog\Logger;

class Dlc extends BaseDecrypter
{

	private $client = null;

	public function __construct(ClientInterface $client = null)
	{
		if (!$client) {
			$client = new Client(
				[
					'headers' => [
						'Accept' => 'application/json, text/javascript, */*',
						'Content-Type' => 'application/x-www-form-urlencoded',
						'Host' => 'dcrypt.it',
						'Origin' => 'http://dcrypt.it',
						'Referer' => 'http://dcrypt.it/',
						'X-Requested-With' => 'XMLHttpRequest',
						'Connection' => 'keep-alive',
						'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/31.0.1650.39 Safari/537.36'
					]
				]
			);
		}
		$this->client = $client;
	}

	public function getLinks($content)
	{
		$this->log('Calling http://dcrypt.it/decrypt/paste', Logger::INFO);
		$res = $this->client->request('post', 'http://dcrypt.it/decrypt/paste', [
			'form_params' => [
				'content' => $content
			]
		]);

		$decRes = json_decode($res->getBody()->getContents());
		$this->log('Response from http://dcrypt.it/decrypt/paste was: ' . print_r($decRes, true));

		if (is_object($decRes) && isset($decRes->success) && is_array($decRes->success->links)) {
			$links = $decRes->success->links;
			$links = array_filter($links, function ($link) {
				return strpos($link, 'http') === 0;
			});

			$this->log('Found ' . count($links), Logger::INFO);

			return $links;
		} else {
			throw new \RuntimeException('Failed parsing response: ' . var_export($decRes, true));
		}
	}
}
