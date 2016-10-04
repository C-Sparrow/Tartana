<?php
namespace Tests\Unit\Tartana\Mixins;

use SimpleBus\Message\Bus\MessageBus;

class CommandBusAwareTraitTest extends \PHPUnit_Framework_TestCase
{

	public function testHandleCommandNoCommandBus()
	{
		$trait = $this->getObjectForTrait('Tartana\Mixins\CommandBusAwareTrait');

		$trait->handleCommand(new \stdClass());

		$this->assertEmpty($trait->getCommandBus());
	}

	public function testHandleCommandSetNullCommandBus()
	{
		$trait = $this->getObjectForTrait('Tartana\Mixins\CommandBusAwareTrait');

		$trait->setCommandBus(null);
		$trait->handleCommand(new \stdClass());

		$this->assertEmpty($trait->getCommandBus());
	}

	public function testHandleCommandWithCommandBus()
	{
		$command = new \stdClass();
		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();
		$commandBus->expects($this->once())
			->method('handle')
			->with($this->equalTo($command));
		$trait = $this->getObjectForTrait('Tartana\Mixins\CommandBusAwareTrait');
		$trait->setCommandBus($commandBus);
		$trait->handleCommand($command);

		$this->assertEquals($commandBus, $trait->getCommandBus());
	}
}
