<?php
namespace Tartana\Component\Command;

use Symfony\Component\Process\Process;
use Tartana\Mixins\LoggerAwareTrait;

class Runner
{

	use LoggerAwareTrait;

	private $environment = null;

	public function __construct($environment = null)
	{
		$this->environment = $environment;
	}

	/**
	 * Executes the given command and returns the output.
	 * If async is true, the process id is returned.
	 * If a file is given all the output will be piped to that file.
	 *
	 * If a callback is set the output will be delegated line by line to the
	 * given callback, then the given command will never run in async mode!
	 *
	 * @param \Tartana\Component\Command\Command $command
	 * @param $callback
	 * @return string|integer
	 */
	public function execute(Command $command, $callback = null)
	{
		if ($this->environment !== null) {
			// Setting the correct environment
			$isAppCommand = false;
			$envSet       = false;
			foreach ($command->getArguments() as $arg) {
				if (strpos($arg, '/cli/app.php') !== false) {
					$isAppCommand = true;
				}

				// If the argument has -e or --env replace it
				if ($isAppCommand && (strpos($arg, '-e ') !== false || strpos($arg, '--env ') !== false)) {
					$command->replaceArgument($arg, '--env ' . $this->environment, false);
					$envSet = true;
				}
			}

			if ($isAppCommand && !$envSet) {
				$command->addArgument('--env ' . $this->environment, false);
			}
		}

		$this->log('Running real command on runner: ' . $command);
		$process = new Process((string)$command);
		$process->setTimeout(null);
		$process->setIdleTimeout(null);

		if ($callback) {
			// Can not get the output of an async command
			$command->setAsync(false);

			$process->run(function ($type, $buffer) use ($callback) {
				$callback($buffer);
			});
		} else {
			$process->run();
		}
		$this->log('Finished real command on runner: ' . $command);

		return trim($process->getOutput());
	}
}
