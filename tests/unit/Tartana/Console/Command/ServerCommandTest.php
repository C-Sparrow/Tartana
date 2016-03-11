<?php
namespace Tests\Unit\Tartana\Console\Command;
use League\Flysystem\Adapter\Local;
use Tartana\Component\Command\Runner;
use Tartana\Console\Command\ServerCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tartana\Component\Command\Command;
use League\Flysystem\Config;

class ServerCommandTest extends \PHPUnit_Framework_TestCase
{

	const PID_FILE_NAME = 'server_test.pid';

	public function testStartServer ()
	{
		$runner = $this->getMockRunner();
		$runner->expects($this->exactly(2))
			->method('execute')
			->withConsecutive([
				$this->callback(function  (Command $command) {
					return strpos($command, '-S 0.0.0.0:') !== false;
				})
		], [
				$this->callback(function  (Command $command) {
					return strpos($command, "app.php' 'default'") !== false;
				})
		])
			->will($this->onConsecutiveCalls(20, 'Good job'));

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
		$runner = $this->getMockRunner();
		$runner->expects($this->exactly(2))
			->method('execute')
			->withConsecutive(
				[
						$this->callback(function  (Command $command) {
							return strpos($command, '-S 0.0.0.0:9999') !== false;
						})
				], [
						$this->callback(function  (Command $command) {
							return strpos($command, "app.php' 'default'") !== false;
						})
				])
			->will($this->onConsecutiveCalls(20, 'Good job'));

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
		$runner = $this->getMockRunner();
		$runner->expects($this->exactly(2))
			->method('execute')
			->withConsecutive([
				$this->callback(function  (Command $command) {
					return strpos($command, '-S 0.0.0.0:') !== false;
				})
		], [
				$this->callback(function  (Command $command) {
					return strpos($command, "app.php' 'default'") !== false;
				})
		])
			->will($this->onConsecutiveCalls(20, 'Good job'));

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
		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

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
				'action' => 'start',
				'--env' => 'test'
		]);

		$this->assertTrue($fs->has(self::PID_FILE_NAME));
		$this->assertEquals(getmypid(), $fs->read(self::PID_FILE_NAME)['contents']);
	}

	public function testStartServerHasDiedPid ()
	{
		$runner = $this->getMockRunner();
		$runner->expects($this->exactly(2))
			->method('execute')
			->withConsecutive([
				$this->callback(function  (Command $command) {
					return strpos($command, '-S 0.0.0.0:') !== false;
				})
		], [
				$this->callback(function  (Command $command) {
					return strpos($command, "app.php' 'default'") !== false;
				})
		])
			->will($this->onConsecutiveCalls(20, 'Good job'));

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
		$runner = $this->getMockRunner();
		$runner->expects($this->once())
			->method('execute')
			->with($this->callback(function  (Command $command) {
			return strpos($command, "kill '-9' '" . getmypid()) !== false;
		}))
			->will($this->returnValue('Killed'));

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
		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

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
				'action' => 'stop',
				'--env' => 'test'
		]);

		$this->assertFalse($fs->has(self::PID_FILE_NAME));
	}

	public function testStopServerNoPidFile ()
	{
		$runner = $this->getMockRunner();
		$runner->expects($this->never())
			->method('execute');

		$command = new ServerCommand($runner);
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

	private function getMockRunner ()
	{
		$runner = $this->getMockBuilder(Runner::class)->getMock();
		return $runner;
	}
}
