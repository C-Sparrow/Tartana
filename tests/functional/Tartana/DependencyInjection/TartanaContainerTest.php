<?php
namespace Tests\Functional\Synology\DependencyInjection;
use GuzzleHttp\ClientInterface;
use Tartana\Entity\Download;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Monolog\Logger;
use Tartana\Domain\LogRepository;
use Tartana\Handler\ProcessCompletedDownloadsHandler;
use Tartana\Handler\ChangeDownloadStateHandler;
use Tartana\Host\HostFactory;

class TartanaContainerTest extends KernelTestCase
{

	public function testHasClientInterface ()
	{
		$container = static::$kernel->getContainer();
		$client = $container->get('ClientInterface');

		$this->assertInstanceOf(ClientInterface::class, $client);
	}

	public function testHasLogger ()
	{
		$container = static::$kernel->getContainer();
		$logger = $container->get('Logger');

		$this->assertInstanceOf(Logger::class, $logger);
	}

	public function testHasLogRepository ()
	{
		$container = static::$kernel->getContainer();
		$repository = $container->get('LogRepository');

		$this->assertInstanceOf(LogRepository::class, $repository);
	}

	public function testHasHostFactory ()
	{
		$container = static::$kernel->getContainer();
		$factory = $container->get('HostFactory');

		$this->assertInstanceOf(HostFactory::class, $factory);
		$this->assertNotNull($factory->getCommandBus());
		$this->assertNotNull($factory->getLogger());
	}

	public function testHasProcessCompletedDownloadsHandler ()
	{
		$container = static::$kernel->getContainer();
		$handler = $container->get('ProcessCompletedDownloadsHandler');

		$this->assertInstanceOf(ProcessCompletedDownloadsHandler::class, $handler);
	}

	public function testHasChangeDownloadStateHandler ()
	{
		$container = static::$kernel->getContainer();
		$handler = $container->get('ChangeDownloadStateHandler');

		$this->assertInstanceOf(ChangeDownloadStateHandler::class, $handler);
	}

	public function testRunCommandNoHandler ()
	{
		$container = static::$kernel->getContainer();
		$commandBus = $container->get('CommandBus');
		$commandBus->handle(new Download());
	}

	public function testUsingDistFile ()
	{
		$container = static::$kernel->getContainer();
		$parameter = $container->getParameter('tartana.config');

		$this->assertEmpty($parameter['links']['hostFilter']);
	}

	protected function setUp ()
	{
		self::bootKernel();
	}
}