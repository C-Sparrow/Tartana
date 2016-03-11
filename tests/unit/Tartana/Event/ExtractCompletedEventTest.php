<?php
namespace Tests\Unit\Tartana\Event;
use League\Flysystem\Adapter\NullAdapter;
use Tartana\Event\ExtractCompletedEvent;

class ExtractCompletedEventTest extends \PHPUnit_Framework_TestCase
{

	public function testExtractCompletedEventTestSuccess ()
	{
		$src = new NullAdapter();
		$dst = new NullAdapter();
		$event = new ExtractCompletedEvent($src, $dst, true);

		$this->assertEquals($src, $event->getSource());
		$this->assertEquals($dst, $event->getDestination());
		$this->assertTrue($event->isSuccess());
	}

	public function testExtractCompletedEventTestFailed ()
	{
		$src = new NullAdapter();
		$dst = new NullAdapter();
		$event = new ExtractCompletedEvent($src, $dst, false);

		$this->assertEquals($src, $event->getSource());
		$this->assertEquals($dst, $event->getDestination());
		$this->assertFalse($event->isSuccess());
	}
}