<?php
namespace Tests\Functional\Tartana\Event\Listener;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Event\Listener\SoundConverterListener;
use League\Flysystem\Config;

class SoundConverterListenerTest extends KernelTestCase
{

	public function testConvertFile ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
		$fs->deleteDir('test1');
		$fs->createDir('test1', new Config());
		$fs->copy('../../../../unit/Tartana/Console/Command/files/test.mp4', 'test/test.mp4');

		$configuration = new Registry([
				'async' => false,
				'sound' => [
						'destination' => $fs->applyPathPrefix('test1')
				]
		]);

		$d = new Download();
		$d->setDestination($fs->applyPathPrefix('test'));
		$event = new DownloadsCompletedEvent($this->getMockBuilder(DownloadRepository::class)->getMock(), [
				$d
		]);
		$subscriber = new SoundConverterListener(self::$kernel->getContainer()->get('CommandRunner'), $configuration);
		$subscriber->onProcessCompletedDownloads($event);

		$this->assertTrue($fs->has('test1/test/test.mp3'));
		$this->assertTrue($fs->has('test/test.mp4'));
	}

	protected function setUp ()
	{
		self::bootKernel();
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}
}
