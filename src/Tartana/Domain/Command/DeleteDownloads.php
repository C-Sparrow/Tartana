<?php
namespace Tartana\Domain\Command;
use Tartana\Util;

class DeleteDownloads
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