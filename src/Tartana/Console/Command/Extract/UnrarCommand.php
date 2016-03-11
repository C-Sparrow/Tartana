<?php
namespace Tartana\Console\Command\Extract;
use League\Flysystem\Adapter\AbstractAdapter;
use Tartana\Event\ExtractProgressEvent;
use Tartana\Util;
use Symfony\Component\Console\Command\Command;

class UnrarCommand extends ExtractCommand
{

	private $lastFileName = null;

	protected function isSuccessfullFinished ($output)
	{
		return Util::endsWith($output, 'All OK');
	}

	protected function getExtractCommand ($password, AbstractAdapter $source, AbstractAdapter $destination)
	{
		if ($password == '')
		{
			$password = '-';
		}

		/*
		 * The unrar command:
		 * x: Extract
		 * -y: Answer all with yes, no interaction
		 * -r: Recursive all rar files
		 * -o+: Overwrite files
		 * -p: The password
		 */
		return 'unrar x -y -r -o- -p' . $password . ' "' . $source->applyPathPrefix('*.rar') . '" ' . $destination->getPathPrefix();
	}

	protected function getFilesToDelete (AbstractAdapter $source)
	{
		$filesToDelete = [];

		$list = shell_exec('unrar l -v "' . $source->applyPathPrefix('*.rar') . '"');

		// Delet the files which do belong to successfull unrar
		preg_match_all("/^(Archive:?|Volume) (.*)/m", $list, $matches);
		if (isset($matches[2]))
		{
			foreach ($matches[2] as $match)
			{
				$filesToDelete[] = $source->removePathPrefix($match);
			}
		}
		return $filesToDelete;
	}

	protected function processLine ($line, AbstractAdapter $source, AbstractAdapter $destination)
	{
		parent::processLine($line, $source, $destination);

		if ($this->dispatcher)
		{
			if (Util::startsWith($line, 'Extracting from '))
			{
				$this->lastFileName = trim(str_replace('Extracting from ' . $source->getPathPrefix(), '', $line));
			}
			if ($this->lastFileName && preg_match("/\s[0-9]+%$/", $line, $matches))
			{
				$this->dispatcher->dispatch('extract.progress', new ExtractProgressEvent($source, $destination, $this->lastFileName, $matches[0]));
			}
		}
	}
}
