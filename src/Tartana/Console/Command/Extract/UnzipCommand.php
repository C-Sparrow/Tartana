<?php
namespace Tartana\Console\Command\Extract;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\AbstractAdapter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Util;

class UnzipCommand extends SevenzCommand
{

	private $executable = '';

	public function __construct (EventDispatcherInterface $dispatcher, Registry $configuration, Runner $runner)
	{
		// Setting the command name based on the class
		parent::__construct($dispatcher, $configuration);

		// On the DSM 6 7z is available only
		$cmd = new Command('which');
		$cmd->addArgument('7z');
		$cmd->setAsync(false);
		if ($runner->execute($cmd))
		{
			$this->executable = '7z';
		}
		else
		{
			$this->executable = 'unzip';
		}
	}

	protected function configure ()
	{
		parent::configure();

		$this->setName('unzip');
	}

	protected function isSuccessfullFinished ($output)
	{
		if ($this->executable == '7z')
		{
			return parent::isSuccessfullFinished($output);
		}
		else
		{
			return strpos($output, 'cannot find zipfile directory') === false && strpos($output, 'archive had fatal errors') === false;
		}
	}

	protected function getExtractCommand ($password, AbstractAdapter $source, AbstractAdapter $destination)
	{
		if ($this->executable == '7z')
		{
			return parent::getExtractCommand($password, $source, $destination);
		}
		else
		{
			/*
			 * The unzip command:
			 * -o: Overwrite
			 * -p: The password
			 * -d: Destination to extract to
			 */
			return 'unzip -o -P ' . escapeshellarg($password) . ' ' . escapeshellarg($source->applyPathPrefix('*.zip')) . ' -d ' .
					 $destination->getPathPrefix() . ' 2>&1';
		}
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
