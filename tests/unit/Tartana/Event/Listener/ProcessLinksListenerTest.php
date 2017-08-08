<?php
namespace Tests\Unit\Tartana\Event\Listener;

use Tartana\Domain\Command\ProcessLinks;
use Tartana\Domain\Command\SaveParameters;
use Tartana\Event\CommandEvent;
use Tartana\Event\Listener\ProcessLinksListener;
use Tartana\Host\HostInterface;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class ProcessLinksListenerTest extends TartanaBaseTestCase
{

	public function testProcessingLinks()
	{
		$host = $this->getMockBuilder(HostInterface::class)->getMock();
		$host->expects($this->once())
			->method('fetchLinkList')
			->willReturn([
				'http://bar.foo'
			])
			->with($this->callback(function ($link) {
				return $link == 'http://foo.bar';
			}));

		$listener = new ProcessLinksListener();
		$listener->setHostFactory($this->getMockHostFactory($host));

		$command = new CommandEvent(new ProcessLinks([
			'http://foo.bar'
		]));
		$listener->onProcessLinksBefore($command);

		$links = $command->getCommand()->getLinks();
		$this->assertCount(1, $links);
		$this->assertEquals([
			'http://bar.foo'
		], $links);
	}

	public function testProcessingLinksNoDownloader()
	{
		$listener = new ProcessLinksListener();
		$listener->setHostFactory($this->getMockHostFactory(null));

		$command = new CommandEvent(new ProcessLinks([
			'http://foo.bar'
		]));
		$listener->onProcessLinksBefore($command);

		$links = $command->getCommand()->getLinks();
		$this->assertCount(1, $links);
		$this->assertEquals([
			'http://foo.bar'
		], $links);
	}

	public function testInvalidCommand()
	{
		$listener = new ProcessLinksListener();
		$listener->setHostFactory($this->getMockHostFactory());

		$command = new CommandEvent(new SaveParameters([]));
		$listener->onProcessLinksBefore($command);

		$this->assertInstanceOf(SaveParameters::class, $command->getCommand());
	}
}
