<?php
namespace Tests\Unit\Tartana\Component\Decrypter;
use Tartana\Component\Decrypter\Txt;

class TxtTest extends BaseDecrypterTestCase
{

	protected function getDecrypter ()
	{
		return new Txt();
	}
}