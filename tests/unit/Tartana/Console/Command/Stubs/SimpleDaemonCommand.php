<?php
namespace Tests\Unit\Tartana\Console\Command\Stubs;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tartana\Component\Command\Command;
use Tartana\Console\Command\AbstractDaemonCommand;
use Symfony\Component\Console\Input\InputOption;

class SimpleDaemonCommand extends AbstractDaemonCommand
{

	public $started = false;

	protected function configure()
	{
		parent::configure();

		$this->setName('simple');
		$this->addOption('env', 'e', InputOption::VALUE_REQUIRED);
	}

	protected function doWork(InputInterface $input, OutputInterface $output)
	{
		$this->started = true;
	}
}
