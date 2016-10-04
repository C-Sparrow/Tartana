<?php
namespace Tartana\Host;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SimpleBus\Message\Bus\MessageBus;
use Tartana\Entity\Download;

/**
 * The interface for a downloader.
 */
interface HostInterface
{

	/**
	 * Fetches additional links to download for the given link.
	 * This is usefule if a hoster supports playlist links or similar. If the returned list
	 *
	 * @param string $link
	 * @return string[]
	 */
	public function fetchLinkList($link);

	/**
	 * Prefetches download information like file name size, etc.
	 *
	 * @param \Tartana\Entity\Download[] $downloads
	 */
	public function fetchDownloadInfo(array $downloads);

	/**
	 * Downloads the given downloads objects.
	 *
	 * @param \Tartana\Entity\Download[] $downloads
	 */
	public function download(array $downloads);

	public function setLogger(LoggerInterface $logger = null);

	public function setCommandBus(MessageBus $commandBus = null);
}
