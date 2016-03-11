<?php
namespace Tests\Functional\Local\Console\Command;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Tartana\Host\HostFactory;
use Tartana\Host\HostInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DownloadCommandTest extends WebTestCase
{

	public function testExecute ()
	{
		$this->loadFixtures([
				'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$host = $this->getMockBuilder(HostInterface::class)->getMock();
		$host->method('download')->willReturn([]);
		$mock = $this->getMockBuilder(HostFactory::class)->getMock();
		$mock->method('createHostDownloader')->willReturn($host);
		$client->getContainer()->set('HostFactory', $mock);

		$app = new Application($client->getKernel());
		$app->setAutoExit(false);
		$output = new BufferedOutput();

		$exitCode = $app->run(new ArrayInput([
				'command' => 'download',
				'-vvv'
		]), $output);

		$outputString = $output->fetch();
		$this->assertEmpty($outputString, $outputString);
		$this->assertSame(0, $exitCode);
	}
}
