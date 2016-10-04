<?php
namespace Synology\Handler;

use GuzzleHttp\ClientInterface;
use Joomla\Registry\Registry;
use Tartana\Domain\Command\ProcessLinks;
use Synology\Mixins\SynologyApiTrait;
use Local\Handler\LocalProcessLinksHandler;

class SynologyProcessLinksHandler
{
	use SynologyApiTrait;

	private $configuration = null;

	public function __construct(ClientInterface $client, Registry $configuration)
	{
		$this->setClient($client);
		$this->setUrl($configuration->get('synology.address', 'https://localhost:5001/webapi'));
		$this->setUsername($configuration->get('synology.username', 'admin'));
		$this->setPassword($configuration->get('synology.password', 'admin'));

		$this->configuration = $configuration;
	}

	public function handle(ProcessLinks $links)
	{
		$destinationFolder = LocalProcessLinksHandler::createJobDir($this->configuration->get('downloads'), false);
		// Something is wrong
		if (empty($destinationFolder)) {
			return;
		}

		$args = array(
				'method' => 'create',
				'destination' => trim($this->configuration->get('synology.downloadShare'), '/') . '/' . $destinationFolder,
				'uri' => implode(',', $links->getLinks())
		);
		$this->synologyApiCall($args);
	}
}
