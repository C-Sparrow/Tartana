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

class DownloadCommandTest extends LocalBaseTestCase
{

	public function testExecute ()
	{
		$commandBus = $this->getMockCommandBus(
				[
						$this->callback(
								function  (SaveDownloads $command) {
									return $command->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_STARTED;
								})
				]);

		$host = $this->getMockBuilder(HostInterface::class)->getMock();
		$host->expects($this->once())
			->method('download')
			->with(
				$this->callback(function  (array $downloads) {
					return $downloads[0]->getState() == Download::STATE_DOWNLOADING_STARTED;
				}));
		$cmd = new DownloadCommand($this->getMockRepository(), $this->getMockHostFactory($host));
		$cmd->setCommandBus($commandBus);
		$application = new Application();
		$application->add($cmd);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([]);
	}

	public function testExecuteNoDownloader ()
	{
		$cmd = new DownloadCommand($this->getMockRepository(), $this->getMockHostFactory(null));
		$cmd->setCommandBus($this->getMockCommandBus());
		$application = new Application();
		$application->add($cmd);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([]);
	}

	public function testExecuteStartedDownloads ()
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
								function  (SaveDownloads $command) {
									return $command->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_STARTED;
								})
				]);

		$cmd = new DownloadCommand($this->getMockRepository([], [
				$started
		], [
				$notStarted
		]), $this->getMockHostFactory($this->getMockBuilder(HostInterface::class)
			->getMock()));
		$cmd->setCommandBus($commandBus);
		$application = new Application();
		$application->add($cmd);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([]);
	}

	public function testExecuteTooManyAlreadyStarted ()
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
		$cmd = new DownloadCommand($repositoryMock, $factory);
		$cmd->setCommandBus($this->getMockCommandBus());
		$application = new Application();
		$application->add($cmd);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([]);
	}

	public function testExecuteForceReset ()
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
								function  (SaveDownloads $command) {
									return $command->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_NOT_STARTED;
								})
				]);

		$cmd = new DownloadCommand($this->getMockRepository($downloadsNotStarted, [], []), $this->getMockHostFactory());
		$cmd->setCommandBus($commandBus);
		$application = new Application();
		$application->add($cmd);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName(),
				'--force' => 1
		]);
	}

	public function testExecuteRestartZombies ()
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
								function  (SaveDownloads $command) {
									return $command->getDownloads()[0]->getState() == Download::STATE_DOWNLOADING_NOT_STARTED;
								})
				]);

		$cmd = new DownloadCommand($this->getMockRepository($zombies, [], []), $this->getMockHostFactory());
		$cmd->setCommandBus($commandBus);
		$application = new Application();
		$application->add($cmd);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testExecuteIgnoreReset ()
	{
		$zombies = [];
		$download = new Download();
		$download->setLink('http://devnull.org/klad');
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$download->setState(Download::STATE_DOWNLOADING_ERROR);
		$download->setPid(19236);
		$zombies[] = $download;

		$cmd = new DownloadCommand($this->getMockRepository($zombies, [], []), $this->getMockHostFactory());
		$cmd->setCommandBus($this->getMockCommandBus());
		$application = new Application();
		$application->add($cmd);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testExecuteIgnoreResetRunning ()
	{
		$download = new Download();
		$download->setLink('http://devnull.org/klad');
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$download->setState(Download::STATE_DOWNLOADING_STARTED);
		$download->setPid(getmypid());

		$cmd = new DownloadCommand($this->getMockRepository([
				$download
		], [], []), $this->getMockHostFactory());
		$cmd->setCommandBus($this->getMockCommandBus());
		$application = new Application();
		$application->add($cmd);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testExecuteHosterConfiguration ()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/app/config');
		if ($fs->has('hosters.yml'))
		{
			$fs->rename('hosters.yml', 'hosters.yml.backup.for.test');
		}

		$fs->write('hosters.yml', Yaml::dump([
				'setmessage' => 'test'
		]), new Config());

		$factory = $this->getMockHostFactory($this->getMockBuilder(HostInterface::class)
			->getMock());
		$factory->method('createHostDownloader')->with($this->anything(),
				$this->callback(function  (Registry $config) {
					return $config->get('setmessage') == 'test';
				}));
		$application = new Application();
		$application->add(new DownloadCommand($this->getMockRepository(), $factory));

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([
				'command' => $command->getName()
		]);
	}

	public function testExecuteSharedClient ()
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
			->with($this->callback(function  (ClientInterface $c) use ( $client) {
			return $client == $c;
		}));
		$cmd = new DownloadCommand($this->getMockRepository([], [], [
				new Download(),
				new Download()
		]), $this->getMockHostFactory([
				$host1,
				$host2
		]));
		$application = new Application();
		$application->add($cmd);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([]);
	}

	public function testExecuteSharedClientNotEqual ()
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

		$cmd = new DownloadCommand($this->getMockRepository([], [], [
				new Download(),
				new Download()
		]), $this->getMockHostFactory([
				$host1,
				$host2
		]));
		$application = new Application();
		$application->add($cmd);

		$command = $application->find('download');
		$commandTester = new CommandTester($command);

		$commandTester->execute([]);
	}

	protected function tearDown ()
	{
		$fs = new Local(TARTANA_PATH_ROOT . '/app/config');
		if ($fs->has('hosters.yml.backup.for.test'))
		{
			$fs->rename('hosters.yml.backup.for.test', 'hosters.yml');
		}
	}

	protected function getMockRepository ($zombieDownloads = [], $downloadsStarted = [], $downloadsNotStarted = null)
	{
		if ($downloadsNotStarted === null)
		{
			$downloadsNotStarted = [];

			// Not started download
			$download = new Download();
			$download->setLink('http://devnull.org/klad');
			$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
			$downloadsNotStarted[] = $download;
		}

		return parent::getMockRepository([
				$zombieDownloads,
				$downloadsStarted,
				$downloadsNotStarted
		]);
	}
}
