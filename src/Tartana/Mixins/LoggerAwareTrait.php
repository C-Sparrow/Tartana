<?php
namespace Tartana\Mixins;

use Monolog\Logger;
use Psr\Log\LoggerInterface;

trait LoggerAwareTrait
{

	private $logger;

	public function getLogger()
	{
		return $this->logger;
	}

	public function setLogger(LoggerInterface $logger = null)
	{
		$this->logger = $logger;
	}

	public function log($message, $level = Logger::DEBUG)
	{
		if ($this->logger) {
			$reflect = new \ReflectionClass($this);
			$this->logger->log($level, $message, [
					$reflect->getShortName()
			]);
		}
	}
}
