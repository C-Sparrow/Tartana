<?php
namespace Tests\Functional\Tartana\Component;
use Tartana\Component\Command\Runner;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Component\Command\Command;

class RunnerTest extends \PHPUnit_Framework_TestCase
{

	public function testRunCommandPipeToFile ()
	{
		$fs = new Local(__DIR__);
		if ($fs->has('test.out'))
		{
			$fs->delete('test.out');
		}

		$command = new Command('echo');
		$command->addArgument('Unit Test');
		$command->setOutputFile($fs->applyPathPrefix('test.out'));

		$runner = new Runner();
		$output = $runner->execute($command);

		$this->assertEmpty($output);
		$this->assertTrue($fs->has('test.out'));
		$this->assertContains('Unit Test', $fs->read('test.out')['contents']);
		$fs->delete('test.out');
	}

	public function testRunCommandPipeToFileAppend ()
	{
		$fs = new Local(__DIR__);
		$fs->write('test.out', 'Hello ', new Config());

		$command = new Command('echo');
		$command->addArgument('Unit Test');
		$command->setOutputFile($fs->applyPathPrefix('test.out'));
		$command->setAppend(true);

		$runner = new Runner();
		$output = $runner->execute($command);

		$this->assertEmpty($output);
		$this->assertTrue($fs->has('test.out'));
		$this->assertContains('Hello Unit Test', $fs->read('test.out')['contents']);
		$fs->delete('test.out');
	}
}