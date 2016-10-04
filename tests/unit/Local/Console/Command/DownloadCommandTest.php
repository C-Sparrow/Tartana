<?php
namespace Tests\Unit\Local\Console\Command;

use GuzzleHttp\ClientInterface;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Local\Console\Command\DownloadCommand;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;
use Tartana\Host\Common\Http;
use Tartana\Host\HostInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;
use Tests\Unit\Local\LocalBaseTestCase;
use Tartana\Host\Common\Https;
use Tartana\Component\Command\Command;

class DownloadCommandTest extends LocalBaseTestCase
{

	public function testExecute()
	{
		$commandBus = $this->getMockCommandBus(
			[
				$this->callback(
					function (SaveDownloads $command) {
						return $command->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_STARTED;
					}
				)
			]
		);

		$host = $this->getMockBuilder(HostInterface::class)->getMock();
		$host->expects($this->once())
			->method('download')
			->with(
				$this->callback(function (array $downloads) {
					return $downloads[0]->getState() == Download::STATE_DOWNLOADING_STARTED;
				})
			);
		$command = new DownloadCommand($this->getMockRepository(), $this->getMockHostFactory($host), $this->getMockRunner());
		$command->addOption('env', 'e');
		$command->setCommandBus($commandBus);
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);
		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteNoDownloader()
	{
		$command = new DownloadCommand($this->getMockRepository(), $this->getMockHostFactory(null), $this->getMockRunner());
		$command->addOption('env', 'e');
		$command->setCommandBus($this->getMockCommandBus());
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);
		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteStartedDownloads()
	{
		// Not started download
		$notStarted = new Download();
		$notStarted->setLink('http://devnull.org/klad');
		$notStarted->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');

		// Started download
		$started = new Download();
		$started->setLink('http://devnull.org/kladsdfz');
		$started->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$started->setState(Download::STATE_DOWNLOADING_STARTED);

		$commandBus = $this->getMockCommandBus(
			[
				$this->callback(
					function (SaveDownloads $command) {
						return $command->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_STARTED;
					}
				)
			]
		);

		$command = new DownloadCommand($this->getMockRepository([], [
			$started
		], [
			$notStarted
		]), $this->getMockHostFactory($this->getMockBuilder(HostInterface::class)
			->getMock()), $this->getMockRunner());
		$command->addOption('env', 'e');
		$command->setCommandBus($commandBus);
		$application = new Application();
		$application->add($command);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteTooManyAlreadyStarted()
	{
		// Not started download
		$notStarted = new Download();
		$notStarted->setLink('http://devnull.org/klad');
		$notStarted->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');

		// Started download
		$started = new Download();
		$started->setLink('http://devnull.org/kladsdfz');
		$started->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$started->setState(Download::STATE_DOWNLOADING_STARTED);

		$repositoryMock = $this->getMockRepository([], [
			$started,
			$started,
			$started,
			$started,
			$started
		], [
			$notStarted
		]);

		$factory = $this->getMockHostFactory();
		$factory->expects($this->never())
			->method('createHostDownloader');
		$command = new DownloadCommand($repositoryMock, $factory, $this->getMockRunner());
		$command->addOption('env', 'e');
		$command->setCommandBus($this->getMockCommandBus());
		$application = new Application();
		$application->add($command);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteForceReset()
	{
		$downloadsNotStarted = [];
		$download = new Download();
		$download->setLink('http://devnull.org/klad');
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$download->setState(Download::STATE_DOWNLOADING_ERROR);
		$downloadsNotStarted[] = $download;

		$commandBus = $this->getMockCommandBus(
			[
				$this->callback(
					function (SaveDownloads $command) {
						return $command->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_NOT_STARTED;
					}
				)
			]
		);

		$command = new DownloadCommand($this->getMockRepository($downloadsNotStarted, [], []), $this->getMockHostFactory(), $this->getMockRunner());
		$command->addOption('env', 'e');
		$command->setCommandBus($commandBus);
		$application = new Application();
		$application->add($command);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test',
			'--force' => 1
		]);
	}

	public function testExecuteRestartZombies()
	{
		$zombies = [];
		$download = new Download();
		$download->setLink('http://devnull.org/klad');
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$download->setState(Download::STATE_DOWNLOADING_STARTED);
		$download->setPid(19236);
		$zombies[] = $download;

		$commandBus = $this->getMockCommandBus(
			[
				$this->callback(
					function (SaveDownloads $command) {
						return $command->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_NOT_STARTED;
					}
				)
			]
		);

		$command = new DownloadCommand($this->getMockRepository($zombies, [], []), $this->getMockHostFactory(), $this->getMockRunner());
		$command->addOption('env', 'e');
		$command->setCommandBus($commandBus);
		$application = new Application();
		$application->add($command);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteIgnoreReset()
	{
		$zombies = [];
		$download = new Download();
		$download->setLink('http://devnull.org/klad');
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$download->setState(Download::STATE_DOWNLOADING_ERROR);
		$download->setPid(19236);
		$zombies[] = $download;

		$command = new DownloadCommand($this->getMockRepository($zombies, [], []), $this->getMockHostFactory(), $this->getMockRunner());
		$command->addOption('env', 'e');
		$command->setCommandBus($this->getMockCommandBus());
		$application = new Application();
		$application->add($command);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteIgnoreResetRunning()
	{
		$download = new Download();
		$download->setLink('http://devnull.org/klad');
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$download->setState(Download::STATE_DOWNLOADING_STARTED);
		$download->setPid(getmypid());

		$command = new DownloadCommand($this->getMockRepository([
			$download
		], [], []), $this->getMockHostFactory(), $this->getMockRunner());
		$command->addOption('env', 'e');
		$command->setCommandBus($this->getMockCommandBus());
		$application = new Application();
		$application->add($command);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteWithSpeedLimit()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/app/config');

		$fs->write(
			'parameters.yml',
			Yaml::dump([
				'sleepTime' => 0,
				'parameters' => [
					'tartana.local.downloads.speedlimit' => 10
				]
			]),
			new Config()
		);

		$factory = $this->getMockHostFactory($this->getMockBuilder(HostInterface::class)
			->getMock());
		$factory->method('createHostDownloader')->with(
			$this->anything(),
			$this->callback(function (Registry $config) {
				return $config->get('speedlimit') == 2;
			})
		);

		$command = new DownloadCommand($this->getMockRepository(), $factory, $this->getMockRunner());
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteWithDayLimitNotReached()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/app/config');

		$fs->write(
			'parameters.yml',
			Yaml::dump([
				'sleepTime' => 0,
				'parameters' => [
					'tartana.local.downloads.daylimit' => 10
				]
			]),
			new Config()
		);

		$downloads = [];
		$download = new Download();
		$download->setSize(20000);
		$download->setFinishedAt(new \DateTime());
		$download->getFinishedAt()->modify('-1 day');
		$downloads[] = $download;
		$download = new Download();
		$download->setSize(5000);
		$download->setFinishedAt(new \DateTime());
		$downloads[] = $download;

		$command = new DownloadCommand(
			$this->getMockRepository([], $downloads, $downloads, $downloads),
			$this->getMockHostFactory(
				[
					$this->getMockBuilder(HostInterface::class)
						->getMock(),
					$this->getMockBuilder(HostInterface::class)
						->getMock()
				]
			),
			$this->getMockRunner()
		);
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteWithDayLimitReached()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/app/config');

		$fs->write(
			'parameters.yml',
			Yaml::dump([
				'sleepTime' => 0,
				'parameters' => [
					'tartana.local.downloads.daylimit' => 10
				]
			]),
			new Config()
		);

		$downloads = [];
		$download = new Download();
		$download->setSize(20000);
		$download->setFinishedAt(new \DateTime());
		$downloads[] = $download;

		$command = new DownloadCommand(
			$this->getMockRepository([], $downloads, $downloads, $downloads),
			$this->getMockHostFactory(),
			$this->getMockRunner()
		);
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteLoadHostersFile()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/app/config');

		$fs->write('hosters.yml', Yaml::dump([
			'message' => 'unit-test'
		]), new Config());

		$factory = $this->getMockHostFactory($this->getMockBuilder(HostInterface::class)
			->getMock());
		$factory->method('createHostDownloader')->with(
			$this->anything(),
			$this->callback(function (Registry $config) {
				return $config->get('message') == 'unit-test';
			})
		);

		$command = new DownloadCommand($this->getMockRepository(), $factory, $this->getMockRunner());
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteSharedClient()
	{
		$client = $this->getMockBuilder(ClientInterface::class)->getMock();

		$host1 = $this->getMockBuilder(Http::class)
			->disableOriginalConstructor()
			->getMock();
		$host1->expects($this->once())
			->method('getClient')
			->willReturn($client);

		$host2 = $this->getMockBuilder(Http::class)
			->disableOriginalConstructor()
			->getMock();
		$host2->expects($this->once())
			->method('setClient')
			->with($this->callback(function (ClientInterface $c) use ($client) {
				return $client == $c;
			}));
		$command = new DownloadCommand($this->getMockRepository([], [], [
			new Download(),
			new Download()
		]), $this->getMockHostFactory([
			$host1,
			$host2
		]), $this->getMockRunner());
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	public function testExecuteSharedClientNotEqual()
	{
		$client = $this->getMockBuilder(ClientInterface::class)->getMock();

		$host1 = $this->getMockBuilder(Https::class)
			->disableOriginalConstructor()
			->getMock();
		$host1->expects($this->once())
			->method('getClient')
			->willReturn($client);

		$host2 = $this->getMockBuilder(Http::class)
			->disableOriginalConstructor()
			->getMock();
		$host2->expects($this->never())
			->method('setClient');

		$command = new DownloadCommand($this->getMockRepository([], [], [
			new Download(),
			new Download()
		]), $this->getMockHostFactory([
			$host1,
			$host2
		]), $this->getMockRunner());
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute([
			'--env' => 'test'
		]);
	}

	protected function setUp()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/app/config');
		if ($fs->has('parameters.yml')) {
			$fs->rename('parameters.yml', 'parameters.yml.backup.for.test');
		}
		if ($fs->has('hosters.yml')) {
			$fs->rename('hosters.yml', 'hosters.yml.backup.for.test');
		}

		$fs->write('parameters.yml', Yaml::dump([
			'sleepTime' => 0
		]), new Config());

		$fs = new Local(TARTANA_PATH_ROOT . '/var/tmp/');
		if ($fs->has('download_test.pid')) {
			$fs->delete('download_test.pid');
		}
	}

	protected function tearDown()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/app/config');
		if ($fs->has('parameters.yml.backup.for.test')) {
			$fs->rename('parameters.yml.backup.for.test', 'parameters.yml');
		}
		if ($fs->has('hosters.yml.backup.for.test')) {
			$fs->rename('hosters.yml.backup.for.test', 'hosters.yml');
		}
	}

	protected function getMockRunner($callbacks = [], $returnData = [])
	{
		if (empty($callbacks)) {
			$callbacks = [
				$this->callback(function (Command $command) {
					return strpos((string)$command, 'default') !== false;
				})
			];
		}
		return parent::getMockRunner($callbacks, $returnData);
	}

	protected function getMockRepository($zombieDownloads = [], $downloadsStarted = [], $downloadsNotStarted = null, $new = [])
	{
		if ($downloadsNotStarted === null) {
			$downloadsNotStarted = [];

			// Not started download
			$download = new Download();
			$download->setLink('http://devnull.org/klad');
			$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
			$downloadsNotStarted[] = $download;
		}

		return parent::getMockRepository([
			$zombieDownloads,
			$downloadsNotStarted,
			$downloadsStarted,
			$new
		]);
	}
}
