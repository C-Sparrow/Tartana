<?php
namespace Tests\Unit\Tartana\Component;

use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;

class RunnerTest extends \PHPUnit_Framework_TestCase
{

	public function testRunCommand()
	{
		$command = new Command('echo');
		$command->addArgument('Unit Test');

		$runner = new Runner();
		$output = $runner->execute($command);

		$this->assertEquals('Unit Test', $output);
	}

	public function testRunCommandAsync()
	{
		$command = new Command('(sleep 2; echo Unit Test)');
		$command->setAsync(true);

		$runner = new Runner();
		$output = $runner->execute($command);

		$this->assertNotEquals('Unit Test', $output);
		$this->assertTrue(is_numeric($output), 'Output is: ' . var_export($output, true));
	}

	public function testRunCommandSetEnvironment()
	{
		$command = new Command('echo');
		$command->addArgument('/cli/app.php');

		$runner = new Runner('prod');
		$output = $runner->execute($command);

		$this->assertContains('--env prod', $output);
	}

	public function testRunCommandChangeEnvironment()
	{
		$command = new Command('echo');
		$command->addArgument('/cli/app.php');
		$command->addArgument('--env test');

		$runner = new Runner('prod');
		$output = $runner->execute($command);

		$this->assertContains('--env prod', $output);
	}

	public function testRunCommandWithCallback()
	{
		$command = new Command('echo');
		$command->addArgument('Unit Test');

		$runner = new Runner();

		$buffer = [];
		$output = $runner->execute($command, function ($line) use (&$buffer) {
			$buffer[] = $line;
		});

		$this->assertEquals('Unit Test', $output);
		$this->assertCount(1, $buffer);
		$this->assertEquals('Unit Test', trim($buffer[0]));
	}
}
