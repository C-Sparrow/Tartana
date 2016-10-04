<?php
namespace Tests\Unit\Tartana\Component\Decrypter;

use League\Flysystem\Adapter\Local;
use Tests\Unit\Tartana\TartanaBaseTestCase;

abstract class BaseDecrypterTestCase extends TartanaBaseTestCase
{

	abstract protected function getDecrypter();

	public function testDecryptFile()
	{
		$file = __DIR__ . '/files/simple.' . $this->getFileExtension();
		$dec = $this->getDecrypter();
		$links = $dec->decrypt($file);

		$this->assertTrue(is_array($links));
		$this->assertGreaterThanOrEqual(2, count($links));

		foreach ($links as $link) {
			$this->assertContains('http', $link);
		}
	}

	public function testDecryptWrongFile()
	{
		$file = __DIR__ . '/not-existing.' . $this->getFileExtension();
		$dec = $this->getDecrypter();

		$this->setExpectedException('RuntimeException');
		$dec->decrypt($file);
	}

	public function testDecryptContent()
	{
		$fs = new Local(__DIR__);
		$content = $fs->read('/files/simple.' . $this->getFileExtension())['contents'];
		$dec = $this->getDecrypter();
		$links = $dec->decrypt($content);

		$this->assertTrue(is_array($links));
		$this->assertGreaterThanOrEqual(2, count($links));

		foreach ($links as $link) {
			$this->assertContains('http', $link);
		}
	}

	public function testDecryptWrongContent()
	{
		$dec = $this->getDecrypter();

		$this->setExpectedException('RuntimeException');
		$dec->decrypt('unit test');
	}

	public function testDecryptEmptyContent()
	{
		$dec = $this->getDecrypter();

		$this->setExpectedException('RuntimeException');
		$dec->decrypt('');
		$dec->decrypt(null);
	}

	private function getFileExtension()
	{
		$name = strtolower((new \ReflectionClass($this))->getShortName());

		return str_replace('test', '', $name);
	}
}
