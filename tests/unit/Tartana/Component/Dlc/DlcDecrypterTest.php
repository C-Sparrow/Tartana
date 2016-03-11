<?php
namespace Tests\Unit\Tartana\Component;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use League\Flysystem\Adapter\Local;
use Tartana\Component\Dlc\Decrypter;

class DlcDecrypterTest extends \PHPUnit_Framework_TestCase
{

	public function testDecryptFile ()
	{
		$file = __DIR__ . '/simple.dlc';
		$dec = new Decrypter($this->getMockClient());
		$links = $dec->decrypt($file);

		$this->assertTrue(is_array($links));
		$this->assertCount(2, $links);

		foreach ($links as $link)
		{
			$this->assertContains('http', $link);
		}
	}

	public function testDecryptWrongFile ()
	{
		$file = __DIR__ . '/not-existing.dlc';
		$dec = new Decrypter($this->getMockClient());

		$this->setExpectedException('RuntimeException');
		$dec->decrypt($file);
	}

	public function testDecryptContent ()
	{
		$fs = new Local(__DIR__);
		$content = $fs->read('/simple.dlc')['contents'];
		$dec = new Decrypter($this->getMockClient());
		$links = $dec->decrypt($content);

		$this->assertTrue(is_array($links));
		$this->assertCount(2, $links);

		foreach ($links as $link)
		{
			$this->assertContains('http', $link);
		}
	}

	public function testDecryptWrongContent ()
	{
		$dec = new Decrypter($this->getMockClient());

		$this->setExpectedException('RuntimeException');
		$dec->decrypt('unit test');
	}

	public function testDecryptEmptyContent ()
	{
		$dec = new Decrypter($this->getMockClient());

		$this->setExpectedException('RuntimeException');
		$dec->decrypt('');
		$dec->decrypt(null);
	}

	private function getMockClient ()
	{
		$client = $this->getMockBuilder(ClientInterface::class)->getMock();
		$client->method('request')->will(
				$this->returnCallback(
						function  ($method, $url, $arguments) {
							$content = [
									'success' => [
											'links' => [
													'http://foo.bar/ldasd',
													'http://foo.bar/ggaweree'
											]
									]
							];

							$arg = $arguments['form_params']['content'];
							if ($arg == 'unit test' || $arg == __DIR__ . '/not-existing.dlc')
							{
								unset($content['success']);
							}

							return new Response(200, [
									'Content-Type' => 'application/json'
							], json_encode($content));
						}));
		return $client;
	}
}