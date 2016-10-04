<?php
namespace Tartana\Console\Command;

use League\Flysystem\Config;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tartana\Component\Command\Command;

class ServerCommand extends AbstractDaemonCommand
{

	protected function configure()
	{
		parent::configure();

		$this->setName('server');
		$this->setDescription('Runs Tartana own web server!');

		$this->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'The port listening to.', 8000);
	}

	protected function doWork(InputInterface $input, OutputInterface $output)
	{
		// Getting arguments
		$port = (int)$input->getOption('port');
		$environment = $input->getOption('env');

		$this->log('Starting server on port ' . $port, Logger::INFO);

		// On restricted environments we need to be in the web folder
		chdir(TARTANA_PATH_ROOT . '/web');
		$command = new Command('php');
		$command->setAsync(true);
		$command->setCaptureErrorInOutput(true);
		$command->addArgument('-S 0.0.0.0:' . $port, false);
		$command->addArgument(
			TARTANA_PATH_ROOT . '/vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Resources/config/router_' . $environment . '.php'
		);
		$pid = $this->getCommandRunner()->execute($command);

		$this->attachPid($input, $pid);

		do {
			$output = trim($this->getCommandRunner()->execute(Command::getAppCommand('default')));
			if ($output) {
				$this->log('Default command returned with output, set log level to debug to get the reason', Logger::ERROR);
				$this->log('Output was: ' . $output);
			} else {
				// @codeCoverageIgnoreStart
				sleep(10);
				// @codeCoverageIgnoreEnd
			}
		} while (!$output);
	}
}
