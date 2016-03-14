<?php
namespace Tests\Unit\Tartana\Console\Command;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Console\Command\ServerCommand;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class ServerCommandTest extends TartanaBaseTestCase
{

	const PID_FILE_NAME = 'server_test.pid';

	public function testStartServer ()
	{
		$runner = $this->getMockRunner(
				[
						$this->callback(function  (Command $command) {
							return strpos($command, '-S 0.0.0.0:') !== false;
						}),
						$this->callback(function  (Command $command) {
							return strpos($command, "app.php' 'default'") !== false;
						})
				], [
						20,
						'Good job'
				]);

		$command = new ServerCommand($runner);
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$command = $application->find('server');
		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'start',
				'--env' => 'test'
		]);

		$this->assertTrue($fs->has(self::PID_FILE_NAME));
		$this->assertEquals(20, $fs->read(self::PID_FILE_NAME)['contents']);
	}

	public function testStartServerWithPort ()
	{
		$runner = $this->getMockRunner(
				[
						$this->callback(function  (Command $command) {
							return strpos($command, '-S 0.0.0.0:9999') !== false;
						}),
						$this->callback(function  (Command $command) {
							return strpos($command, "app.php' 'default'") !== false;
						})
				], [
						20,
						'Good job'
				]);

		$command = new ServerCommand($runner);
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$command = $application->find('server');
		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'start',
				'--port' => 9999,
				'--env' => 'test'
		]);

		$this->assertTrue($fs->has(self::PID_FILE_NAME));
		$this->assertEquals(20, $fs->read(self::PID_FILE_NAME)['contents']);
	}

	public function testStartServerNoActionSet ()
	{
		$runner = $this->getMockRunner(
				[
						$this->callback(function  (Command $command) {
							return strpos($command, '-S 0.0.0.0:') !== false;
						}),
						$this->callback(function  (Command $command) {
							return strpos($command, "app.php' 'default'") !== false;
						})
				], [
						20,
						'Good job'
				]);

		$command = new ServerCommand($runner);
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$command = $application->find('server');
		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$commandTester->execute([
				'command' => $command->getName(),
				'--env' => 'test'
		]);

		$this->assertTrue($fs->has(self::PID_FILE_NAME));
		$this->assertEquals(20, $fs->read(self::PID_FILE_NAME)['contents']);
	}

	public function testStartServerHasRunningPid ()
	{
		$command = new ServerCommand($this->getMockRunner());
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$command = $application->find('server');
		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$fs->write(self::PID_FILE_NAME, getmypid(), new Config());
		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'start',
				'--env' => 'test'
		]);

		$this->assertTrue($fs->has(self::PID_FILE_NAME));
		$this->assertEquals(getmypid(), $fs->read(self::PID_FILE_NAME)['contents']);
	}

	public function testStartServerHasDiedPid ()
	{
		$runner = $this->getMockRunner(
				[
						$this->callback(function  (Command $command) {
							return strpos($command, '-S 0.0.0.0:') !== false;
						}),
						$this->callback(function  (Command $command) {
							return strpos($command, "app.php' 'default'") !== false;
						})
				], [
						20,
						'Good job'
				]);

		$command = new ServerCommand($runner);
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$command = $application->find('server');
		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$fs->write(self::PID_FILE_NAME, '8751238512351235328123', new Config());
		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'start',
				'--env' => 'test'
		]);

		$this->assertTrue($fs->has(self::PID_FILE_NAME));
		$this->assertEquals(20, $fs->read(self::PID_FILE_NAME)['contents']);
	}

	public function testStopServer ()
	{
		$runner = $this->getMockRunner(
				[
						$this->callback(function  (Command $command) {
							return strpos($command, "kill '-9' '" . getmypid()) !== false;
						})
				], [
						'Killed'
				]);

		$command = new ServerCommand($runner);
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$command = $application->find('server');
		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$fs->write(self::PID_FILE_NAME, getmypid(), new Config());
		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'stop',
				'--env' => 'test'
		]);

		$this->assertFalse($fs->has(self::PID_FILE_NAME));
	}

	public function testStopServerInvalidPid ()
	{
		$command = new ServerCommand($this->getMockRunner());
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$command = $application->find('server');
		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$fs->write(self::PID_FILE_NAME, '8751238512351235328123', new Config());
		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'stop',
				'--env' => 'test'
		]);

		$this->assertFalse($fs->has(self::PID_FILE_NAME));
	}

	public function testStopServerNoPidFile ()
	{
		$command = new ServerCommand($this->getMockRunner());
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$command = $application->find('server');
		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		$commandTester->execute([
				'command' => $command->getName(),
				'action' => 'stop',
				'--env' => 'test'
		]);

		$this->assertFalse($fs->has(self::PID_FILE_NAME));
	}

	protected function tearDown ()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp');
		if ($fs->has(self::PID_FILE_NAME))
		{
			$fs->delete(self::PID_FILE_NAME);
		}
	}
}
