<?php
namespace Tests\Functional\Local\Handler;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Local\Handler\LocalSaveDownloadsHandler;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;

class LocalSaveDownloadsHandlerTest extends WebTestCase
{

	public function testCreateDownload()
	{
		$this->loadFixtures([]);

		$client = static::createClient();

		$download = new Download();
		$download->setLink('http://foo.bar/lhadu');
		$download->setDestination(__DIR__);

		$handler = $client->getContainer()->get('LocalSaveDownloadsHandler');
		$handler->handle(new SaveDownloads([
				$download
		]));

		$repository = $client->getContainer()->get('DownloadRepository');
		$downloads = $repository->findDownloads();

		$this->assertNotEmpty($downloads);
		$this->assertCount(1, $downloads);
		$this->assertGreaterThan(0, $downloads[0]->getId());
		$this->assertEquals('http://foo.bar/lhadu', $downloads[0]->getLink());
	}

	public function testCreateDownloadNoLink()
	{
		$this->loadFixtures([]);

		$client = static::createClient();

		$this->setExpectedException('Doctrine\DBAL\Exception\NotNullConstraintViolationException');

		$handler = $client->getContainer()->get('LocalSaveDownloadsHandler');
		$handler->handle(new SaveDownloads([
				new Download()
		]));
	}

	public function testCreateDownloadNoDestination()
	{
		$this->loadFixtures([]);

		$client = static::createClient();

		$this->setExpectedException('Doctrine\DBAL\Exception\NotNullConstraintViolationException');

		$download = new Download();
		$download->setLink('http://foo.bar/ashd');

		$handler = $client->getContainer()->get('LocalSaveDownloadsHandler');
		$handler->handle(new SaveDownloads([
				$download
		]));
	}

	public function testUpdateDownload()
	{
		$this->loadFixtures([]);

		$client = static::createClient();

		$download = new Download();
		$download->setLink('http://foo.bar/lhadu');
		$download->setDestination(__DIR__);

		$handler = $client->getContainer()->get('LocalSaveDownloadsHandler');
		$handler->handle(new SaveDownloads([
				$download
		]));

		$repository = $client->getContainer()->get('DownloadRepository');
		$download = $repository->findDownloads()[0];
		$download->setLink('http://foo.bar/new');
		$download->setProgress(10.33);
		$handler->handle(new SaveDownloads([
				$download
		]));

		$downloads = $repository->findDownloads();

		$this->assertNotEmpty($downloads);
		$this->assertCount(1, $downloads);
		$this->assertGreaterThan(0, $downloads[0]->getId());
		$this->assertEquals('http://foo.bar/new', $downloads[0]->getLink());
		$this->assertEquals(10.33, $downloads[0]->getProgress());
	}

	public function testUpdateDownloadClone()
	{
		$this->loadFixtures([]);

		$client = static::createClient();

		$download = new Download();
		$download->setLink('http://foo.bar/lhadu');
		$download->setDestination(__DIR__);

		$handler = $client->getContainer()->get('LocalSaveDownloadsHandler');
		$handler->handle(new SaveDownloads([
				$download
		]));

		$repository = $client->getContainer()->get('DownloadRepository');
		$download = clone $repository->findDownloads()[0];
		$download->setLink('http://foo.bar/new');
		$download->setProgress(10.33);
		$handler->handle(new SaveDownloads([
				$download
		]));

		$downloads = $repository->findDownloads();

		$this->assertNotEmpty($downloads);
		$this->assertCount(1, $downloads);
		$this->assertGreaterThan(0, $downloads[0]->getId());
		$this->assertEquals('http://foo.bar/new', $downloads[0]->getLink());
		$this->assertEquals(10.33, $downloads[0]->getProgress());
	}
}
