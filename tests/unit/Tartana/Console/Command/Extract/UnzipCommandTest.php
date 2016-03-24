<?php
namespace Tests\Unit\Tartana\Console\Command\Extract;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tartana\Component\Command\Command;
use Tartana\Console\Command\Extract\UnzipCommand;
use Tests\Unit\Tartana\Console\Command\Extract\ExtractBaseTestCase;

class UnzipCommandTest extends ExtractBaseTestCase
{

	protected $archivesPath = 'zips';

	public function test7z ()
	{
		if (! $this->copyArchives())
		{
			return;
		}

		$application = new Application();
		$command = new UnzipCommand($this->getMockDispatcher(), new Registry(),
				$this->getMockRunner([
						$this->callback(function  (Command $command) {
							return true;
						})
				], [
						'found'
				]));
		$application->add($command);

		$commandTester = new CommandTester($command);

		$fs = new Local(__DIR__);
		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
				]);

		$this->assertTrue($fs->has('test1/Downloads/symfony.png'));
		$this->assertFalse($fs->has('test'));
	}

	protected function createCommand (EventDispatcherInterface $dispatcher, Registry $config = null)
	{
		if ($config === null)
		{
			$config = new Registry();
		}
		return new UnzipCommand($dispatcher, $config,
				$this->getMockRunner([
						$this->callback(function  (Command $command) {
							return true;
						})
				]));
	}
}
