<?php
namespace Synology\Mixins;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Client;
use Tartana\Mixins\LoggerAwareTrait;
use Monolog\Logger;

trait SynologyApiTrait
{

	use LoggerAwareTrait;

	private $client = null;

	private $url = 'https://localhost:5001/webapi/';

	private $username = 'admin';

	private $password = 'admin';

	private $sid = null;

	public function getClient ()
	{
		return $this->client;
	}

	public function setClient (ClientInterface $client)
	{
		$this->client = $client;
	}

	public function getUrl ()
	{
		return $this->url;
	}

	public function setUrl ($url)
	{
		$this->url = $url;
	}

	public function getUsername ()
	{
		return $this->username;
	}

	public function setUsername ($username)
	{
		$this->username = $username;
	}

	public function getPassword ()
	{
		return $this->password;
	}

	public function setPassword ($password)
	{
		$this->password = $password;
	}

	/**
	 * Calls the Synology web api and returns the response as object.
	 *
	 * @param array $args
	 * @param string $path
	 * @throws \RuntimeException
	 * @return \stdClass
	 */
	public function synologyApiCall ($args, $path = '/DownloadStation/task.cgi')
	{
		$this->log('Calling synology server: ' . trim($this->url, '/') . $path, Logger::INFO);

		if (! $this->client)
		{
			$this->setClient(new Client([
					'verify' => false
			]));
		}

		$args['version'] = 2;

		if (! key_exists('api', $args))
		{
			$args['api'] = 'SYNO.DownloadStation.Task';
		}
		if (! key_exists('_sid', $args) && (! key_exists('method', $args) || $args['method'] != 'login'))
		{
			$args['_sid'] = $this->synologyApiLogin();
		}

		$reducedArgs = $args;
		if (key_exists('passwd', $reducedArgs))
		{
			$reducedArgs['passwd'] = 'XXX';
		}
		$this->log('Arguments are: ' . print_r($reducedArgs, true));

		$res = $this->client->request('post', trim($this->url, '/') . $path, [
				'body' => http_build_query($args)
		]);
		$decRes = json_decode(method_exists($res, 'getContents') ? $res->getBody()->getContents() : $res->getBody());
		$this->log('Response was:' . print_r($decRes, true));
		if (! isset($decRes->success) || ! $decRes->success)
		{
			throw new \RuntimeException("Got error response from Syno-Api:\n" . "REQUEST-INFO:\n" . print_r($decRes, true));
		}

		$this->log('Call successfully on synology server: ' . trim($this->url, '/') . $path, Logger::INFO);

		return $decRes;
	}

	/**
	 * Does the login on ther Synology station and returns the sid fur further
	 * api calls.
	 * The sid is cached.
	 *
	 * @return string
	 */
	public function synologyApiLogin ()
	{
		if ($this->sid === null)
		{
			$args = array(
					'format' => 'cookie',
					'session' => 'DownloadStation',
					'api' => 'SYNO.API.Auth',
					'method' => 'login',
					'account' => $this->username,
					'passwd' => $this->password
			);
			$decRes = $this->synologyApiCall($args, '/auth.cgi');

			if (isset($decRes->data->sid))
			{
				$this->sid = $decRes->data->sid;
			}
			else
			{
				$this->sid = false;
			}
		}
		return $this->sid;
	}
}