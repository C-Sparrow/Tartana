<?php
namespace Tartana\Handler;

use League\Flysystem\Adapter\Local;
use Tartana\Domain\Command\DeleteLogs;

class DeleteFileLogsHandler
{

	private $logFile = null;

	public function __construct($logFile)
	{
		$this->logFile = $logFile;
	}

	public function handle(DeleteLogs $command)
	{
		if (! file_exists($this->logFile)) {
			return;
		}

		$fs = new Local(dirname($this->logFile));
		$fs->delete($fs->removePathPrefix($this->logFile));
	}
}
