<?php
namespace Tests\Unit\Tartana\Event;
use Tartana\Event\CommandEvent;

class CommandEventTest extends \PHPUnit_Framework_TestCase
{

	public function testDownloadsCompletedEvent ()
	{
		$command = new \stdClass();
		$event = new CommandEvent($command);

		$this->assertEquals($command, $event->getCommand());
	}
}