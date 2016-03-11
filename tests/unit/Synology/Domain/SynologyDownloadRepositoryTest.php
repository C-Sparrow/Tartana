<?php
namespace Tests\Unit\Synology\Domain;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Joomla\Registry\Registry;
use Synology\Domain\SynologyDownloadRepository;
use Tartana\Entity\Download;

class SynologyDownloadRepositoryTest extends \PHPUnit_Framework_TestCase
{

	public function testFindAllDownloads ()
	{
		$repository = new SynologyDownloadRepository($this->getMockClient(), new Registry());

		$downloads = $repository->findDownloads();

		$this->assertNotEmpty($downloads);
		$this->assertCount(4, $downloads);

		foreach ($downloads as $download)
		{
			$this->assertNotEmpty($download->getId());
			$this->assertNotEmpty($download->getLink());
			$this->assertNotEmpty($download->getDestination());
			$this->assertNotEmpty($download->getState());
		}
	}

	public function testFindDownloadsSingleState ()
	{
		$repository = new SynologyDownloadRepository($this->getMockClient(), new Registry());

		$downloads = $repository->findDownloads(Download::STATE_DOWNLOADING_COMPLETED);

		$this->assertNotEmpty($downloads);
		$this->assertCount(1, $downloads);

		$this->assertEquals('db_001', $downloads[0]->getId());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());
	}

	public function testFindDownloadsMultipleState ()
	{
		$repository = new SynologyDownloadRepository($this->getMockClient(), new Registry());

		$downloads = $repository->findDownloads([
				Download::STATE_DOWNLOADING_COMPLETED,
				Download::STATE_DOWNLOADING_ERROR
		]);

		$this->assertNotEmpty($downloads);
		$this->assertCount(2, $downloads);

		$this->assertEquals('db_001', $downloads[0]->getId());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());
		$this->assertEquals('db_003', $downloads[1]->getId());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $downloads[1]->getState());
	}

	public function testFindDownloadsByDestination ()
	{
		$repository = new SynologyDownloadRepository($this->getMockClient(), new Registry());

		$downloads = $repository->findDownloadsByDestination('/Domain/');

		$this->assertNotEmpty($downloads);
		$this->assertCount(2, $downloads);

		$this->assertEquals('db_001', $downloads[0]->getId());
		$this->assertEquals('/Domain', $downloads[0]->getDestination());
		$this->assertEquals('db_004', $downloads[1]->getId());
		$this->assertEquals('/Domain', $downloads[1]->getDestination());
	}

	public function testFindDownloadsByDestinationPart ()
	{
		$repository = new SynologyDownloadRepository($this->getMockClient(), new Registry());

		$downloads = $repository->findDownloadsByDestination('ain');

		$this->assertNotEmpty($downloads);
		$this->assertCount(2, $downloads);

		$this->assertEquals('db_001', $downloads[0]->getId());
		$this->assertEquals('/Domain', $downloads[0]->getDestination());
		$this->assertEquals('db_004', $downloads[1]->getId());
		$this->assertEquals('/Domain', $downloads[1]->getDestination());
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
									case 'list':
										$content['data']['tasks'] = [
												(object) array(
														'id' => 'db_001',
														'status' => 'finished',
														'additional' => [
																'detail' => [
																		'uri' => 'http://foo.bar/lhueejk',
																		'destination' => __DIR__
																]
														]
												),
												(object) array(
														'id' => 'db_002',
														'status' => 'downloading',
														'additional' => [
																'detail' => [
																		'uri' => 'http://foo.bar/eddeed',
																		'destination' => ''
																]
														]
												),
												(object) array(
														'id' => 'db_003',
														'status' => 'error',
														'additional' => [
																'detail' => [
																		'uri' => 'http://foo.bar/error',
																		'destination' => ''
																]
														]
												),
												(object) array(
														'id' => 'db_004',
														'status' => 'extracting',
														'additional' => [
																'detail' => [
																		'uri' => 'http://foo.bar/extracting',
																		'destination' => __DIR__
																]
														]
												)
										];
								}
							}
							return new Response(200, [
									'Content-Type' => 'application/json'
							], json_encode($content));
						}));
		return $client;
	}
}