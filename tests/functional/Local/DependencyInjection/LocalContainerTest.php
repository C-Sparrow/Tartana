<?php
namespace Tests\Functional\Local\DependencyInjection;
use Local\Domain\LocalDownloadRepository;
use Local\Handler\LocalDeleteDownloadsHandler;
use Local\Handler\LocalProcessLinksHandler;
use Local\Handler\LocalStartDownloadsHandler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class LocalContainerTest extends KernelTestCase
{

	public function testHasDownloadRepository ()
	{
		$container = static::$kernel->getContainer();
		$repository = $container->get('DownloadRepository');

		$this->assertInstanceOf(LocalDownloadRepository::class, $repository);
	}

	public function testHasLocalProcessLinksHandler ()
	{
		$container = static::$kernel->getContainer();
		$handler = $container->get('LocalProcessLinksHandler');

		$this->assertInstanceOf(LocalProcessLinksHandler::class, $handler);
	}

	public function testHasLocalStartDownloadsHandler ()
	{
		$container = static::$kernel->getContainer();
		$handler = $container->get('LocalStartDownloadsHandler');

		$this->assertInstanceOf(LocalStartDownloadsHandler::class, $handler);
	}

	public function testHasLocalDeleteDownloadsHandler ()
	{
		$container = static::$kernel->getContainer();
		$handler = $container->get('LocalDeleteDownloadsHandler');

		$this->assertInstanceOf(LocalDeleteDownloadsHandler::class, $handler);
	}

	protected function setUp ()
	{
		self::bootKernel();
	}
}