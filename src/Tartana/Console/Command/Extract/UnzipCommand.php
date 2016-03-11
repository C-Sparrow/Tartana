<?php
namespace Tartana\Console\Command\Extract;
use League\Flysystem\Adapter\AbstractAdapter;
use Tartana\Util;
use Symfony\Component\Console\Command\Command;

class UnzipCommand extends ExtractCommand
{

	protected function isSuccessfullFinished ($output)
	{
		return strpos($output, 'cannot find zipfile directory') === false && strpos($output, 'archive had fatal errors') === false;
	}

	protected function getExtractCommand ($password, AbstractAdapter $source, AbstractAdapter $destination)
	{
		/*
		 * The unzip command:
		 * -p: The password
		 */
		return 'unzip -o -P ' . escapeshellarg($password) . ' ' . escapeshellarg($source->applyPathPrefix('*.zip')) . ' -d ' .
				 $destination->getPathPrefix() . ' 2>&1';
	}

	protected function getFilesToDelete (AbstractAdapter $source)
	{
		$filesToDelete = [];
		foreach ($source->listContents() as $file)
		{
			if (! Util::endsWith($file['path'], '.zip'))
			{
				continue;
			}

			$filesToDelete[] = $file['path'];
		}
		return $filesToDelete;
	}
}
