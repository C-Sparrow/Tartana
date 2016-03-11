<?php
namespace Tests\Unit\Tartana\Console\Command;
use Tartana\Console\Command\DownloadControlCommand;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\DeleteDownloads;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use SimpleBus\Message\Bus\MessageBus;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Translation\TranslatorInterface;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class DownloadControlCommandTest extends TartanaBaseTestCase
{

	public function testStatus ()
	{
		$downloads = [];
		$download = new Download();
		$download->setLink('http://foo.bar/lkhasdu');
		$download->setDestination(__DIR__ . '/test');
		$download->setProgress(20);
		$download->setSize(1505);
		$downloads[] = $download;

		$download = new Download();
		$download->setLink('http://foo.bar/3d23424');
		$download->setDestination(__DIR__ . '/test1');
		$download->setProgress(30);
		$download->setSize(53453);
		$downloads[] = $download;

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->expects($this->once())
			->method('findDownloads')
			->willReturn($downloads);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->atLeast(5))
			->method('trans')
			->will($this->onConsecutiveCalls('1111', '2222', '3333'));

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $this->getMockCommandBus(), $translator));
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'status'
		));
		$content = $commandTester->getDisplay();

		$this->assertEquals(2, substr_count($content, '1111'));
		$this->assertEquals(2, substr_count($content, '2222'));
		$this->assertEquals(2, substr_count($content, '3333'));
		$this->assertContains($download->getProgress(), $content);
		$this->assertContains($download->getLink(), $content);
		$this->assertContains($download->getDestination(), $content);
	}

	public function testStatusNoAction ()
	{
		$downloads = [];
		$download = new Download();
		$download->setLink('http://foo.bar/lkhasdu');
		$download->setDestination(__DIR__);
		$download->setProgress(20);
		$downloads[] = $download;

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->expects($this->once())
			->method('findDownloads')
			->willReturn($downloads);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $this->getMockCommandBus(), $translator));
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName()
		));
		$content = $commandTester->getDisplay();

		$this->assertContains($download->getLink(), $content);
	}

	public function testStatusByDestination ()
	{
		$downloads = [];
		$download = new Download();
		$download->setLink('http://foo.bar/lkhasdu');
		$download->setDestination(__DIR__ . '/test');
		$download->setProgress(20);
		$download->setSize(1505);
		$downloads[] = $download;

		$download = new Download();
		$download->setLink('http://foo.bar/3d23424');
		$download->setDestination(__DIR__ . '/test');
		$download->setProgress(30);
		$download->setSize(53453);
		$downloads[] = $download;

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->expects($this->once())
			->method('findDownloadsByDestination')
			->willReturn($downloads)
			->with($this->equalTo(__DIR__));

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $this->getMockCommandBus(), $translator));
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'status',
				'--destination' => __DIR__
		));
		$content = $commandTester->getDisplay();

		$this->assertContains($downloads[0]->getLink(), $content);
		$this->assertContains($downloads[1]->getLink(), $content);
	}

	public function testStatusCompact ()
	{
		$downloads = [];
		$download = new Download();
		$download->setLink('http://foo.bar/lkhasdu');
		$download->setDestination(__DIR__ . '/test');
		$download->setFileName('test.zip');
		$download->setProgress(20);
		$download->setSize(1505);
		$download->setState(Download::STATE_DOWNLOADING_STARTED);
		$downloads[] = $download;

		$download = new Download();
		$download->setLink('http://foo.bar/3d23424');
		$download->setDestination(__DIR__ . '/test1');
		$download->setProgress(30);
		$download->setSize(53453);
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);
		$download = new Download();
		$download->setLink('http://foo.bar/jkgasd72j');
		$download->setDestination(__DIR__ . '/test1');
		$download->setProgress(30);
		$download->setSize(53453);
		$download->setState(Download::STATE_DOWNLOADING_ERROR);
		$downloads[] = $download;

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->expects($this->once())
			->method('findDownloads')
			->willReturn($downloads);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->atLeast(5))
			->method('trans')
			->will($this->onConsecutiveCalls('Destination', 'Total', 'Size', 'State', 'Name', 'Not started'));

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $this->getMockCommandBus(), $translator));
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'status',
				'--compact' => 1
		));
		$content = $commandTester->getDisplay();

		$this->assertEquals(2, substr_count($content, 'Destination'));
		$this->assertEquals(2, substr_count($content, 'Total'));
		$this->assertEquals(2, substr_count($content, 'Size'));
		$this->assertContains('test.zip', $content);
		$this->assertContains(__DIR__ . '/test', $content);
		$this->assertContains(__DIR__ . '/test1', $content);
		$this->assertContains('1', $content);
	}

	public function testClearAll ()
	{
		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->willReturn([]);

		$messageBusMock = $this->getMockBuilder(MessageBus::class)->getMock();
		$messageBusMock->expects($this->once())
			->method('handle');

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $messageBusMock, $translator));
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'clearall'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('Success run!', $content);
	}

	public function testClearAllByDestination ()
	{
		$download = new Download();
		$download->setLink('http://foo.bar/3d23424');
		$download->setDestination(__DIR__ . '/test1');
		$download->setProgress(30);
		$download->setSize(53453);

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloadsByDestination')->willReturn([
				$download
		]);

		$messageBusMock = $this->getMockCommandBus(
				[
						$this->callback(
								function  (DeleteDownloads $command) {
									return count($command->getDownloads()) == 1 && $command->getDownloads()[0]->getDestination() == __DIR__ . '/test1';
								})
				]);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $messageBusMock, $translator));
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'clearall',
				'-d' => __DIR__ . '/test1'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('Success run!', $content);
	}

	public function testClearCompleted ()
	{
		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->willReturn([]);

		$messageBusMock = $this->getMockBuilder(MessageBus::class)->getMock();
		$messageBusMock->expects($this->once())
			->method('handle')
			->with($this->callback(function  (DeleteDownloads $command) use ( $repositoryMock) {
			return true;
		}));

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $messageBusMock, $translator));
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'clearcompleted'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('Success run!', $content);
	}

	public function testResumeFailed ()
	{
		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();

		$messageBusMock = $this->getMockBuilder(MessageBus::class)->getMock();
		$messageBusMock->expects($this->once())
			->method('handle')
			->with($this->callback(function  (ChangeDownloadState $command) {
			return true;
		}));

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $messageBusMock, $translator));
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'resumefailed'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('Success run!', $content);
	}

	public function testResumeAll ()
	{
		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();

		$messageBusMock = $this->getMockBuilder(MessageBus::class)->getMock();
		$messageBusMock->expects($this->once())
			->method('handle')
			->with($this->callback(function  (ChangeDownloadState $command) {
			return true;
		}));

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $messageBusMock, $translator));
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'resumeall'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('Success run!', $content);
	}

	public function testReprocess ()
	{
		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();

		$messageBusMock = $this->getMockBuilder(MessageBus::class)->getMock();
		$messageBusMock->expects($this->once())
			->method('handle')
			->with($this->callback(function  (ChangeDownloadState $command) {
			return true;
		}));

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $messageBusMock, $translator));
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'reprocess'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('Success run!', $content);
	}
}
