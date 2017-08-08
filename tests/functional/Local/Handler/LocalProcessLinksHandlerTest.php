<?php
namespace Tests\Functional\Local\Handler;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Tartana\Domain\Command\ProcessLinks;

class LocalProcessLinksHandlerTest extends WebTestCase
{

	public function testCreateDownload()
	{
		$this->loadFixtures([
			'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$repository                = $client->getContainer()->get('DownloadRepository');
		$downloadsBeforeProcessing = $repository->findDownloads();

		$handler = $client->getContainer()->get('LocalProcessLinksHandler');
		$handler->setHostFactory(null);
		$handler->handle(new ProcessLinks([
			$downloadsBeforeProcessing[0]->getLink()
		]));

		$downloads = $repository->findDownloads();

		$this->assertNotEmpty($downloads);
		$this->assertEquals($downloadsBeforeProcessing, $downloads);
	}
}
