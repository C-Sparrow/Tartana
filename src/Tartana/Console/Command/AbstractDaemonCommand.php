<?php
namespace Tartana\Console\Command;

use League\Flysystem\Adapter\Local;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;
use League\Flysystem\Config;

abstract class AbstractDaemonCommand extends \Symfony\Component\Console\Command\Command
{
	use LoggerAwareTrait;

	private $commandRunner = null;

	public function __construct(Runner $commandRunner)
	{
		parent::__construct();

		$this->commandRunner = $commandRunner;
	}

	/**
	 * The long running work.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	abstract protected function doWork(InputInterface $input, OutputInterface $output);

	protected function configure()
	{
		$this->addArgument('action', InputArgument::OPTIONAL, 'The action, can be start or stop.', 'start');
		$this->addOption('background', 'b', InputOption::VALUE_NONE, 'Should start on background.');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// Getting arguments
		$action = $input->getArgument('action');
		$environment = $input->getOption('env');
		$background = (boolean)$input->getOption('background');

		$pidFile = $this->getName() . '_' . $environment . '.pid';
		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp/');
		if ($action == 'start')
		{
			if ($fs->has($pidFile))
			{
				$pids = array_filter(explode(':', $fs->read($pidFile)['contents']));
				if (!empty($pids))
				{
					$runningPids = [];
					foreach ($pids as $key => $pid)
					{
						if (!Util::isPidRunning($pid))
						{
							continue;
						}

						$runningPids[$key] = $pid;
					}

					if ($runningPids == $pids)
					{
						$this->log('Daemon for command ' . $this->getName() . ' is already running with the pids ' . implode(':', $pids),
								Logger::INFO);
						return;
					}
					else
					{
						// Killing all running processes and starting again
						foreach ($runningPids as $pid)
						{
							$this->killPid($pid);
						}
						$fs->delete($pidFile);
					}
				}
			}

			if ($background)
			{
				// Stripping out the not needed tokens
				$inputString = (string)$input;
				$inputString = str_replace([
						'-b ',
						'--backgound ',
						$this->getName() . ' '
				], '', $inputString);

				$command = Command::getAppCommand($this->getName());
				$command->setAsync(true);
				$command->setCaptureErrorInOutput(true);
				$command->addArgument($inputString, false);
				$this->getCommandRunner()->execute($command);

				$this->log('Started daeomon for command ' . $this->getName() . ' in background mode.', Logger::INFO);
				return;
			}

			$this->attachPid($input, getmypid());

			$this->doWork($input, $output);
			$fs->delete($pidFile);
		}
		if ($action == 'stop' && $fs->has($pidFile))
		{
			$pids = explode(':', $fs->read($pidFile)['contents']);
			if (!empty($pids))
			{
				$this->log('Daemon for command ' . $this->getName() . ' is running with the pids ' . implode(':', $pids) . ' killing it',
						Logger::INFO);

				foreach ($pids as $pid)
				{
					if (!Util::isPidRunning($pid))
					{
						continue;
					}
					$this->killPid($pid);
				}
			}
			$fs->delete($pidFile);

			$this->log('Daemon for command ' . $this->getName() . ' stopped', Logger::INFO);
		}
	}

	/**
	 *
	 * @return \Tartana\Component\Command\Runner
	 */
	protected function getCommandRunner()
	{
		return $this->commandRunner;
	}

	protected function attachPid(InputInterface $input, $pid)
	{
		$environment = $input->getOption('env');

		$pidFile = $this->getName() . '_' . $environment . '.pid';
		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp/');

		$pids = [];
		if ($fs->has($pidFile))
		{
			$pids = array_filter(explode(':', $fs->read($pidFile)['contents']));
		}
		if (!in_array($pid, $pids))
		{
			$pids[] = $pid;
		}

		$fs->write($pidFile, implode(':', $pids), new Config());
	}

	private function killPid($pid)
	{
		$command = new Command('kill');
		$command->addArgument('-9');
		$command->addArgument($pid);

		$output = $this->getCommandRunner()->execute($command);
		$this->log('Output of daemon ' . $this->getName() . ' kill is for pid ' . $pid . ' is: ' . $output);
	}
}
