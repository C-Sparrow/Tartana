<?php
namespace Tests\Unit\Tartana\Console\Command;

use League\Flysystem\Adapter\Local;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Console\Command\ServerCommand;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class ServerCommandTest extends TartanaBaseTestCase
{

	public function testStartServer()
	{
		$runner = $this->getMockRunner(
			[
				$this->callback(function (Command $command) {
					return strpos($command, '-S 0.0.0.0:') !== false;
				}),
				$this->callback(function (Command $command) {
					return strpos($command, "app.php' 'default'") !== false;
				})
			],
			[
				20,
				'Good job!'
			]
		);

		$command = new ServerCommand($runner);
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$command       = $application->find('server');
		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$commandTester->execute([
			'command' => $command->getName(),
			'action' => 'start',
			'--env' => 'test'
		]);
	}

	public function testStartServerWithPort()
	{
		$runner = $this->getMockRunner(
			[
				$this->callback(function (Command $command) {
					return strpos($command, '-S 0.0.0.0:9999') !== false;
				}),
				$this->callback(function (Command $command) {
					return strpos($command, "app.php' 'default'") !== false;
				})
			],
			[
				20,
				'Good job'
			]
		);

		$command = new ServerCommand($runner);
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$command       = $application->find('server');
		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$commandTester->execute([
			'command' => $command->getName(),
			'action' => 'start',
			'--port' => 9999,
			'--env' => 'test'
		]);
	}
}
