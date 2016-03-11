<?php
namespace Tartana\Domain;

interface LogRepository
{

	/**
	 * Returns an array of logs.
	 *
	 * @param integer $count
	 * @return \Tartana\Entity\Log[]
	 */
	public function findLogs ($count = 10);
}