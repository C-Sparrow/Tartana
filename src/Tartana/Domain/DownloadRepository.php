<?php
namespace Tartana\Domain;

/**
 * The repository interface which manages downloads.
 */
interface DownloadRepository
{

	/**
	 * Returns an array of downloads.
	 * The state can be an integer or an array of integers.
	 *
	 * @param array|integer $state
	 * @return \Tartana\Entity\Download[]
	 * @see \Tartana\Entity\Download::getState()
	 */
	public function findDownloads ($state = null);

	/**
	 * Returns an array of downloads which match the given destination.
	 *
	 * @param string $destination
	 * @return Download[]
	 */
	public function findDownloadsByDestination ($destination);
}