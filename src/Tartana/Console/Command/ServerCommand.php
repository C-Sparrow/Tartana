<?php
namespace Tartana\Console\Command;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;

class ServerCommand extends \Symfony\Component\Console\Command\Command
{
	use LoggerAwareTrait;

	private $commandRunner = null;

	public function __construct (Runner $commandRunner)
	{
		parent::__construct('server');
		$this->commandRunner = $commandRunner;
	}

	protected function configure ()
	{
		$this->setDescription('Runs Tartana own web server!');

		$this->addArgument('action', InputArgument::OPTIONAL, 'The command for the server, can be start or stop.', 'start');
		$this->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'The port listening to.', 8000);
		$this->addOption('background', 'b', InputOption::VALUE_NONE, 'Should start on background.');
	}

	protected function execute (InputInterface $input, OutputInterface $output)
	{
		// Getting arguments
		$action = $input->getArgument('action');
		$port = (int) $input->getOption('port');
		$environment = $input->getOption('env');
		$background = (boolean) $input->getOption('background');

		$pidFile = 'server_' . $environment . '.pid';
		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp/');
		if ($action == 'start')
		{
			if ($fs->has($pidFile))
			{
				$pids = explode(':', $fs->read($pidFile)['contents']);
				if ($pids && count($pids) > 1 && Util::isPidRunning($pids[0]) && Util::isPidRunning($pids[1]))
				{
					$this->log('Web server is already running with the pids ' . implode(':', $pids), Logger::INFO);
					return;
				}
			}

			if ($background)
			{
				$command = Command::getAppCommand('server');
				$command->setAsync(true);
				$command->setCaptureErrorInOutput(true);
				$command->addArgument('-p ' . $port);
				$this->commandRunner->execute($command);

				$this->log('Started server in background mode.', Logger::INFO);
				return;
			}

			$this->log('Starting server on port ' . $port, Logger::INFO);

			// On restricted environments we need to be in the web folder
			chdir(TARTANA_PATH_ROOT . '/web');
			$command = new Command('php');
			$command->setAsync(true);
			$command->setCaptureErrorInOutput(true);
			$command->addArgument('-S 0.0.0.0:' . $port, false);
			$command->addArgument(
					TARTANA_PATH_ROOT . '/vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Resources/config/router_' . $environment . '.php');
			$pid = $this->commandRunner->execute($command);

			$fs->write($pidFile, getmypid() . ':' . $pid, new Config());

			do
			{
				$output = trim($this->commandRunner->execute(Command::getAppCommand('default')));
				if ($output)
				{
					$this->log('Default command returned with output, set log level to debug to get the reason', Logger::ERROR);
					$this->log('Output was: ' . $output);
				}
				else
				{
					// @codeCoverageIgnoreStart
					sleep(10);
					// @codeCoverageIgnoreEnd
				}
			}
			while (! $output);
		}
		if ($action == 'stop' && $fs->has($pidFile))
		{
			$pids = explode(':', $fs->read($pidFile)['contents']);
			if ($pids && count($pids) > 1 && Util::isPidRunning($pids[0]) && Util::isPidRunning($pids[1]))
			{
				$this->log('Web server is running with the pids ' . implode(':', $pids) . ' killing it', Logger::INFO);

				$command = new Command('kill');
				$command->addArgument('-9');
				$command->addArgument($pids[0]);

				$output = $this->commandRunner->execute($command);
				$this->log('Output of main server kill is: ' . $output);

				$command = new Command('kill');
				$command->addArgument('-9');
				$command->addArgument($pids[1]);

				$output = $this->commandRunner->execute($command);
				$this->log('Output of server kill is: ' . $output);
			}
			$fs->delete($pidFile);
		}
	}
}
