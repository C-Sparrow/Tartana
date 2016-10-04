<?php
namespace Tests\Functional\Tartana\Console\Command;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class DefaultCommandTest extends WebTestCase
{

	public function testExecute()
	{
		$this->loadFixtures([
				'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$app = new Application($client->getKernel());
		$app->setAutoExit(false);
		$output = new BufferedOutput();

		$exitCode = $app->run(new ArrayInput([
				'command' => 'default',
				'-vvv'
		]), $output);

		$outputString = $output->fetch();
		$this->assertEmpty($outputString, $outputString);
		$this->assertSame(0, $exitCode);
	}
}
