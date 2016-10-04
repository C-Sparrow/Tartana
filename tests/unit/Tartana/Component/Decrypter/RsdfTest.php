<?php
namespace Tests\Unit\Tartana\Component\Decrypter;

use Tartana\Component\Decrypter\Rsdf;

class RsdfTest extends BaseDecrypterTestCase
{

	public function testDecryptFile2()
	{
		$file = __DIR__ . '/files/simple1.rsdf';
		$dec = $this->getDecrypter();
		$links = $dec->decrypt($file);

		$this->assertTrue(is_array($links));
		$this->assertGreaterThanOrEqual(2, count($links));

		foreach ($links as $link) {
			$this->assertContains('http', $link);
		}
	}

	protected function getDecrypter()
	{
		return new Rsdf();
	}
}
