<?php
namespace Tests\Functional\Tartana\Event\Listener;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Event\DownloadsCompletedEvent;
use Tartana\Event\Listener\ExtractListener;

class ExtractListenerTest extends KernelTestCase
{

	public function testExtractRarFile ()
	{
		$this->copyRars();

		$src = new Local(__DIR__ . '/test');
		$dst = new Local(__DIR__ . '/test1');
		$configuration = new Registry([
				'async' => false,
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]);

		$downloads = [];
		foreach ($src->listContents() as $file)
		{
			$d = new Download();
			$d->setDestination($src->getPathPrefix());
			$downloads[] = $d;
		}
		$event = new DownloadsCompletedEvent($this->getMockBuilder(DownloadRepository::class)->getMock(), $downloads);
		$listener = new ExtractListener(self::$kernel->getContainer()->get('CommandRunner'), $configuration);
		$listener->onProcessCompletedDownloads($event);

		$this->assertTrue($dst->has('test/Downloads/symfony.png'));
		$this->assertEmpty($src->listContents());
	}

	public function testExtractRarFileAsync ()
	{
		$this->copyRars();

		$src = new Local(__DIR__ . '/test');
		$dst = new Local(__DIR__ . '/test1');
		$configuration = new Registry([
				'async' => false,
				'extract' => [
						'destination' => $dst->getPathPrefix()
				]
		]);

		$downloads = [];
		foreach ($src->listContents() as $file)
		{
			$d = new Download();
			$d->setDestination($src->getPathPrefix());
			$downloads[] = $d;
		}
		$event = new DownloadsCompletedEvent($this->getMockBuilder(DownloadRepository::class)->getMock(), $downloads);
		$listener = new ExtractListener(self::$kernel->getContainer()->get('CommandRunner'), $configuration);
		$listener->onProcessCompletedDownloads($event);

		// As we run it async we wait at least 10 seconds
		for ($i = 0; $i < 3 && ! $dst->has('test/Downloads/symfony.png'); $i ++)
		{
			sleep(1);
		}

		$this->assertTrue($dst->has('test/Downloads/symfony.png'));
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

	private function copyRars ($folder = 'simple')
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
		$fs->deleteDir('test1');
		foreach ($fs->listContents('../../Console/Command/Extract/rars/' . $folder, false) as $rar)
		{
			if ($rar['type'] != 'file')
			{
				continue;
			}
			$fs->copy($rar['path'], str_replace('../../Console/Command/Extract/rars/' . $folder, 'test/', $rar['path']));
		}
	}
}
