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
		/*
		 * The 7z command:
		 * x: extract command with folder structure
		 * -p: The password
		 */
		return '7z x -y -p' . escapeshellarg($password) . ' ' . escapeshellarg($source->applyPathPrefix('*.zip')) . ' -o' .
				 $destination->getPathPrefix() . ' 2>&1';
	}

	protected function getFilesToDelete (AbstractAdapter $source)
	{
		$filesToDelete = [];
		foreach ($source->listContents() as $file)
		{
			if (! Util::endsWith($file['path'], '.7z') && ! Util::endsWith($file['path'], '.zip'))
			{
				continue;
			}

			$filesToDelete[] = $file['path'];
		}
		return $filesToDelete;
	}
}
