<?php
namespace Tests\Functional\Tartana\Controller;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;

class ApiLogControllerTest extends WebTestCase
{

	private $client = null;

	public function testV1FindLogs()
	{
		$logger = $this->client->getContainer()->get('Logger');
		$logger->log(Logger::ERROR, 'Unit test.');
		$crawler = $this->client->request('GET', '/api/v1/log/find');

		$this->assertEquals(200, $this->client->getResponse()
			->getStatusCode());
		$resp = json_decode($this->client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);
		$this->assertNotEmpty($resp->data);
		$this->assertCount(1, $resp->data);

		$this->assertNotEmpty($resp->data[0]->date);
		$this->assertNotEmpty($resp->data[0]->message);
		$this->assertContains('Unit test.', $resp->data[0]->message);
	}

	public function testV1FindLogsMany()
	{
		$logger = $this->client->getContainer()->get('Logger');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');

		$crawler = $this->client->request('GET', '/api/v1/log/find');

		$this->assertEquals(200, $this->client->getResponse()
			->getStatusCode());
		$resp = json_decode($this->client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);
		$this->assertNotEmpty($resp->data);
		$this->assertCount(12, $resp->data);

		foreach ($resp->data as $log) {
			$this->assertNotEmpty($log->date);
			$this->assertNotEmpty($log->message);
			$this->assertContains('Unit test.', $log->message);
		}
	}

	public function testV1ClearAll()
	{
		$logger = $this->client->getContainer()->get('Logger');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');
		$logger->log(Logger::INFO, 'Unit test.');

		$crawler = $this->client->request('GET', '/api/v1/log/deleteall');

		$this->assertEquals(200, $this->client->getResponse()
			->getStatusCode());
		$resp = json_decode($this->client->getResponse()->getContent());

		$this->assertNotEmpty($resp);
		$this->assertEquals(true, $resp->success);
		$this->assertEmpty($resp->message);

		$this->assertFileNotExists($this->client->getContainer()
			->getParameter('tartana.log.path'));
	}

	protected function setUp()
	{
		$this->loadFixtures([]);

		$client = static::createClient();

		$logFileName = $client->getContainer()->getParameter('kernel.environment') . '.log';
		$fs          = new Local($client->getContainer()->getParameter('kernel.logs_dir'));
		$fs->write($logFileName, '', new Config());

		/** @var \Monolog\Logger $logger * */
		$logger      = $client->getContainer()->get('Logger');
		$oldHandlers = $logger->getHandlers();
		$logger->setHandlers([
			new StreamHandler($fs->applyPathPrefix($logFileName))
		]);

		$this->client = $client;
	}
}
