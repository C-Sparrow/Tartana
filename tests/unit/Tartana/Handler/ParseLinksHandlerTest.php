<?php
namespace Test\Unit\Tartana\Handler;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\Config;
use Tartana\Component\Dlc\Decrypter;
use Tartana\Domain\Command\ParseLinks;
use Tartana\Domain\Command\ProcessLinks;
use Tartana\Handler\ParseLinksHandler;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class ParseLinksHandlerTest extends TartanaBaseTestCase
{

	public function testDlcParseLinksFile ()
	{
		$messageBusMock = $this->getMockCommandBus(
				[
						$this->callback(
								function  ($command) {
									return $command->getLinks()[0] == 'http://foo.bar/kjashd' && $command->getLinks()[1] == 'http://bar.foo/uzwhka';
								})
				]);

		$handler = new ParseLinksHandler($this->getDecrypter(), $messageBusMock, new Registry());
		$handler->handle(new ParseLinks(new NullAdapter(), 'simple.dlc'));
	}

	public function testTxtParseLinksFile ()
	{
		$messageBusMock = $this->getMockCommandBus(
				[
						$this->callback(
								function  (ProcessLinks $command) {
									return $command->getLinks()[0] == 'http://foo.bar/kjashd';
								})
				]);

		$fs = new Local(__DIR__ . '/test');
		$fs->write('simple.txt', 'http://foo.bar/kjashd', new Config());

		$handler = new ParseLinksHandler($this->getDecrypter(), $messageBusMock, new Registry());
		$handler->handle(new ParseLinks($fs, 'simple.txt'));
	}

	public function testParseLinksFileHttps ()
	{
		$messageBusMock = $this->getMockCommandBus(
				[
						$this->callback(
								function  ($command) {
									return $command->getLinks()[0] == 'https://foo.bar/kjashd' && $command->getLinks()[1] == 'https://bar.foo/uzwhka';
								})
				]);

		$handler = new ParseLinksHandler($this->getDecrypter(), $messageBusMock,
				new Registry([
						'links' => [
								'convertToHttps' => true
						]
				]));
		$handler->handle(new ParseLinks(new NullAdapter(), 'simple.dlc'));
	}

	public function testParseLinksFilterHosts ()
	{
		$messageBusMock = $this->getMockCommandBus(
				[
						$this->callback(function  ($command) {
							return $command->getLinks()[0] == 'http://foo.bar/kjashd';
						})
				]);

		$handler = new ParseLinksHandler($this->getDecrypter(), $messageBusMock,
				new Registry([
						'links' => [
								'hostFilter' => 'foo.bar'
						]
				]));
		$handler->handle(new ParseLinks(new NullAdapter(), 'simple.dlc'));
	}

	public function testParseLinksFileWithEmptyLines ()
	{
		$messageBusMock = $this->getMockCommandBus(
				[
						$this->callback(function  (ProcessLinks $command) {
							return $command->getLinks()[0] == 'http://foo.bar/kjashd';
						})
				]);

		$fs = new Local(__DIR__ . '/test');
		$fs->write('simple.txt', 'http://foo.bar/kjashd' . PHP_EOL . '' . PHP_EOL, new Config());

		$handler = new ParseLinksHandler($this->getDecrypter(), $messageBusMock, new Registry());
		$handler->handle(new ParseLinks($fs, 'simple.txt'));
	}

	public function testNoValidFile ()
	{
		$handler = new ParseLinksHandler($this->getDecrypter(), $this->getMockCommandBus(), new Registry());
		$handler->handle(new ParseLinks(new NullAdapter(), 'simple.file'));
	}

	protected function setUp ()
	{
		$fs = new Local(__DIR__);
		if ($fs->has('test'))
		{
			$fs->deleteDir('test');
		}
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		if ($fs->has('test'))
		{
			$fs->deleteDir('test');
		}
	}

	private function getDecrypter ()
	{
		$dlcDecrypter = $this->getMockBuilder(Decrypter::class)->getMock();
		$dlcDecrypter->method('decrypt')->willReturn(array(
				'http://foo.bar/kjashd',
				'http://bar.foo/uzwhka'
		));
		return $dlcDecrypter;
	}
}