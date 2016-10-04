<?php
namespace Tests\Unit\Tartana\Handler;

use Tartana\Domain\Command\ProcessLinks;
use Tartana\Middleware\MessageBusEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tartana\Event\CommandEvent;

class MessageBusEventDispatcherTest extends \PHPUnit_Framework_TestCase
{

	public function testTestEventsFired()
	{
		$command = new ProcessLinks([]);
		$dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();
		$dispatcher->expects($this->exactly(2))
			->method('dispatch')
			->withConsecutive(
				[
						$this->equalTo('commandbus.command.before'),
						$this->callback(function (CommandEvent $event) use ($command) {
							return $event->getCommand() == $command;
						})
				],
				[
						$this->equalTo('commandbus.command.after'),
						$this->callback(function (CommandEvent $event) use ($command) {
							return $event->getCommand() == $command;
						})
				]
			);
				$busDispatcher = new MessageBusEventDispatcher($dispatcher);
				$busDispatcher->handle($command, function () {
				});
	}
}
