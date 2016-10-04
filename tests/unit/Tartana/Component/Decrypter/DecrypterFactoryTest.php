<?php
namespace Tests\Unit\Tartana\Component\Decrypter;

use Psr\Log\LoggerInterface;
use Tartana\Component\Decrypter\DecrypterFactory;
use Tartana\Component\Decrypter\DecrypterInterface;
use Tartana\Component\Decrypter\Dlc;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class DecrypterFactoryTest extends TartanaBaseTestCase
{

	public function testLocalHost()
	{
		$factory = new DecrypterFactory();
		$decryptor = $factory->createDecryptor('simple.dlc');

		$this->assertInstanceOf(DecrypterInterface::class, $decryptor);
		$this->assertInstanceOf(Dlc::class, $decryptor);
	}

	public function testClassNotExists()
	{
		$factory = new DecrypterFactory();
		$decryptor = $factory->createDecryptor('test.notexists');

		$this->assertEmpty($decryptor);
	}

	public function testNotCorrectInterface()
	{
		$factory = new DecrypterFactory();
		$decryptor = $factory->createDecryptor('test.Decrypterfactorytestinvalid');

		$this->assertEmpty($decryptor);
	}

	public function testLoggerSet()
	{
		$logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
		$factory = new DecrypterFactory();
		$factory->setLogger($logger);
		$decryptor = $factory->createDecryptor('test.txt');

		$this->assertNotEmpty($decryptor);
		$this->assertEquals($logger, $decryptor->getLogger());
	}
}
namespace Tartana\Component\Decrypter;

class Decrypterfactorytestinvalid
{
}
