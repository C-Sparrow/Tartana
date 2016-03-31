<?php
namespace Tests\Unit\Tartana\Event;
use Tartana\Event\CommandEvent;

class CommandEventTest extends \PHPUnit_Framework_TestCase
{

	public function testGetCommandEvent ()
	{
		$command = new \stdClass();
		$event = new CommandEvent($command);

		$this->assertEquals($command, $event->getCommand());
	}

	public function testSetCommandEvent ()
	{
		$command = new \stdClass();
		$event = new CommandEvent($command);

		$command1 = new \stdClass();
		$command1->test = 'test';
		$event->setCommand($command1);

		$this->assertEquals($command1, $event->getCommand());
		$this->assertNotEquals($command, $event->getCommand());
	}
}