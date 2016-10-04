<?php
namespace Tests\Unit\Tartana\Event\Listener;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Entity\Download;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Event\Listener\ConvertSoundListener;
use Tests\Unit\Tartana\TartanaBaseTestCase;
use Joomla\Registry\Registry;

class ConvertSoundListenerTest extends TartanaBaseTestCase
{

	public function testHasFilesToProcess()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/test.mp4', 'unit test', new Config());
		$fs->createDir('test1', new Config());

		$runner = $this->getMockRunner(
			[
						$this->callback(
							function (Command $command) use ($fs) {
									return $command->getCommand() == 'php' && strpos($command, 'unit') !== false &&
											 strpos($command, $fs->applyPathPrefix('test')) !== false &&
											 strpos($command, $fs->applyPathPrefix('test1')) !== false;
							}
						)
			]
		);

		$download = new Download();
		$download->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);

		$fs = new Local(__DIR__);

		$listener = new ConvertSoundListener(
			$runner,
			new Registry([
						'sound' => [
								'destination' => $fs->applyPathPrefix('test1')
						]
			])
		);
		$listener->onProcessCompletedDownloads($event);

		$this->assertEquals(Download::STATE_PROCESSING_STARTED, $download->getState());
		$this->assertEmpty($download->getMessage());
	}

	protected function setUp()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}
}
