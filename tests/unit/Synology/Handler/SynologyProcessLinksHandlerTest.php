<?php
namespace Tests\Unit\Synology\Handler;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Domain\Command\ProcessLinks;
use Synology\Handler\SynologyProcessLinksHandler;
use Tartana\Util;

class SynologyProcessLinksHandlerTest extends \PHPUnit_Framework_TestCase
{

	public function testProcessLinks ()
	{
		$handler = new SynologyProcessLinksHandler($this->getMockClient(), new Registry([
				'downloads' => __DIR__ . '/test'
		]));
		$handler->handle(new ProcessLinks([
				'http://foo.bar/kjashd',
				'http://bar.foo/uzwhka'
		]));

		$fs = new Local(__DIR__ . '/test');

		$this->assertNotEmpty($fs->listContents());
		$this->assertEquals(1, count($fs->listContents()));

		$this->assertStringStartsWith('job-', $fs->listContents()[0]['path']);
	}

	public function testProcessLinksDirectoryExists ()
	{
		$handler = new SynologyProcessLinksHandler($this->getMockClient(), new Registry([
				'downloads' => __DIR__ . '/test'
		]));

		$fs = new Local(__DIR__ . '/test');
		for ($i = 0; $i < 5; $i ++)
		{
			$fs->createDir('job-' . date('YmdHis', time() + $i) . '-1', new Config());
		}

		$existingDirectories = $fs->listContents();

		$handler->handle(new ProcessLinks([
				'http://foo.bar/kjashd',
				'http://bar.foo/uzwhka'
		]));

		$this->assertNotEmpty($fs->listContents());
		$this->assertCount(count($existingDirectories) + 1, $fs->listContents());

		$hasNewNumber = false;
		foreach ($fs->listContents() as $dir)
		{
			if (Util::endsWith($dir['path'], '-2'))
			{
				$hasNewNumber = true;
				break;
			}
		}
		$this->assertTrue($hasNewNumber);
	}

	public function testProcessLinksInvalidDirectory ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');

		$handler = new SynologyProcessLinksHandler($this->getMockClient(), new Registry([
				'downloads' => __DIR__ . '/invalid-dir'
		]));
		$handler->handle(new ProcessLinks([
				'http://foo.bar/kjashd',
				'http://bar.foo/uzwhka'
		]));

		foreach ($fs->listContents() as $file)
		{
			$this->assertNotEquals('dir', $file['type']);
		}
	}

	protected function setUp ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test');
		$fs->createDir('test', new Config());
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test/');
	}

	private function getMockClient ()
	{
		$client = $this->getMockBuilder(ClientInterface::class)->getMock();
		$client->method('request')->will(
				$this->returnCallback(
						function  ($method, $url, $arguments) {
							$content = [
									'success' => true,
									'data' => []
							];

							parse_str($arguments['body'], $arguments);
							if (key_exists('method', $arguments))
							{
								switch ($arguments['method'])
								{
									case 'login':
										$content['data']['sid'] = 1234;
								}
							}
							return new Response(200, [
									'Content-Type' => 'application/json'
							], json_encode($content));
						}));
		return $client;
	}
}