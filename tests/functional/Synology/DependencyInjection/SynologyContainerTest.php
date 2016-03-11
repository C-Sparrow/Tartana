<?php
namespace Tests\Functional\Synology\DependencyInjection;
use Tartana\Domain\DownloadRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Synology\Domain\SynologyDownloadRepository;
use Synology\Handler\SynologyProcessCompletedDownloadsHandler;
use Synology\Handler\SynologyProcessLinksHandler;

class SynologyContainerTest extends KernelTestCase
{

	public function testHasDownloadRepository ()
	{
		$container = static::$kernel->getContainer();
		$repository = $container->get('DownloadRepository');

		$this->assertInstanceOf(SynologyDownloadRepository::class, $repository);
	}

	public function testHasSynologyProcessLinksHandler ()
	{
		$container = static::$kernel->getContainer();
		$handler = $container->get('SynologyProcessLinksHandler');

		$this->assertInstanceOf(SynologyProcessLinksHandler::class, $handler);
	}

	public function testHasSynologyProcessCompletedDownloadsHandler ()
	{
		$container = static::$kernel->getContainer();
		$handler = $container->get('SynologyProcessCompletedDownloadsHandler');

		$this->assertInstanceOf(SynologyProcessCompletedDownloadsHandler::class, $handler);
	}

	protected function setUp ()
	{
		self::bootKernel([
				'environment' => 'test_synology'
		]);
	}
}