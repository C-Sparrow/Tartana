<?php
namespace Tests\Unit\Tartana\Console\Command;

use League\Flysystem\Adapter\Local;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tests\Unit\Tartana\Console\Command\Stubs\SimpleDaemonCommand;
use Tests\Unit\Tartana\TartanaBaseTestCase;
use League\Flysystem\Config;

class AbstractDaemonCommandTest extends TartanaBaseTestCase
{

	const PID_FILE_NAME = 'simple_test.pid';

	public function testStart()
	{
		$command = new SimpleDaemonCommand($this->getMockRunner());
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'start',
				'--env' => 'test'
		]);

		$this->assertTrue($command->started);
		$this->assertFalse($fs->has(self::PID_FILE_NAME));
	}

	public function testNoActionSet()
	{
		$command = new SimpleDaemonCommand($this->getMockRunner());
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$commandTester->execute([
				'command' => $command->getName(),
				'--env' => 'test'
		]);

		$this->assertTrue($command->started);
		$this->assertFalse($fs->has(self::PID_FILE_NAME));
	}

	public function testStartEmptyFile()
	{
		$command = new SimpleDaemonCommand($this->getMockRunner());
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$fs->write(self::PID_FILE_NAME, '', new Config());

		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'start',
				'--env' => 'test'
		]);

		$this->assertTrue($command->started);
		$this->assertFalse($fs->has(self::PID_FILE_NAME));
	}

	public function testStartAlreadyStarted()
	{
		$command = new SimpleDaemonCommand($this->getMockRunner());
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$fs->write(self::PID_FILE_NAME, getmypid(), new Config());

		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'start',
				'--env' => 'test'
		]);

		$this->assertFalse($command->started);
		$this->assertTrue($fs->has(self::PID_FILE_NAME));
		$this->assertEquals(getmypid(), $fs->read(self::PID_FILE_NAME)['contents']);
	}

	public function testStartDeadPids()
	{
		$runner = $this->getMockRunner(
				[
						$this->callback(function (Command $command)
						{
							return strpos($command, 'kill') !== false;
						})
				]);

		$command = new SimpleDaemonCommand($runner);
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$fs->write(self::PID_FILE_NAME, getmypid() . ':128761291727182538765123', new Config());

		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'start',
				'--env' => 'test'
		]);

		$this->assertTrue($command->started);
		$this->assertFalse($fs->has(self::PID_FILE_NAME));
	}

	public function testStartInBackground()
	{
		$runner = $this->getMockRunner(
				[
						$this->callback(
								function (Command $command)
								{
									return strpos($command, 'simple') !== false && strpos($command, '--backgound') === false;
								})
				]);

		$command = new SimpleDaemonCommand($runner);
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'start',
				'--env' => 'test',
				'--background' => 1
		]);

		$this->assertFalse($command->started);
		$this->assertFalse($fs->has(self::PID_FILE_NAME));
	}

	public function testStop()
	{
		$runner = $this->getMockRunner(
				[
						$this->callback(
								function (Command $command)
								{
									return strpos($command, "kill '-9' '" . getmypid() . "'") !== false;
								})
				]);

		$command = new SimpleDaemonCommand($runner);
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$fs->write(self::PID_FILE_NAME, getmypid() . ':8963986132896123', new Config());

		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'stop',
				'--env' => 'test'
		]);

		$this->assertFalse($command->started);
		$this->assertFalse($fs->has(self::PID_FILE_NAME));
	}

	protected function tearDown()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		if ($fs->has(self::PID_FILE_NAME))
		{
			$fs->delete(self::PID_FILE_NAME);
		}
	}
}
