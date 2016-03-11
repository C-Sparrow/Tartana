<?php
namespace Tartana\Component\Command;
use Tartana\Mixins\LoggerAwareTrait;
use Symfony\Component\Process\Process;

class Runner
{

	use LoggerAwareTrait;

	private $environment = null;

	public function __construct ($environment = null)
	{
		$this->environment = $environment;
	}

	/**
	 * Executes the given command and returns the output.
	 * If async is true, the process id is returned.
	 * If a file is given all the output will be piped to that file.
	 *
	 * @param \Tartana\Component\Command $command
	 * @return string|integer
	 */
	public function execute (Command $command)
	{
		if ($this->environment !== null)
		{
			// Setting the correct environment
			$isAppCommand = false;
			$envSet = false;
			foreach ($command->getArguments() as $arg)
			{
				if (strpos($arg, '/cli/app.php') !== false)
				{
					$isAppCommand = true;
				}

				// If the argument has -e or --env replace it
				if ($isAppCommand && (strpos($arg, '-e ') !== false || strpos($arg, '--env ') !== false))
				{
					$command->replaceArgument($arg, '--env ' . $this->environment, false);
					$envSet = true;
				}
			}

			if ($isAppCommand && ! $envSet)
			{
				$command->addArgument('--env ' . $this->environment, false);
			}
		}

		$this->log('Running real command on runner: ' . $command);
		$output = shell_exec($command);
		$this->log('Finished real command on runner: ' . $command);

		return trim($output);
	}
}