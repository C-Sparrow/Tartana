<?php
namespace Tartana\Console\Command;

use League\Flysystem\Adapter\Local;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tartana\Component\Command\Command;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;

class ProcessDiscFolderCommand extends SymfonyCommand
{
	use LoggerAwareTrait;

	public function __construct()
	{
		parent::__construct('process:disc');
	}

	protected function configure()
	{
		$this->setDescription(
			'Converts folders with the name CD and which contains mp3 files to a flat structure. This command is running in foreground!'
		);

		$this->addArgument('source', InputArgument::REQUIRED, 'The folder to process.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$source = Util::realPath($input->getArgument('source'));
		if (empty($source)) {
			$this->log('Source directory no found to convert.', Logger::ERROR);
			return;
		}

		$source = new Local($source);

		foreach ($source->listContents('', true) as $directory) {
			if ($directory['type'] != 'dir') {
				// Not a directory
				continue;
			}

			$dirName = basename($directory['path']);
			if ($dirName != 'CD' && $dirName != 'Cover') {
				// Not meant to be processed
				continue;
			}

			foreach ($source->listContents($directory['path'], false) as $file) {
				if ($file['type'] != 'file') {
					continue;
				}
				$source->rename($file['path'], dirname(dirname($file['path'])) . '/' . basename($file['path']));
			}
			if (empty($source->listContents($directory['path'], false))) {
				$source->deleteDir($directory['path']);
			}
		}
	}
}
