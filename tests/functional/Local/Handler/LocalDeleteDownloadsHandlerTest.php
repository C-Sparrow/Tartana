<?php
namespace Tests\Functional\Local\Handler;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Tartana\Domain\Command\DeleteDownloads;
use Tartana\Entity\Download;

class LocalDeleteDownloadsHandlerTest extends WebTestCase
{

	public function testDeleteDownloads ()
	{
		$this->loadFixtures([
				'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$commandBus = $client->getContainer()->get('CommandBus');

		$repository = $client->getContainer()->get('DownloadRepository');
		$downloads = $repository->findDownloads();

		$this->assertNotEmpty($downloads);
		foreach ($downloads as $download)
		{
			$this->assertGreaterThan(0, $download->getId());
			$commandBus->handle(new DeleteDownloads([
					$download
			]));
		}

		$this->assertEmpty($repository->findDownloads());
	}
}