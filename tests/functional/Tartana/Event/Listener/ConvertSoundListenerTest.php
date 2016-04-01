<?php
namespace Tests\Functional\Tartana\Event\Listener;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Event\Listener\ConvertSoundListener;

class ConvertSoundListenerTest extends KernelTestCase
{

	public function testConvertFile ()
	{
		$src = new Local(__DIR__ . '/test');
		$src->copy('../files/test.mp4', 'test.mp4');
		$dst = new Local(__DIR__ . '/test1');

		$d = new Download();
		$d->setFileName('test.mp4');
		$d->setDestination($src->getPathPrefix());

		$event = new DownloadsCompletedEvent($this->getMockBuilder(DownloadRepository::class)->getMock(), [
				$d
		]);
		$subscriber = new ConvertSoundListener(self::$kernel->getContainer()->get('CommandRunner'),
				new Registry([
						'sound' => [
								'destination' => $dst->getPathPrefix()
						]
				]));
		$subscriber->onConvertDownloads($event);

		$this->assertTrue($dst->has('test/test.mp3'));
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
