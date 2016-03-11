<?php
namespace Local\Handler;
use League\Flysystem\Adapter\Local;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Domain\Command\StartDownloads;
use Tartana\Mixins\LoggerAwareTrait;

class LocalStartDownloadsHandler
{
	use LoggerAwareTrait;

	private $runner = null;

	public function __construct (Runner $runner)
	{
		$this->runner = $runner;
	}

	public function handle (StartDownloads $downloads)
	{
		$command = Command::getAppCommand('download');
		$command->setAsync(true);

		$this->log('Running command to download the links: ' . $command);

		// Starting the long running download script
		$this->runner->execute($command);
	}
}