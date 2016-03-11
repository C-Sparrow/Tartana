<?php
namespace Tests\Unit\Tartana\Console\Command;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Console\Command\UpdateCommand;
use Tartana\Host\HostInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class UpdateCommandTest extends TartanaBaseTestCase
{

	public function testUpdateGithub ()
	{
		$runner = $this->getMockRunner(
				[
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'stop') !== false;
								})
						],
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'unzip') !== false;
								})
						],
						[
								$this->callback(
										function  (Command $command) {
											return strpos($command, 'doctrine:migrations:migrate') !== false;
										})
						]
				]);

		$host1 = $this->getMockBuilder(HostInterface::class)->getMock();
		$host1->expects($this->once())
			->method('download')
			->willReturnCallback(
				function  (array $downloads) {
					$asset = new \stdClass();
					$asset->browser_download_url = 'http://tartana/tartana.zip';
					$obj = new \stdClass();
					$obj->assets = [
							$asset
					];
					$fs = new Local($downloads[0]->getDestination());
					$fs->write($downloads[0]->getFileName(), json_encode([
							$obj
					]), new Config());
				})
			->with($this->callback(function  (array $downloads) {
			return $downloads[0]->getFileName() == 'github-update-data.json';
		}));
		$host1->expects($this->once())
			->method('setCommandBus')
			->with($this->equalTo(null));

		$application = new Application();
		$application->add(new UpdateCommand($runner, 'github', $this->getMockHostFactory([
				$host1,
				$this->getMockHost()
		])));

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testUpdateGithubInvalidResponseUrl ()
	{
		$runner = $this->getMockRunner(
				[
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'stop') !== false;
								})
						]
				]);

		$host = $this->getMockBuilder(HostInterface::class)->getMock();
		$host->expects($this->once())
			->method('download')
			->willReturnCallback(
				function  (array $downloads) {
					$obj = new \stdClass();
					$obj->assets = [];
					$fs = new Local($downloads[0]->getDestination());
					$fs->write($downloads[0]->getFileName(), json_encode([
							$obj
					]), new Config());
				})
			->with($this->callback(function  (array $downloads) {
			return $downloads[0]->getFileName() == 'github-update-data.json';
		}));

		$application = new Application();
		$application->add(new UpdateCommand($runner, 'github', $this->getMockHostFactory($host)));

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testUpdateNormalUrl ()
	{
		$runner = $this->getMockRunner(
				[
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'stop') !== false;
								})
						],
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'unzip') !== false;
								})
						],
						[
								$this->callback(
										function  (Command $command) {
											return strpos($command, 'doctrine:migrations:migrate') !== false;
										})
						]
				]);

		$application = new Application();
		$application->add(new UpdateCommand($runner, 'test', $this->getMockHostFactory($this->getMockHost())));

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testUpdateEmptyCache ()
	{
		$application = new Application();
		$application->add(
				new UpdateCommand($this->getMockBuilder(Runner::class)
					->getMock(), 'test', $this->getMockHostFactory($this->getMockHost())));

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$fs = new Local(TARTANA_PATH_ROOT . '/var/cache');
		$fs->write('test.txt', 'hello', new Config());
		$fs->createDir('test', new Config());

		$commandTester->execute([
				'command' => $command->getName()
		]);

		$this->assertEmpty($fs->listContents(''));
	}

	public function testUpdateHasZipAlready ()
	{
		$runner = $this->getMockRunner(
				[
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'stop') !== false;
								})
						],
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'unzip') !== false;
								})
						],
						[
								$this->callback(
										function  (Command $command) {
											return strpos($command, 'doctrine:migrations:migrate') !== false;
										})
						]
				]);

		$fs = new Local(__DIR__ . '/test');
		$this->createZipForUpdate(TARTANA_PATH_ROOT . '/var/tmp/tartana.zip');

		$application = new Application();
		$application->add(new UpdateCommand($runner, 'test', $this->getMockHostFactory($this->getMockHost())));

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testUpdateInvalidZipFile ()
	{
		$runner = $this->getMockRunner(
				[
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'stop') !== false;
								})
						]
				]);

		$host = $this->getMockBuilder(HostInterface::class)->getMock();
		$host->expects($this->once())
			->method('download')
			->willReturnCallback(
				function  (array $downloads) {
					$fs = new Local($downloads[0]->getDestination());
					$fs->write($downloads[0]->getFileName(), 'invalid content', new Config());
				});

		$application = new Application();
		$application->add(new UpdateCommand($runner, 'test', $this->getMockHostFactory($host)));

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testUpdateNoDownloader ()
	{
		$runner = $this->getMockRunner(
				[
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'stop') !== false;
								})
						]
				]);

		$application = new Application();
		$application->add(new UpdateCommand($runner, 'test', $this->getMockHostFactory(null)));

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testUpdateOlderVersion ()
	{
		$runner = $this->getMockRunner(
				[
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'stop') !== false;
								})
						]
				]);

		$host = $this->getMockBuilder(HostInterface::class)->getMock();
		$host->expects($this->once())
			->method('download')
			->willReturnCallback(
				function  (array $downloads) {
					$this->createZipForUpdate($downloads[0]->getDestination() . '/' . $downloads[0]->getFileName(), '0.0.1');
				});

		$application = new Application();
		$application->add(new UpdateCommand($runner, 'test', $this->getMockHostFactory($host)));

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testUpdateOlderVersionForce ()
	{
		$runner = $this->getMockRunner(
				[
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'stop') !== false;
								})
						],
						[
								$this->callback(function  (Command $command) {
									return strpos($command, 'unzip') !== false;
								})
						],
						[
								$this->callback(
										function  (Command $command) {
											return strpos($command, 'doctrine:migrations:migrate') !== false;
										})
						]
				]);

		$host = $this->getMockBuilder(HostInterface::class)->getMock();
		$host->expects($this->once())
			->method('download')
			->willReturnCallback(
				function  (array $downloads) {
					$this->createZipForUpdate($downloads[0]->getDestination() . '/' . $downloads[0]->getFileName(), '0.0.1');
				});

		$application = new Application();
		$application->add(new UpdateCommand($runner, 'test', $this->getMockHostFactory($host)));

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName(),
				'--force' => 1
		]);
	}

	public function testUpdateInvalidZipFetch ()
	{
		$runner = $this->getMockRunner(
				[
						$this->callback(function  (Command $command) {
							return strpos($command, 'stop') !== false;
						})
				]);

		$host = $this->getMockBuilder(HostInterface::class)->getMock();
		$host->expects($this->once())
			->method('download')
			->willThrowException(new \Exception());

		$application = new Application();
		$application->add(new UpdateCommand($runner, 'test', $this->getMockHostFactory([
				$host
		])));

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testUpdateEmptyUrl ()
	{
		$runner = $this->getMockRunner([]);

		$application = new Application();
		$application->add(new UpdateCommand($runner, null, $this->getMockHostFactory([])));

		$command = $application->find('update');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	private function createZipForUpdate ($path, $version = null)
	{
		if ($version === null)
		{
			$fs = new Local(TARTANA_PATH_ROOT . '/app/config/internal');
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

	private function getMockHost ()
	{
		$host = $this->getMockBuilder(HostInterface::class)->getMock();
		$host->expects($this->once())
			->method('download')
			->willReturnCallback(
				function  (array $downloads) {
					$this->createZipForUpdate($downloads[0]->getDestination() . '/' . $downloads[0]->getFileName());
				})
			->with($this->callback(function  (array $downloads) {
			return ! empty($downloads[0]->getLink());
		}));

		$host->expects($this->once())
			->method('setCommandBus')
			->with($this->equalTo(null));
		return $host;
	}

	protected function setUp ()
	{
		$fs = new Local(TARTANA_PATH_ROOT);

		if ($fs->has('var/tmp/tartana.zip'))
		{
			$fs->delete('var/tmp/tartana.zip');
		}
		if ($fs->has('var/tmp/github-update-data.json'))
		{
			$fs->delete('var/tmp/github-update-data.json');
		}

		$fs->copy('app/config/internal/version.txt', 'app/config/internal/version.txt.backup.for.test');

		$fs = new Local(__DIR__ . '/test');
	}

	protected function tearDown ()
	{
		$fs = new Local(TARTANA_PATH_ROOT);
		if ($fs->has('var/tmp/tartana.zip'))
		{
			$fs->delete('var/tmp/tartana.zip');
		}
		if ($fs->has('var/tmp/github-update-data.json'))
		{
			$fs->delete('var/tmp/github-update-data.json');
		}
		$fs->write('var/cache/.gitkeep', '', new Config());

		$fs->rename('app/config/internal/version.txt.backup.for.test', 'app/config/internal/version.txt');

		$fs = new Local(__DIR__);
		if ($fs->has('test'))
		{
			$fs->deleteDir('test');
		}
	}
}
