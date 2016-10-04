<?php
namespace Tartana\Console\Command\Extract;

use League\Flysystem\Adapter\AbstractAdapter;
use Tartana\Component\Command\Command;
use Tartana\Event\ProcessingProgressEvent;
use Tartana\Util;

class UnrarCommand extends ExtractCommand
{

	private $lastFileName = null;

	protected function isSuccessfullFinished($output)
	{
		return Util::endsWith($output, 'All OK');
	}

	protected function getExtractCommand($password, AbstractAdapter $source, AbstractAdapter $destination)
	{
		if ($password == '') {
			$password = '-';
		}

		$command = new Command('unrar');
		// Extract
		$command->addArgument('x', false);
		// Set all to yes
		$command->addArgument('-y', false);
		// Recursive
		$command->addArgument('-r');
		// Overwrite existing files
		$command->addArgument('-o-');
		// Password
		$command->addArgument('-p' . $password);
		// Input files
		$command->addArgument($source->applyPathPrefix('*.rar'));
		// Output
		$command->addArgument($destination->getPathPrefix());

		return $command;
	}

	protected function getFilesToDelete(AbstractAdapter $source)
	{
		$filesToDelete = [];

		$command = new Command('unrar');
		$command->addArgument('l', false);
		$command->addArgument('-v', false);
		$command->addArgument($source->applyPathPrefix('*.rar'));
		$list = $this->runner->execute($command);

		// Delet the files which do belong to successfull unrar
		preg_match_all("/^(Archive:?|Volume) (.*)/m", $list, $matches);
		if (isset($matches[2])) {
			foreach ($matches[2] as $match) {
				$filesToDelete[] = $source->removePathPrefix($match);
			}
		}
		return $filesToDelete;
	}

	protected function processLine($line, AbstractAdapter $source, AbstractAdapter $destination)
	{
		parent::processLine($line, $source, $destination);

		if ($this->dispatcher) {
			if (Util::startsWith($line, 'Extracting from ')) {
				$this->lastFileName = trim(str_replace('Extracting from ' . $source->getPathPrefix(), '', $line));
			}
			if ($this->lastFileName && preg_match("/\s[0-9]+%$/", $line, $matches)) {
				$this->dispatcher->dispatch(
					'processing.progress',
					new ProcessingProgressEvent($source, $destination, $this->lastFileName, $matches[0])
				);
			}
		}
	}
}
