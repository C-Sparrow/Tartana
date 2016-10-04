<?php
namespace Tests\Unit\Tartana\Component;

use Tartana\Component\Command\Command;

class CommandTest extends \PHPUnit_Framework_TestCase
{

	public function testCommandName()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals('unit', (string) $command);
		$this->assertEquals('unit', $command->getCommand());
		$this->assertEmpty($command->getArguments());
	}

	public function testCommandGetAddArgument()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->addArgument('test'));

		$this->assertEquals("unit 'test'", (string) $command);
		$this->assertEquals('unit', $command->getCommand());
		$this->assertCount(1, $command->getArguments());
		$this->assertEquals("'test'", $command->getArguments()[0]);
	}

	public function testCommandEmptyArgument()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->addArgument(''));

		$this->assertEquals("unit", (string) $command);
	}

	public function testCommandArgumentNotEscaped()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->addArgument('test', false));

		$this->assertEquals("unit test", (string) $command);
	}

	public function testCommandReplaceArgument()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->addArgument('test'));
		$this->assertEquals($command, $command->replaceArgument('test', 'new'));

		$this->assertEquals("unit 'new'", (string) $command);
		$this->assertEquals('unit', $command->getCommand());
		$this->assertCount(1, $command->getArguments());
		$this->assertEquals("'new'", $command->getArguments()[0]);
	}

	public function testCommandReplaceArgumentNotEscaped()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->addArgument('test', false));
		$this->assertEquals($command, $command->replaceArgument('test', 'new', false));

		$this->assertEquals("unit new", (string) $command);
		$this->assertEquals('unit', $command->getCommand());
		$this->assertCount(1, $command->getArguments());
		$this->assertEquals("new", $command->getArguments()[0]);
	}

	public function testCommandReplaceArgumentMixedEscaped()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->addArgument('test', false));
		$this->assertEquals($command, $command->replaceArgument('test', 'new', true));

		$this->assertEquals("unit 'new'", (string) $command);
		$this->assertEquals('unit', $command->getCommand());
		$this->assertCount(1, $command->getArguments());
		$this->assertEquals("'new'", $command->getArguments()[0]);
	}

	public function testCommandReplaceArgumentMixedNotEscaped()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->addArgument('test', true));
		$this->assertEquals($command, $command->replaceArgument('test', 'new', false));

		$this->assertEquals("unit new", (string) $command);
		$this->assertEquals('unit', $command->getCommand());
		$this->assertCount(1, $command->getArguments());
		$this->assertEquals("new", $command->getArguments()[0]);
	}

	public function testCommandAsync()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->setAsync(true));

		$this->assertEquals("unit > /dev/null & echo $!", (string) $command);
	}

	public function testCommandAsyncArguments()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->addArgument('test'));
		$this->assertEquals($command, $command->setAsync(true));

		$this->assertEquals("unit 'test' > /dev/null & echo $!", (string) $command);
	}

	public function testCommandCaptureError()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(true));

		$this->assertEquals("unit 2>&1", (string) $command);
	}

	public function testCommandCaptureErrorArguments()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(true));
		$this->assertEquals($command, $command->addArgument('test'));

		$this->assertEquals("unit 'test' 2>&1", (string) $command);
	}

	public function testCommandOutputFile()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->setOutputFile('test.out'));

		$this->assertEquals("unit > test.out", (string) $command);
	}

	public function testCommandOutputFileArguments()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->addArgument('test'));
		$this->assertEquals($command, $command->setOutputFile('test.out'));

		$this->assertEquals("unit 'test' > test.out", (string) $command);
	}

	public function testCommandAppendOutputFile()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->setOutputFile('test.out'));
		$this->assertEquals($command, $command->setAppend(true));

		$this->assertEquals("unit >> test.out", (string) $command);
	}

	public function testCommandAppendOutputFileArguments()
	{
		$command = new Command('unit');
		$this->assertEquals($command, $command->setCaptureErrorInOutput(false));
		$this->assertEquals($command, $command->addArgument('test'));
		$this->assertEquals($command, $command->setOutputFile('test.out'));
		$this->assertEquals($command, $command->setAppend(true));

		$this->assertEquals("unit 'test' >> test.out", (string) $command);
	}
}
