<?php
namespace Tests\Unit\Tartana\Event\Listener;

use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Entity\Download;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Event\Listener\ExtractListener;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class ExtractListenerTest extends TartanaBaseTestCase
{

	public function testPasswordFilePath()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/pw.txt', 'password', new Config());
		$fs->write('test/test/test.rar', 'unit test', new Config());
		$fs->createDir('test1', new Config());

		$runner = $this->getMockRunner(
			[
						$this->callback(
							function (Command $command) use ($fs) {
									return $command->getCommand() == 'php' && strpos($command, __DIR__ . '/test/pw.txt');
							}
						)
			]
		);

		$download = new Download();
		$download->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockRepository(), [
				$download
		]);

		$listener = new ExtractListener(
			$runner,
			new Registry(
				[
								'extract' => [
										'destination' => $fs->applyPathPrefix('test1'),
										'passwordFile' => $fs->applyPathPrefix('test/pw.txt')
								]
					]
			)
		);

		$listener->onProcessCompletedDownloads($event);
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
