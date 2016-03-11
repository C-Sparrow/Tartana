<?php
namespace Tests\Connection\Tartana\Component;
use Tartana\Component\Dlc\Decrypter;

class DlcDecrypterTest extends \PHPUnit_Framework_TestCase
{

	public function testRealDecryptFile ()
	{
		$file = __DIR__ . '/../../../../unit/Tartana/Component/Dlc/simple.dlc';
		$dec = new Decrypter();
		$links = $dec->decrypt($file);

		$this->assertTrue(is_array($links));
		$this->assertCount(11, $links);

		foreach ($links as $link)
		{
			$this->assertContains('http', $link);
		}
	}
}