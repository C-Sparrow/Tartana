<?php
namespace Tartana\Host;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SimpleBus\Message\Bus\MessageBus;
use Tartana\Entity\Download;

/**
 * The repository interface which manages the environment.
 */
interface HostInterface
{

	/**
	 *
	 * @param Tartana\Entity\Download[] $downloads
	 */
	public function download (array $downloads);

	public function setLogger (LoggerInterface $logger = null);

	public function setCommandBus (MessageBus $commandBus = null);
}