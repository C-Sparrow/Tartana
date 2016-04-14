<?php
namespace Tartana\Console\Command\Extract;
use League\Flysystem\Adapter\AbstractAdapter;
use Tartana\Component\Command\Command;
use Tartana\Util;

class SevenzCommand extends ExtractCommand
{

	protected function configure ()
	{
		parent::configure();

		$this->setName('7z');
	}

	protected function isSuccessfullFinished ($output)
	{
		return strpos($output, 'Everything is Ok') !== false;
	}

	protected function getExtractCommand ($password, AbstractAdapter $source, AbstractAdapter $destination)
	{
		$command = new Command('7z');
		// Extract
		$command->addArgument('x', false);
		// Set all to yes
		$command->addArgument('-y', false);
		// Password
		$command->addArgument('-p' . $password);
		// Input files
		$command->addArgument($source->applyPathPrefix('*.' . $this->getFileExtension() . '*'));
		// Output
		$command->addArgument('-o' . $destination->getPathPrefix());

		return $command;
	}

	protected function getFilesToDelete (AbstractAdapter $source)
	{
		$filesToDelete = [];
		foreach ($source->listContents() as $file)
		{
			// Multipart archives do have the naming pattern test.7z.001
			if (! Util::endsWith($file['path'], '.' . $this->getFileExtension()) && strpos($file['path'], '.7z.') === false)
			{
				continue;
			}

			$filesToDelete[] = $file['path'];
		}
		return $filesToDelete;
	}

	/**
	 * Returns the file extension this command is processing.
	 * Subclasses can override as the 7z command can handle multiple file
	 * formats, eg. zip files.
	 *
	 * @return string
	 */
	protected function getFileExtension ()
	{
		return '7z';
	}
}
