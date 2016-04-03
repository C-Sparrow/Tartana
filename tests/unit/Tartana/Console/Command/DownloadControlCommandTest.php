<?php
namespace Tests\Unit\Tartana\Console\Command;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Translation\TranslatorInterface;
use Tartana\Console\Command\DownloadControlCommand;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\DeleteDownloads;
use Tartana\Entity\Download;
use Tests\Unit\Tartana\TartanaBaseTestCase;
use Tartana\Domain\DownloadRepository;

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

		$repositoryMock = $this->getMockRepository([
				$downloads
		]);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->atLeast(5))
			->method('trans')
			->will($this->onConsecutiveCalls('1111', '2222', '3333'));

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $translator));
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
		$download = new Download();
		$download->setLink('http://foo.bar/lkhasdu');
		$download->setDestination(__DIR__);
		$download->setProgress(20);

		$repositoryMock = $this->getMockRepository([
				[
						$download
				]
		]);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $translator));
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

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();

		$application = new Application();
		$application->add(new DownloadControlCommand($this->getMockRepository([
				$downloads
		]), $translator));
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

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->atLeast(5))
			->method('trans')
			->will($this->onConsecutiveCalls('Destination', 'Total', 'Size', 'State', 'Name', 'Not started'));

		$application = new Application();
		$application->add(new DownloadControlCommand($this->getMockRepository([
				$downloads
		]), $translator));
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

	public function testDetails ()
	{
		$downloads = [];
		$download = new Download();
		$download->setId(2);
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

		$repositoryMock = $this->getMockRepository([
				$downloads
		]);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->atLeast(5))
			->method('trans');

		$application = new Application();
		$application->add(new DownloadControlCommand($repositoryMock, $translator));
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'details',
				'--id' => 2
		));
		$content = $commandTester->getDisplay();

		$this->assertContains('20', $content);
		$this->assertContains('http://foo.bar/lkhasdu', $content);
		$this->assertContains(__DIR__ . '/test', $content);

		$this->assertNotContains($download->getProgress(), $content);
		$this->assertNotContains($download->getLink(), $content);
		$this->assertNotContains($download->getDestination(), $content);
	}

	public function testClearAll ()
	{
		$messageBusMock = $this->getMockCommandBus([
				$this->callback(function  (DeleteDownloads $command) {
					return true;
				})
		]);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$application = new Application();
		$cmd = new DownloadControlCommand($this->getMockRepository(), $translator);
		$cmd->setCommandBus($messageBusMock);
		$application->add($cmd);
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

		$repositoryMock = $this->getMockRepository([
				[
						$download
				]
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
		$cmd = new DownloadControlCommand($repositoryMock, $translator);
		$cmd->setCommandBus($messageBusMock);
		$application->add($cmd);
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
		$messageBusMock = $this->getMockCommandBus([
				$this->callback(function  (DeleteDownloads $command) {
					return true;
				})
		]);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$download = new Download();
		$download->setState(Download::STATE_PROCESSING_COMPLETED);
		$application = new Application();
		$cmd = new DownloadControlCommand($this->getMockRepository([
				[
						$download
				]
		]), $translator);
		$cmd->setCommandBus($messageBusMock);
		$application->add($cmd);
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'clearcompleted'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('Success run!', $content);
	}

	public function testClearFailed ()
	{
		$messageBusMock = $this->getMockCommandBus([
				$this->callback(function  (DeleteDownloads $command) {
					return true;
				})
		]);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$download = new Download();
		$download->setState(Download::STATE_DOWNLOADING_ERROR);
		$application = new Application();
		$cmd = new DownloadControlCommand($this->getMockRepository([
				[
						$download
				]
		]), $translator);
		$cmd->setCommandBus($messageBusMock);
		$application->add($cmd);
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'clearfailed'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('Success run!', $content);
	}

	public function testResumeFailed ()
	{
		$messageBusMock = $this->getMockCommandBus([
				$this->callback(function  (ChangeDownloadState $command) {
					return true;
				})
		]);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$downloads = [];
		$d = new Download();
		$d->setState(Download::STATE_DOWNLOADING_ERROR);
		$downloads[] = $d;
		$downloads = [];
		$d = new Download();
		$d->setState(Download::STATE_DOWNLOADING_COMPLETED);
		$downloads[] = $d;

		$application = new Application();
		$cmd = new DownloadControlCommand($this->getMockRepository([
				$downloads
		]), $translator);
		$cmd->setCommandBus($messageBusMock);
		$application->add($cmd);
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'resumefailed'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('Success run!', $content);
	}

	public function testResumeFailedByDestination ()
	{
		$download = new Download();
		$download->setLink('http://foo.bar/3d23424');
		$download->setDestination(__DIR__ . '/test1');
		$download->setProgress(30);
		$download->setSize(53453);

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->expects($this->never())
			->method('findDownloads');
		$repositoryMock->expects($this->once())
			->method('findDownloadsByDestination')
			->willReturn([
				$download
		]);

		$messageBusMock = $this->getMockCommandBus(
				[
						$this->callback(function  (ChangeDownloadState $command) {
							return true;
						})
				]);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$application = new Application();
		$cmd = new DownloadControlCommand($repositoryMock, $translator);
		$cmd->setCommandBus($messageBusMock);
		$application->add($cmd);
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'resumefailed',
				'-d' => __DIR__ . '/test1'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('Success run!', $content);
	}

	public function testResumeAll ()
	{
		$download = new Download();
		$download->setFileName('hello.txt');

		$messageBusMock = $this->getMockCommandBus(
				[
						$this->callback(
								function  (ChangeDownloadState $command) {
									return $command->getFromState() == [
											Download::STATE_DOWNLOADING_STARTED,
											Download::STATE_DOWNLOADING_COMPLETED,
											Download::STATE_DOWNLOADING_ERROR,
											Download::STATE_PROCESSING_NOT_STARTED,
											Download::STATE_PROCESSING_STARTED,
											Download::STATE_PROCESSING_COMPLETED,
											Download::STATE_PROCESSING_ERROR
									] && $command->getToState() == Download::STATE_DOWNLOADING_NOT_STARTED;
								})
				]);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$application = new Application();
		$cmd = new DownloadControlCommand($this->getMockRepository([
				[
						new Download()
				]
		]),

		$translator);
		$cmd->setCommandBus($messageBusMock);
		$application->add($cmd);
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
		$messageBusMock = $this->getMockCommandBus([
				$this->callback(function  (ChangeDownloadState $command) {
					return true;
				})
		]);

		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('Success run!'));

		$application = new Application();
		$cmd = new DownloadControlCommand($this->getMockRepository([
				[
						new Download()
				]
		]), $translator);
		$cmd->setCommandBus($messageBusMock);
		$application->add($cmd);
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'reprocess'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('Success run!', $content);
	}

	public function testInvalidAction ()
	{
		$translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
		$translator->expects($this->once())
			->method('trans')
			->will($this->returnValue('No action found!'));

		$application = new Application();
		$cmd = new DownloadControlCommand($this->getMockRepository(), $translator);
		$cmd->setCommandBus($this->getMockCommandBus([]));
		$application->add($cmd);
		$command = $application->find('download:control');

		$commandTester = new CommandTester($command);
		$commandTester->execute(array(
				'command' => $command->getName(),
				'action' => 'invalid'
		));
		$content = trim($commandTester->getDisplay());

		$this->assertEquals('No action found!', $content);
	}
}
