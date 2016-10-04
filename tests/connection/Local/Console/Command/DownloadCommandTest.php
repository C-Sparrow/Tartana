<?php
namespace Tests\Unit\Local\Console\Command;

use GuzzleHttp\ClientInterface;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Local\Console\Command\DownloadCommand;
use Tartana\Component\Command\Runner;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;
use Tartana\Host\Common\Http;
use Tartana\Host\HostFactory;
use Tartana\Host\HostInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;
use Tests\Unit\Local\LocalBaseTestCase;
use Tartana\Host\Common\Https;
use Tartana\Component\Command\Command;

class DownloadCommandTest extends LocalBaseTestCase
{

	public function testExecute()
	{
		$dest = new Local(__DIR__ . '/test');

		$downloads = [];
		$download = new Download();
		$download->setLink('http://www.share-online.biz/dl/EG5BWT3OO27');
		$download->setDestination($dest->getPathPrefix());
		$download->setFileName('test1.png');
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test2.png');
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test3.png');
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test4.png');
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test5.png');
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test6.png');
		$download = clone $download;
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test7.png');
		$download = clone $download;
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test8.png');
		$download = clone $download;
		$downloads[] = $download;
		$download = clone $download;
		$download->setFileName('test9.png');
		$downloads[] = $download;

		$command = new DownloadCommand(
			$this->getMockRepository([[], array_slice($downloads, 0, 5), [], [], array_slice($downloads, 5)]),
			new HostFactory(),
			$this->getMockBuilder(Runner::class)->getMock(),
			new Registry(['sleepTime' => 1])
		);
		$command->addOption('env', 'e');
		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);
		$commandTester->execute([
			'--env' => 'test'
		]);

		$this->assertEquals(count($downloads), count($dest->listContents()));
	}

	protected function setUp()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
	}
}
