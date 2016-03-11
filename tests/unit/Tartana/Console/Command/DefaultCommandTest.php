<?php
namespace Tests\Unit\Tartana\Console\Command;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Tartana\Console\Command\DefaultCommand;
use Tartana\Domain\Command\ParseLinks;
use Tartana\Domain\Command\ProcessCompletedDownloads;
use Tartana\Domain\Command\StartDownloads;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use SimpleBus\Message\Bus\MessageBus;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DefaultCommandTest extends \PHPUnit_Framework_TestCase
{

	public function testExecuteWithDlcFile ()
	{
		$fs = new Local(__DIR__);
		$fs->copy('../../Component/Dlc/simple.dlc', 'testdlcs/simple.dlc');

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->willReturn([]);

		$messageBusMock = $this->getMockBuilder(MessageBus::class)->getMock();
		$messageBusMock->expects($this->once())
			->method('handle')
			->with($this->callback(function  (ParseLinks $command) {
			return $command->getPath() == 'simple.dlc';
		}));

		$application = new Application();
		$application->add(
				new DefaultCommand($repositoryMock, $messageBusMock,
						new Registry([
								'links' => [
										'folder' => $fs->applyPathPrefix('testdlcs')
								]
						])));
		$command = $application->find('default');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName()
		));
	}

	public function testExecuteWithCompletedDownloads ()
	{
		$downloads = [];

		$download = new Download();
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);
		$download->setDestination(__DIR__ . '/test');
		$downloads[] = $download;

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->willReturn($downloads);

		$messageBusMock = $this->getMockBuilder(MessageBus::class)->getMock();
		$messageBusMock->expects($this->once())
			->method('handle')
			->with(
				$this->callback(
						function  (ProcessCompletedDownloads $command) {
							return $command->getDownloads()[0]->getDestination() == __DIR__ . '/test';
						}));

		$application = new Application();
		$application->add(new DefaultCommand($repositoryMock, $messageBusMock, new Registry()));
		$command = $application->find('default');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName()
		));
	}

	public function testExecuteWithNotCompletedDownloads ()
	{
		$downloads = [];

		$download = new Download();
		$download->setState(Download::STATE_DOWNLOADING_STARTED);
		$download->setDestination(__DIR__ . '/test');
		$downloads[] = $download;
		$download = new Download();
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);
		$download->setDestination(__DIR__ . '/test');
		$downloads[] = $download;

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->willReturn($downloads);

		$messageBusMock = $this->getMockBuilder(MessageBus::class)->getMock();
		$messageBusMock->expects($this->never())
			->method('handle');

		$application = new Application();
		$application->add(new DefaultCommand($repositoryMock, $messageBusMock, new Registry()));

		$command = $application->find('default');
		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName()
		));
	}

	public function testExecuteWithNotCompletedAndCompletedDownloads ()
	{
		$downloads = [];

		$download = new Download();
		$download->setState(Download::STATE_DOWNLOADING_STARTED);
		$download->setDestination(__DIR__ . '/test');
		$downloads[] = $download;
		$download = new Download();
		$download->setState(Download::STATE_DOWNLOADING_STARTED);
		$download->setDestination(__DIR__ . '/test');
		$downloads[] = $download;
		$download = new Download();
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);
		$download->setDestination(__DIR__ . '/test1');
		$downloads[] = $download;

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->willReturn($downloads);

		$messageBusMock = $this->getMockBuilder(MessageBus::class)->getMock();
		$messageBusMock->expects($this->once())
			->method('handle')
			->with(
				$this->callback(
						function  (ProcessCompletedDownloads $command) {
							return $command->getDownloads()[0]->getDestination() == __DIR__ . '/test1';
						}));

		$application = new Application();
		$application->add(new DefaultCommand($repositoryMock, $messageBusMock, new Registry()));

		$command = $application->find('default');
		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName()
		));
	}

	public function testExecuteWithNotStartedDownloads ()
	{
		$downloads = [];

		$download = new Download();
		$download->setState(Download::STATE_DOWNLOADING_NOT_STARTED);
		$download->setDestination(__DIR__ . '/test');
		$downloads[] = $download;

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->willReturn($downloads);

		$messageBusMock = $this->getMockBuilder(MessageBus::class)->getMock();
		$messageBusMock->expects($this->once())
			->method('handle')
			->with($this->callback(function  (StartDownloads $command) {
			return $command->getRepository() !== null;
		}));

		$application = new Application();
		$application->add(new DefaultCommand($repositoryMock, $messageBusMock, new Registry()));

		$command = $application->find('default');
		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName()
		));
	}

	protected function setUp ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
		$fs->deleteDir('test1');
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');

		if ($fs->has('testdlcs'))
		{
			$fs->deleteDir('testdlcs');
		}
	}
}
