<?php
namespace Tests\Functional\Tartana\Controller;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Tartana\Entity\Download;

class ApiDownloadControllerTest extends WebTestCase
{

	public function testV1FindDownloads()
	{
		$this->loadFixtures([
			'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		/* @var \Tartana\Domain\DownloadRepository $repository */
		$repository = $client->getContainer()->get('DownloadRepository');

		$crawler = $client->request('GET', '/api/v1/download/find');

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);
		$this->assertNotEmpty($resp->data);

		foreach ($resp->data as $download) {
			$this->assertNotEmpty($download->id);
			$this->assertNotEmpty($download->link);
		}

		$this->assertEquals(count($resp->data), count($repository->findDownloads()));
	}

	public function testV1FindDownloadsWithState()
	{
		$this->loadFixtures([
			'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		/* @var \Tartana\Domain\DownloadRepository $repository */
		$repository = $client->getContainer()->get('DownloadRepository');

		$crawler = $client->request('GET', '/api/v1/download/find/' . Download::STATE_DOWNLOADING_STARTED);

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);
		$this->assertNotEmpty($resp->data);
		$this->assertCount(1, $resp->data);
		$this->assertEquals(
			$client->getContainer()
				->get('Translator')
				->trans('TARTANA_ENTITY_DOWNLOAD_STATE_' . Download::STATE_DOWNLOADING_STARTED),
			$resp->data[0]->state
		);
	}

	public function testV1FindDownloadsWithMultipleState()
	{
		$this->loadFixtures([
			'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		/* @var \Tartana\Domain\DownloadRepository $repository */
		$repository = $client->getContainer()->get('DownloadRepository');

		$crawler = $client->request('GET', '/api/v1/download/find/' . Download::STATE_DOWNLOADING_STARTED . ',' . Download::STATE_DOWNLOADING_ERROR);

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);
		$this->assertNotEmpty($resp->data);
		$this->assertCount(2, $resp->data);
		$this->assertEquals(
			$client->getContainer()
				->get('Translator')
				->trans('TARTANA_ENTITY_DOWNLOAD_STATE_' . Download::STATE_DOWNLOADING_STARTED),
			$resp->data[0]->state
		);
		$this->assertEquals(
			$client->getContainer()
				->get('Translator')
				->trans('TARTANA_ENTITY_DOWNLOAD_STATE_' . Download::STATE_DOWNLOADING_ERROR),
			$resp->data[1]->state
		);
	}

	public function testV1FindDownloadsEmpty()
	{
		$this->loadFixtures([]);

		$client = static::createClient();

		/* @var \Tartana\Domain\DownloadRepository $repository */
		$repository = $client->getContainer()->get('DownloadRepository');

		$crawler = $client->request('GET', '/api/v1/download/find');
		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);
		$this->assertEmpty($resp->data);

		$this->assertEquals(count($resp->data), count($repository->findDownloads()));
	}

	public function testV1ClearAll()
	{
		$this->loadFixtures([
			'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		/* @var \Tartana\Domain\DownloadRepository $repository */
		$repository = $client->getContainer()->get('DownloadRepository');

		$crawler = $client->request('GET', '/api/v1/download/clearall');
		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);
		$this->assertfalse(isset($resp->data));

		$this->assertEmpty($repository->findDownloads());
	}

	public function testV1ClearCompleted()
	{
		$this->loadFixtures([
			'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		/* @var \Tartana\Domain\DownloadRepository $repository */
		$repository = $client->getContainer()->get('DownloadRepository');

		$crawler = $client->request('GET', '/api/v1/download/clearcompleted');
		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);

		foreach ($repository->findDownloads() as $download) {
			$this->assertNotEquals(Download::STATE_PROCESSING_COMPLETED, $download->getState());
		}
	}

	public function testV1ResumeFailed()
	{
		$this->loadFixtures([
			'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		/* @var \Tartana\Domain\DownloadRepository $repository */
		$repository = $client->getContainer()->get('DownloadRepository');

		$crawler = $client->request('GET', '/api/v1/download/resumefailed');
		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);

		foreach ($repository->findDownloads() as $download) {
			$this->assertNotEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
		}
	}

	public function testV1ResumeAll()
	{
		$this->loadFixtures([
			'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		/* @var \Tartana\Domain\DownloadRepository $repository */
		$repository = $client->getContainer()->get('DownloadRepository');

		$crawler = $client->request('GET', '/api/v1/download/resumeall');
		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);

		foreach ($repository->findDownloads() as $download) {
			$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
		}
	}

	public function testV1Reprocress()
	{
		$this->loadFixtures([
			'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		/* @var \Tartana\Domain\DownloadRepository $repository */
		$repository = $client->getContainer()->get('DownloadRepository');

		$crawler = $client->request('GET', '/api/v1/download/reprocess');

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);

		$hasNotStarted = false;
		$hasStarted    = false;
		$hasError      = false;
		foreach ($repository->findDownloads() as $download) {
			$this->assertNotContains(
				$download->getState(),
				[
					Download::STATE_PROCESSING_NOT_STARTED,
					Download::STATE_PROCESSING_STARTED,
					Download::STATE_PROCESSING_COMPLETED,
					Download::STATE_PROCESSING_ERROR
				]
			);

			if ($download->getState() == Download::STATE_DOWNLOADING_NOT_STARTED) {
				$hasNotStarted = true;
			}
			if ($download->getState() == Download::STATE_DOWNLOADING_STARTED) {
				$hasStarted = true;
			}
			if ($download->getState() == Download::STATE_DOWNLOADING_ERROR) {
				$hasError = true;
			}
		}
		$this->assertTrue($hasNotStarted);
		$this->assertTrue($hasStarted);
		$this->assertTrue($hasError);
	}

	public function testV1ReprocressWithExistingDirectory()
	{
		$this->loadFixtures([
			'Local\DataFixtures\ORM\LoadDownloadData'
		]);

		$client = static::createClient();

		/* @var \Tartana\Domain\DownloadRepository $repository */
		$repository = $client->getContainer()->get('DownloadRepository');

		$destination = new Local(__DIR__ . '/test');
		$client->getContainer()
			->get('config')
			->set('extract.destination', $destination->getPathPrefix());

		$downloadToCheck = null;
		foreach ($repository->findDownloads() as $download) {
			if ($download->getState() != Download::STATE_PROCESSING_COMPLETED) {
				continue;
			}
			$downloadToCheck = $download;
			$destination->createDir(basename($downloadToCheck->getDestination()), new Config());
			break;
		}

		$crawler = $client->request('GET', '/api/v1/download/reprocess');

		$this->assertEquals(200, $client->getResponse()
			->getStatusCode());
		$resp = json_decode($client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);

		$this->assertFalse($destination->has(basename($downloadToCheck->getDestination())));
	}

	protected function setUp()
	{
		$fs = new Local(__DIR__);
		if ($fs->has('test')) {
			$fs->deleteDir('test');
		}
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		if ($fs->has('test')) {
			$fs->deleteDir('test');
		}
	}
}
