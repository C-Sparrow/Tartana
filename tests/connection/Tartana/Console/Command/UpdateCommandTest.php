<?php
namespace Tests\Connection\Tartana\Console\Command;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Component\Command\Command;
use Tartana\Console\Command\UpdateCommand;
use Tartana\Host\HostFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class UpdateCommandTest extends TartanaBaseTestCase
{

	public function testUpdateGithub()
	{
		$application = new Application();
		$application->add(
			new UpdateCommand(
				$this->getMockRunner(
					[
										$this->callback(function (Command $command) {
											return true;
										}),
										$this->callback(
											function (Command $command) {
													return strpos($command, 'unzip') !== false;
											}
										),
										$this->callback(
											function (Command $command) {
													return strpos($command, 'doctrine:migrations:migrate') !== false;
											}
										)
							]
				),
				'github',
				new HostFactory()
			)
		);

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName(),
				'--force' => 1
		]);
	}

	protected function setUp()
	{
		$fs = new Local(TARTANA_PATH_ROOT);

		if ($fs->has('var/tmp/tartana.zip')) {
			$fs->delete('var/tmp/tartana.zip');
		}
	}

	protected function tearDown()
	{
		$fs = new Local(TARTANA_PATH_ROOT);
		if ($fs->has('var/tmp/tartana.zip')) {
			$fs->delete('var/tmp/tartana.zip');
		}
		$fs->write('var/cache/.gitkeep', '', new Config());
	}
}
