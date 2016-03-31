<?php
namespace Tests\Connection\Tartana\Component\Decrypter;
use Tartana\Component\Decrypter\Dlc;

class DlcTest extends \PHPUnit_Framework_TestCase
{

	public function testRealDecryptFile ()
	{
		$file = __DIR__ . '/../../../../unit/Tartana/Component/Decrypter/files/simple.dlc';
		$dec = new Dlc();
		$links = $dec->decrypt($file);

		$this->assertTrue(is_array($links));
		$this->assertCount(11, $links);

		foreach ($links as $link)
		{
			$this->assertContains('http', $link);
		}
	}
}