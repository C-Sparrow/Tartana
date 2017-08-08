<?php
namespace Tests\Functional\Tartana\Console\Command;

use League\Flysystem\Adapter\Local;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Console\Command\UpdateCommand;
use Tartana\Host\HostFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use League\Flysystem\Config;

class UpdateCommandTest extends \PHPUnit_Framework_TestCase
{

	public function testUpdate()
	{
		$fs      = new Local(__DIR__ . '/test');
		$version = $this->createZipForUpdate($fs->applyPathPrefix('tartana.zip'));

		$application = new Application();
		$application->add(new UpdateCommand(new Runner('test'), 'file://localhost' . $fs->applyPathPrefix('tartana.zip'), new HostFactory()));

		$command       = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'command' => $command->getName()
		]);

		$fs = new Local(TARTANA_PATH_ROOT . '/app/config/internal');

		$this->assertEquals($version, trim($fs->read('version.txt')['contents']));
	}

	public function testUpdateTwice()
	{
		$fs = new Local(__DIR__ . '/test');
		$this->createZipForUpdate($fs->applyPathPrefix('tartana.zip'));

		$application = new Application();
		$application->add(new UpdateCommand(new Runner('test'), 'file://localhost' . $fs->applyPathPrefix('tartana.zip'), new HostFactory()));

		$command       = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'command' => $command->getName()
		]);

		$version = $this->createZipForUpdate($fs->applyPathPrefix('tartana.zip'));
		$commandTester->execute([
			'command' => $command->getName()
		]);

		$fs = new Local(TARTANA_PATH_ROOT . '/app/config/internal');

		$this->assertEquals($version, trim($fs->read('version.txt')['contents']));
	}

	protected function setUp()
	{
		$fs = new Local(TARTANA_PATH_ROOT);

		if ($fs->has('var/tmp/tartana.zip')) {
			$fs->delete('var/tmp/tartana.zip');
		}

		$fs->copy('app/config/internal/version.txt', 'app/config/internal/version.txt.backup.for.test');

		$fs = new Local(__DIR__ . '/test');
	}

	protected function tearDown()
	{
		$fs = new Local(TARTANA_PATH_ROOT);
		if ($fs->has('var/tmp/tartana.zip')) {
			$fs->delete('var/tmp/tartana.zip');
		}
		$fs->write('var/cache/.gitkeep', '', new Config());

		$fs->rename('app/config/internal/version.txt.backup.for.test', 'app/config/internal/version.txt');

		$fs = new Local(__DIR__);
		if ($fs->has('test')) {
			$fs->deleteDir('test');
		}
	}

	private function createZipForUpdate($path, $version = null)
	{
		if ($version === null) {
			$fs      = new Local(TARTANA_PATH_ROOT . '/app/config/internal');
			$version = $fs->read('version.txt')['contents'];
			preg_match_all("/(\d+)\.(\d+)\.(\d+)/", $version, $matches);
			$version = $matches[1][0] . '.' . $matches[2][0] . '.' . ($matches[3][0] + 1);
		}
		$zip = new \ZipArchive();
		$zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
		$zip->addFromString('app/config/internal/version.txt', $version);
		$zip->close();

		return $version;
	}
}
