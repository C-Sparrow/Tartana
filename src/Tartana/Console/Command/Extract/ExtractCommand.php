<?php
namespace Tartana\Console\Command\Extract;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Local;
use Tartana\Event\ProcessingCompletedEvent;
use Tartana\Mixins\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use League\Flysystem\Config;
use Tartana\Component\Command\Runner;

abstract class ExtractCommand extends Command
{
	use LoggerAwareTrait;

	protected $dispatcher = null;

	protected $runner = null;

	private $configuration = null;

	public function __construct (EventDispatcherInterface $dispatcher, Runner $runner, Registry $configuration)
	{
		// Setting the command name based on the class
		parent::__construct(str_replace('command', '', strtolower((new \ReflectionClass($this))->getShortName())));

		$this->dispatcher = $dispatcher;
		$this->runner = $runner;
		$this->configuration = $configuration;
	}

	/**
	 * Returns the files to delete within the given source.
	 * Paths has to be relative to the given source.
	 *
	 * @param AbstractAdapter $source
	 * @return string[]
	 */
	abstract protected function getFilesToDelete (AbstractAdapter $source);

	/**
	 * Returns if the command has failed based on the given output.
	 *
	 * @param string $output
	 * @return boolean
	 */
	abstract protected function isSuccessfullFinished ($output);

	/**
	 * Returns the command to execute.
	 *
	 * @param string $password
	 * @param AbstractAdapter $source
	 * @param AbstractAdapter $destination
	 * @return \Tartana\Component\Command\Command
	 */
	abstract protected function getExtractCommand ($password, AbstractAdapter $source, AbstractAdapter $destination);

	/**
	 * Can be used by subclasses to do things during command execution like
	 * sending progress information on the dispatcher.
	 *
	 * @param string $line
	 * @param AbstractAdapter $source
	 * @param AbstractAdapter $destination
	 */
	protected function processLine ($line, AbstractAdapter $source, AbstractAdapter $destination)
	{
	}

	protected function configure ()
	{
		$this->setDescription('Extracts files. This command is running in foreground!');

		$this->addArgument('source', InputArgument::REQUIRED, 'The folder with files to extract.');
		$this->addArgument('destination', InputArgument::REQUIRED, 'The folder to textract the files to.');
		$this->addArgument('pwfile', InputArgument::OPTIONAL, 'The file with passwords to use to extract.');
	}

	protected function execute (InputInterface $input, OutputInterface $output)
	{
		// Getting arguments
		$source = $input->getArgument('source');
		$destination = $input->getArgument('destination');
		$pwFile = $input->getArgument('pwfile');
		$delete = $this->configuration->get('extract.deleteFiles', true);

		// Compiling passwords
		$passwords = [
				''
		];
		if ($pwFile && @file_exists($pwFile))
		{
			$pwFile = realpath($pwFile);
			$fs = new Local(dirname($pwFile));
			$pws = $fs->read(str_replace($fs->getPathPrefix(), '', $pwFile))['contents'];
			$passwords = array_merge($passwords, explode(PHP_EOL, $pws));
		}

		$destination = new Local($destination);
		$source = new Local($source);

		$output->writeln('Starting to extract folder: ' . $source->getPathPrefix() . '!');

		$success = false;

		// Extract with passwords check
		foreach ($passwords as $pw)
		{
			$me = $this;
			$command = $this->getExtractCommand($pw, $source, $destination);
			$this->log('Running pure command to extract the files: ' . $command);

			$buffer = $this->runner->execute($command,
					function  ($line) use ( $output, &$buffer, $me, $source, $destination) {
						$line = trim($line);
						if ($line)
						{
							$output->writeln($line);

							$me->processLine($line, $source, $destination);
						}
					});

			$success = $this->isSuccessfullFinished($buffer);

			// Delete the output file
			if ($source->has('extract.out'))
			{
				$source->delete('extract.out');
			}

			// If the fiels should be deleted on success
			if ($success && $delete)
			{
				$files = $this->getFilesToDelete($source);
				if (is_array($files))
				{
					foreach ($files as $file)
					{
						if ($source->has($file))
						{
							$source->delete($file);
						}
					}
				}
				if (empty($source->listContents()))
				{
					$source->deleteDir('');
				}
			}
			if ($success)
			{
				break;
			}
			else
			{
				// If we failed, write the output to the file
				$source->write('extract.out', $buffer, new Config());
			}
		}
		if ($this->dispatcher)
		{
			$this->dispatcher->dispatch('processing.completed', new ProcessingCompletedEvent($source, $destination, $success));
		}
	}
}
