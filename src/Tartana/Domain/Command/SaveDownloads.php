<?php
namespace Tartana\Domain\Command;
use Tartana\Entity\Download;
use Tartana\Util;

class SaveDownloads
{

	private $downloads = null;

	public function __construct (array $downloads)
	{
		$this->downloads = Util::cloneObjects($downloads);
	}

	/**
	 *
	 * @return Download[]
	 */
	public function getDownloads ()
	{
		return $this->downloads;
	}
}