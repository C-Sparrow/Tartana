<?php
namespace Tests\Unit\Tartana\Event;

use League\Flysystem\Adapter\NullAdapter;
use Tartana\Event\ProcessingProgressEvent;

class ProcessingProgressEventTest extends \PHPUnit_Framework_TestCase
{

	public function testProcessingProgressEventValidProgress()
	{
		$src   = new NullAdapter();
		$dst   = new NullAdapter();
		$event = new ProcessingProgressEvent($src, $dst, 'test.txt', 10);

		$this->assertEquals($src, $event->getSource());
		$this->assertEquals($dst, $event->getDestination());
		$this->assertEquals('test.txt', $event->getFile());
		$this->assertEquals(10, $event->getProgress());
	}

	public function testProcessingProgressEventString()
	{
		$src   = new NullAdapter();
		$dst   = new NullAdapter();
		$event = new ProcessingProgressEvent($src, $dst, 'test.txt', 'unit');

		$this->assertEquals(0, $event->getProgress());
	}

	public function testProcessingProgressEventTooLarge()
	{
		$src   = new NullAdapter();
		$dst   = new NullAdapter();
		$event = new ProcessingProgressEvent($src, $dst, 'test.txt', 200);

		$this->assertEquals(100, $event->getProgress());
	}

	public function testProcessingProgressEventTooLow()
	{
		$src   = new NullAdapter();
		$dst   = new NullAdapter();
		$event = new ProcessingProgressEvent($src, $dst, 'test.txt', -2);

		$this->assertEquals(0, $event->getProgress());
	}
}
