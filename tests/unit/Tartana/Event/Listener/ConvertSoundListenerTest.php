<?php
namespace Tests\Unit\Tartana\Event\Listener;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\ProcessLinks;
use Tartana\Entity\Download;
use Tartana\Event\CommandEvent;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Event\Listener\ConvertSoundListener;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class ConvertSoundListenerTest extends TartanaBaseTestCase
{

	public function testConvert ()
	{
		$dst = new Local(__DIR__ . '/test1');

		$runner = $this->getMockRunner(
				[
						$this->callback(
								function  (Command $command) {
									return $command->getCommand() == 'ffmpeg' && strpos($command, 'test.mp4') !== false &&
											 strpos($command, 'test.mp3') !== false;
								})
				]);

		$download = new Download();
		$download->setFileName('test.mp4');
		$download->setDestination(__DIR__ . '/test');
		$download1 = new Download();
		$download1->setFileName('test.txt');
		$download1->setDestination(__DIR__ . '/test');
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download,
				$download1
		]);
		$listener = new ConvertSoundListener($runner, new Registry([
				'sound' => [
						'destination' => $dst->getPathPrefix()
				]
		]));
		$listener->onConvertDownloads($event);

		$this->assertTrue($dst->has('test'));
	}

	public function testConvertHostFilter ()
	{
		$dst = new Local(__DIR__ . '/test1');

		$runner = $this->getMockRunner(
				[
						$this->callback(
								function  (Command $command) {
									return $command->getCommand() == 'ffmpeg' && strpos($command, 'test.mp4') !== false &&
											 strpos($command, 'test.mp3') !== false;
								})
				]);

		$download = new Download();
		$download->setFileName('test.mp4');
		$download->setDestination(__DIR__ . '/test');
		$download->setLink('http://foo.bar/test');
		$download1 = new Download();
		$download1->setFileName('test1.mp4');
		$download1->setDestination(__DIR__ . '/test');
		$download1->setLink('http://bar.foo/test');
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download,
				$download1
		]);
		$listener = new ConvertSoundListener($runner,
				new Registry([
						'sound' => [
								'destination' => $dst->getPathPrefix(),
								'hostFilter' => 'foo.bar'
						]
				]));
		$listener->onConvertDownloads($event);

		$this->assertTrue($dst->has('test'));
	}

	public function testConvertNoDownloads ()
	{
		$dst = new Local(__DIR__ . '/test1');

		$event = new DownloadsCompletedEvent($this->getMockRepository(), []);
		$listener = new ConvertSoundListener($this->getMockRunner([]),
				new Registry([
						'sound' => [
								'destination' => $dst->getPathPrefix()
						]
				]));
		$listener->onConvertDownloads($event);

		$this->assertEmpty($dst->listContents());
	}

	public function testConvertInvalidDestination ()
	{
		$download = new Download();
		$download->setFileName('test.mp4');
		$download->setDestination(__DIR__ . '/test');
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);
		$listener = new ConvertSoundListener($this->getMockRunner([]),
				new Registry([
						'sound' => [
								'destination' => __DIR__ . 'invalid'
						]
				]));
		$listener->onConvertDownloads($event);

		$this->assertFileNotExists(__DIR__ . 'invalid');
	}

	public function testCleanUpDirectory ()
	{
		$dst = new Local(__DIR__ . '/test1');
		$dst->createDir('test', new Config());

		$listener = new ConvertSoundListener($this->getMockRunner(),
				new Registry([
						'sound' => [
								'destination' => $dst->getPathPrefix()
						]
				]));

		$download = new Download();
		$download->setDestination(__DIR__ . '/test');

		$listener->onChangeDownloadStateAfter(
				new CommandEvent(
						new ChangeDownloadState([
								$download
						], Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_COMPLETED)));

		$this->assertFalse($dst->has('test'));
	}

	public function testCleanUpDirectoryHasNoDestination ()
	{
		$dst = new Local(__DIR__ . '/test1');

		$listener = new ConvertSoundListener($this->getMockRunner(),
				new Registry([
						'sound' => [
								'destination' => $dst->getPathPrefix()
						]
				]));

		$download = new Download();
		$download->setDestination(__DIR__ . '/test');

		$listener->onChangeDownloadStateAfter(
				new CommandEvent(
						new ChangeDownloadState([
								$download
						], Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_COMPLETED)));

		$this->assertFalse($dst->has('test'));
	}

	public function testCleanUpDirectoryWrongState ()
	{
		$dst = new Local(__DIR__ . '/test1');
		$dst->createDir('test', new Config());

		$listener = new ConvertSoundListener($this->getMockRunner(),
				new Registry([
						'sound' => [
								'destination' => $dst->getPathPrefix()
						]
				]));

		$download = new Download();
		$download->setDestination(__DIR__ . '/test');

		$listener->onChangeDownloadStateAfter(
				new CommandEvent(
						new ChangeDownloadState([
								$download
						], Download::STATE_DOWNLOADING_ERROR, Download::STATE_PROCESSING_COMPLETED)));

		$this->assertTrue($dst->has('test'));
	}

	public function testCleanUpDirectoryWrongDestination ()
	{
		$listener = new ConvertSoundListener($this->getMockRunner(),
				new Registry([
						'sound' => [
								'destination' => __DIR__ . '/invalid'
						]
				]));
		$listener->onChangeDownloadStateAfter(
				new CommandEvent(new ChangeDownloadState([], Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_COMPLETED)));
	}

	public function testCleanUpDirectoryWrongEvent ()
	{
		$listener = new ConvertSoundListener($this->getMockRunner(), new Registry([
				'sound' => [
						'destination' => __DIR__
				]
		]));
		$listener->onChangeDownloadStateAfter(new CommandEvent(new ProcessLinks([])));
	}

	protected function setUp ()
	{
		$fs = new Local(__DIR__ . '/');
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}
}
