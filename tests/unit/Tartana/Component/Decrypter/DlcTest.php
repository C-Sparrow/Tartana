<?php
namespace Tests\Unit\Tartana\Component\Decrypter;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Tartana\Component\Decrypter\Dlc;

class DlcTest extends BaseDecrypterTestCase
{

	protected function getDecrypter ()
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
		return new Dlc($client);
	}
}