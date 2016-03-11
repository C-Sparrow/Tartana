<?php
namespace Tests\Unit\Tartana\Event;
use League\Flysystem\Adapter\NullAdapter;
use Tartana\Event\ExtractProgressEvent;

class ExtractProgressEventTest extends \PHPUnit_Framework_TestCase
{

	public function testExtractProgressEventValidProgress ()
	{
		$src = new NullAdapter();
		$dst = new NullAdapter();
		$event = new ExtractProgressEvent($src, $dst, 'test.txt', 10);

		$this->assertEquals($src, $event->getSource());
		$this->assertEquals($dst, $event->getDestination());
		$this->assertEquals('test.txt', $event->getFile());
		$this->assertEquals(10, $event->getProgress());
	}

	public function testExtractProgressEventString ()
	{
		$src = new NullAdapter();
		$dst = new NullAdapter();
		$event = new ExtractProgressEvent($src, $dst, 'test.txt', 'unit');

		$this->assertEquals(0, $event->getProgress());
	}

	public function testExtractProgressEventTooLarge ()
	{
		$src = new NullAdapter();
		$dst = new NullAdapter();
		$event = new ExtractProgressEvent($src, $dst, 'test.txt', 200);

		$this->assertEquals(100, $event->getProgress());
	}

	public function testExtractProgressEventTooLow ()
	{
		$src = new NullAdapter();
		$dst = new NullAdapter();
		$event = new ExtractProgressEvent($src, $dst, 'test.txt', - 2);

		$this->assertEquals(0, $event->getProgress());
	}
}