<?php
namespace Tartana\Console\Command;

use League\Flysystem\Adapter\Local;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Event\ProcessingCompletedEvent;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;
use League\Flysystem\Config;

class ConvertSoundCommand extends SymfonyCommand
{
	use LoggerAwareTrait;

	protected $dispatcher = null;

	protected $commandRunner = null;

	public function __construct(Runner $commandRunner, EventDispatcherInterface $dispatcher = null)
	{
		parent::__construct('convert:sound');

		$this->dispatcher = $dispatcher;
		$this->commandRunner = $commandRunner;
	}

	protected function configure()
	{
		$this->setDescription('Converts mp4 files to mp3. This command is running in foreground!');

		$this->addArgument('source', InputArgument::REQUIRED, 'The folder with files to convert.');
		$this->addArgument('destination', InputArgument::REQUIRED, 'The folder to convert the files to.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$source = Util::realPath($input->getArgument('source'));
		if (empty($source)) {
			$this->log('Source directory no found to convert.', Logger::ERROR);
			return;
		}
		$destination = Util::realPath($input->getArgument('destination'));
		if (empty($destination)) {
			$this->log('Destination directory no found to convert.', Logger::ERROR);
			return;
		}

		if (! $this->commandRunner->execute(new Command('which ffmpeg'))) {
			$this->log("FFmpeg is not on the path, can't convert video files to mp3", Logger::INFO);
			return;
		}

		$destination = new Local($destination);
		$source = new Local($source);

		$success = true;
		foreach ($source->listContents('', true) as $file) {
			if (! Util::endsWith($file['path'], '.mp4')) {
				continue;
			}

			$command = new Command('ffmpeg');
			$command->setAsync(false);
			$command->addArgument('-i');
			$command->addArgument($source->applyPathPrefix($file['path']));
			$command->addArgument('-f mp3', false);
			$command->addArgument('-ab 192k', false);
			$command->addArgument('-y', false);
			$command->addArgument('-vn');
			$command->addArgument(str_replace('.mp4', '.mp3', $destination->applyPathPrefix($file['path'])));

			$this->log('Running ffmpeg system command: ' . $command);

			$output = $this->commandRunner->execute($command, function ($line) use ($output) {
				$output->writeln($line);
			});

			if (Util::endsWith($output, 'Invalid data found when processing input')) {
				$success = false;
				$source->write($file['path'] . '.out', $output, new Config());
			}
		}

		if ($this->dispatcher) {
			$this->dispatcher->dispatch('processing.completed', new ProcessingCompletedEvent($source, $destination, $success));
		}
	}
}
