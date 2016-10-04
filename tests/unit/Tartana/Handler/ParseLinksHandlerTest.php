<?php
namespace Test\Unit\Tartana\Handler;

use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Config;
use Tartana\Component\Decrypter\DecrypterFactory;
use Tartana\Component\Decrypter\DecrypterInterface;
use Tartana\Domain\Command\ParseLinks;
use Tartana\Domain\Command\ProcessLinks;
use Tartana\Handler\ParseLinksHandler;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class ParseLinksHandlerTest extends TartanaBaseTestCase
{

	public function testParseLinksFile()
	{
		$messageBusMock = $this->getMockCommandBus(
			[
						$this->callback(
							function ($command) {
									return $command->getLinks()[0] == 'http://foo.bar/kjashd' && $command->getLinks()[1] == 'http://bar.foo/uzwhka';
							}
						)
			]
		);

		$handler = new ParseLinksHandler($this->getDecrypterFactory(), $messageBusMock, new Registry());
		$handler->handle(new ParseLinks(new NullAdapter(), 'simple.txt'));
	}

	public function testParseLinksFileHttps()
	{
		$messageBusMock = $this->getMockCommandBus(
			[
						$this->callback(
							function ($command) {
									return $command->getLinks()[0] == 'https://foo.bar/kjashd' && $command->getLinks()[1] == 'https://bar.foo/uzwhka';
							}
						)
			]
		);

		$handler = new ParseLinksHandler(
			$this->getDecrypterFactory(),
			$messageBusMock,
			new Registry([
						'links' => [
								'convertToHttps' => true
						]
			])
		);
		$handler->handle(new ParseLinks(new NullAdapter(), 'simple.txt'));
	}

	public function testParseLinksFilterHosts()
	{
		$messageBusMock = $this->getMockCommandBus(
			[
						$this->callback(
							function ($command) {
									return count($command->getLinks()) == 1 && $command->getLinks()[0] == 'http://foo.bar/kjashd';
							}
						)
			]
		);

		$handler = new ParseLinksHandler(
			$this->getDecrypterFactory(),
			$messageBusMock,
			new Registry([
						'links' => [
								'hostFilter' => 'foo.bar'
						]
			])
		);
		$handler->handle(new ParseLinks(new NullAdapter(), 'simple.txt'));
	}

	public function testParseLinksFilterHostsRegexExclude()
	{
		$messageBusMock = $this->getMockCommandBus(
			[
						$this->callback(
							function ($command) {
									return count($command->getLinks()) == 1 && $command->getLinks()[0] != 'http://foo.bar/kjashd';
							}
						)
			]
		);

		$handler = new ParseLinksHandler(
			$this->getDecrypterFactory(),
			$messageBusMock,
			new Registry([
						'links' => [
								'hostFilter' => '^((?!kjashd).)*$'
						]
			])
		);
		$handler->handle(new ParseLinks(new NullAdapter(), 'simple.txt'));
	}

	public function testParseLinksFilterHostsRegexMultiple()
	{
		$messageBusMock = $this->getMockCommandBus(
			[
						$this->callback(
							function ($command) {
									return count($command->getLinks()) == 2 && in_array('http://foo.bar/kjashd', $command->getLinks()) &&
											 in_array('http://bar.foo/kjashd', $command->getLinks());
							}
						)
			]
		);

		$handler = new ParseLinksHandler(
			$this->getDecrypterFactory([
						'http://foo.bar/kjashd',
						'http://bar.foo/kjashd',
						'http://invalid.not/kjashd'
				]),
			$messageBusMock,
			new Registry([
						'links' => [
								'hostFilter' => '(foo.bar|bar.foo)'
						]
			])
		);
				$handler->handle(new ParseLinks(new NullAdapter(), 'simple.txt'));
	}

	public function testParseLinksFileWithEmptyLines()
	{
		$messageBusMock = $this->getMockCommandBus(
			[
						$this->callback(function (ProcessLinks $command) {
							return $command->getLinks()[0] == 'http://foo.bar/kjashd';
						})
			]
		);

		$fs = new Local(__DIR__ . '/test');
		$fs->write('simple.txt', 'http://foo.bar/kjashd' . PHP_EOL . '' . PHP_EOL, new Config());

		$handler = new ParseLinksHandler($this->getDecrypterFactory(), $messageBusMock, new Registry());
		$handler->handle(new ParseLinks($fs, 'simple.txt'));
	}

	public function testParseLinksFileThrowException()
	{
		$fs = new Local(__DIR__ . '/test');
		$fs->write('simple.txt', '' . PHP_EOL . '' . PHP_EOL, new Config());

		$dec = $this->getMockBuilder(DecrypterInterface::class)->getMock();
		$dec->method('decrypt')->willThrowException(new \RuntimeException('unit test'));
		$dlcDecrypter = $this->getMockBuilder(DecrypterFactory::class)->getMock();
		$dlcDecrypter->method('createDecryptor')->willReturn($dec);

		$handler = new ParseLinksHandler($dlcDecrypter, $this->getMockCommandBus(), new Registry());
		$handler->handle(new ParseLinks($fs, 'simple.txt'));
	}

	public function testNoValidFile()
	{
		$handler = new ParseLinksHandler($this->getMockBuilder(DecrypterFactory::class)->getMock(), $this->getMockCommandBus(), new Registry());
		$handler->handle(new ParseLinks(new NullAdapter(), 'simple.file'));
	}

	protected function setUp()
	{
		$fs = new Local(__DIR__);
		if ($fs->has('test')) {
			$fs->deleteDir('test');
		}
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		if ($fs->has('test')) {
			$fs->deleteDir('test');
		}
	}

	private function getDecrypterFactory($links = null)
	{
		if ($links == null) {
			$links = [
					'http://foo.bar/kjashd',
					'http://bar.foo/uzwhka'
			];
		}

		$dec = $this->getMockBuilder(DecrypterInterface::class)->getMock();
		$dec->method('decrypt')->willReturn($links);
		$dlcDecrypter = $this->getMockBuilder(DecrypterFactory::class)->getMock();
		$dlcDecrypter->method('createDecryptor')->willReturn($dec);
		return $dlcDecrypter;
	}
}
