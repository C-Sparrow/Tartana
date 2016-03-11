<?php
namespace Tests\Functional\Local\Domain;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;

class LocalDownloadRepositoryTest extends WebTestCase
{

	public function testFindDownloads ()
	{
		$this->loadFixtures([
				'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$repository = $client->getContainer()->get('DownloadRepository');
		$downloads = $repository->findDownloads();

		$this->assertNotEmpty($downloads);
		foreach ($downloads as $download)
		{
			$this->assertGreaterThan(0, $download->getId());
			$this->assertNotEmpty($download->getLink());
		}
	}

	public function testFindDownloadsWithState ()
	{
		$this->loadFixtures([
				'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$repository = $client->getContainer()->get('DownloadRepository');
		$downloads = $repository->findDownloads(Download::STATE_DOWNLOADING_COMPLETED);

		$this->assertNotEmpty($downloads);
		$this->assertCount(1, $downloads);
		foreach ($downloads as $download)
		{
			$this->assertGreaterThan(0, $download->getId());
			$this->assertNotEmpty($download->getLink());
			$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $download->getState());
		}
	}

	public function testFindDownloadsWithMultipleState ()
	{
		$this->loadFixtures([
				'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$repository = $client->getContainer()->get('DownloadRepository');
		$downloads = $repository->findDownloads([
				Download::STATE_DOWNLOADING_STARTED,
				Download::STATE_DOWNLOADING_COMPLETED
		]);

		$this->assertNotEmpty($downloads);
		$this->assertCount(2, $downloads);

		$hasStarted = false;
		$hasCompleted = false;
		foreach ($downloads as $download)
		{
			$this->assertGreaterThan(0, $download->getId());
			$this->assertNotEmpty($download->getLink());
			if ($download->getState() == Download::STATE_DOWNLOADING_STARTED)
			{
				$hasStarted = true;
			}
			if ($download->getState() == Download::STATE_DOWNLOADING_COMPLETED)
			{
				$hasCompleted = true;
			}
		}
		$this->assertTrue($hasStarted);
		$this->assertTrue($hasCompleted);
	}

	public function testFindDownloadsByDestination ()
	{
		$this->loadFixtures([
				'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$repository = $client->getContainer()->get('DownloadRepository');
		$downloads = $repository->findDownloadsByDestination(TARTANA_PATH_ROOT . '/var/tmp/test');

		$this->assertNotEmpty($downloads);
		foreach ($downloads as $download)
		{
			$this->assertEquals(TARTANA_PATH_ROOT . '/var/tmp/test', $download->getDestination());
		}
	}

	public function testFindDownloadsByDestinationSeparator ()
	{
		$this->loadFixtures([
				'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$repository = $client->getContainer()->get('DownloadRepository');
		$downloads = $repository->findDownloadsByDestination(TARTANA_PATH_ROOT . '/var/tmp/test/');

		$this->assertNotEmpty($downloads);
		foreach ($downloads as $download)
		{
			$this->assertEquals(TARTANA_PATH_ROOT . '/var/tmp/test', $download->getDestination());
		}
	}
	public function testFindDownloadsByDestinationPart ()
	{
		$this->loadFixtures([
				'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$repository = $client->getContainer()->get('DownloadRepository');
		$downloads = $repository->findDownloadsByDestination('tmp/test');

		$this->assertNotEmpty($downloads);
		foreach ($downloads as $download)
		{
			$this->assertEquals(TARTANA_PATH_ROOT . '/var/tmp/test', $download->getDestination());
		}
	}

	public function testFindDownloadsByInvalidDestination ()
	{
		$this->loadFixtures([
				'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$repository = $client->getContainer()->get('DownloadRepository');
		$downloads = $repository->findDownloadsByDestination('/invalid/');

		$this->assertEmpty($downloads);
	}

	public function testFindDownloadsByInvalidOnlyOneDownload ()
	{
		$this->loadFixtures([
				'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		$repository = $client->getContainer()->get('DownloadRepository');
		$download = $repository->findDownloads()[0];
		$download->setDestination(__DIR__);
		$client->getContainer()
			->get('CommandBus')
			->handle(new SaveDownloads([
				$download
		]));
		$downloads = $repository->findDownloadsByDestination(__DIR__);

		$this->assertNotEmpty($downloads);
		$this->assertCount(1, $downloads);
		$this->assertEquals($download, $downloads[0]);
		$this->assertEquals(__DIR__, $downloads[0]->getDestination());
	}
}
