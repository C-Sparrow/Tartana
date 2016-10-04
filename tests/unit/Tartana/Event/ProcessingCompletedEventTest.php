<?php
namespace Tests\Unit\Tartana\Event;

use League\Flysystem\Adapter\NullAdapter;
use Tartana\Event\ProcessingCompletedEvent;

class ProcessingCompletedEventTest extends \PHPUnit_Framework_TestCase
{

	public function testProcessingCompletedEventTestSuccess()
	{
		$src = new NullAdapter();
		$dst = new NullAdapter();
		$event = new ProcessingCompletedEvent($src, $dst, true);

		$this->assertEquals($src, $event->getSource());
		$this->assertEquals($dst, $event->getDestination());
		$this->assertTrue($event->isSuccess());
	}

	public function testProcessingCompletedEventTestFailed()
	{
		$src = new NullAdapter();
		$dst = new NullAdapter();
		$event = new ProcessingCompletedEvent($src, $dst, false);

		$this->assertEquals($src, $event->getSource());
		$this->assertEquals($dst, $event->getDestination());
		$this->assertFalse($event->isSuccess());
	}
}
