<?php
namespace Tests\Unit\Tartana\Event;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Event\DownloadsCompletedEvent;

class DownloadsCompletedEventTest extends \PHPUnit_Framework_TestCase
{

	public function testDownloadsCompletedEvent ()
	{
		$repository = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$event = new DownloadsCompletedEvent($repository, [
				new Download()
		]);

		$this->assertEquals($repository, $event->getRepository());
		$this->assertNotEmpty($event->getDownloads());
	}
}