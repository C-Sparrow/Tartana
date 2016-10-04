<?php
namespace Tartana\Console\Command\Extract;

use League\Flysystem\Adapter\AbstractAdapter;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Util;

class UnzipCommand extends SevenzCommand
{

	private $executable = null;

	protected function configure()
	{
		parent::configure();

		$this->setName('unzip');
	}

	protected function isSuccessfullFinished($output)
	{
		if ($this->is7zAvailable()) {
			return parent::isSuccessfullFinished($output);
		} else {
			return strpos($output, 'cannot find zipfile directory') === false && strpos($output, 'archive had fatal errors') === false;
		}
	}

	protected function getExtractCommand($password, AbstractAdapter $source, AbstractAdapter $destination)
	{
		if ($this->is7zAvailable()) {
			return parent::getExtractCommand($password, $source, $destination);
		} else {
			$command = new Command('unzip');
			// Overwrite existing files
			$command->addArgument('-o');
			// Password
			$command->addArgument('-p' . $password);
			// Input files
			$command->addArgument($source->applyPathPrefix('*.zip'));
			// Output
			$command->addArgument('-d ' . $destination->getPathPrefix());

			return $command;
		}
	}

	protected function getFilesToDelete(AbstractAdapter $source)
	{
		$filesToDelete = [];
		foreach ($source->listContents() as $file) {
			if (! Util::endsWith($file['path'], '.zip')) {
				continue;
			}

			$filesToDelete[] = $file['path'];
		}
		return $filesToDelete;
	}

	protected function getFileExtension()
	{
		return 'zip';
	}

	private function is7zAvailable()
	{
		if ($this->executable === null) {
		// On the DSM 6 7z is available only
			$cmd = new Command('which');
			$cmd->addArgument('7z');
			$cmd->setAsync(false);
			if ($this->runner->execute($cmd)) {
				$this->executable = '7z';
			} else {
				$this->executable = 'unzip';
			}
		}

		return $this->executable == '7z';
	}
}
