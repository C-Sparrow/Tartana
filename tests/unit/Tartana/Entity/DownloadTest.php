<?php
namespace Tests\Unit\Tartana\Domain\Command;

use Tartana\Entity\Download;

class DownloadTest extends \PHPUnit_Framework_TestCase
{

	public function testEmptyDownload()
	{
		$download = new Download();

		$this->assertEmpty($download->getId());
		$this->assertEmpty($download->getLink());
		$this->assertEmpty($download->getDestination());
		$this->assertEmpty($download->getFileName());
		$this->assertEmpty($download->getPid());
		$this->assertEmpty($download->getHash());
		$this->assertEquals(0.00, $download->getProgress());
		$this->assertEmpty($download->getSize());
		$this->assertEmpty($download->getMessage());
		$this->assertEmpty($download->getFinishedAt());
		$this->assertEmpty($download->getStartedAt());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
	}

	public function testSetGetId()
	{
		$download = new Download();
		$this->assertEquals($download, $download->setId(12));
		$this->assertEquals(12, $download->getId());
	}

	public function testSetGetLink()
	{
		$download = new Download();
		$this->assertEquals($download, $download->setLink('http://foo.bar/tege'));
		$this->assertEquals('http://foo.bar/tege', $download->getLink());
	}

	public function testSetGetDestination()
	{
		$download = new Download();
		$this->assertEquals($download, $download->setDestination(__DIR__));
		$this->assertEquals(__DIR__, $download->getDestination());
	}

	public function testSetGetMessage()
	{
		$download = new Download();
		$this->assertEquals($download, $download->setMessage('unit test'));
		$this->assertEquals('unit test', $download->getMessage());
	}

	public function testSetGetProgress()
	{
		$download = new Download();
		$this->assertEquals($download, $download->setProgress(2));
		$this->assertEquals(2.00, $download->getProgress());
		$this->assertEquals($download, $download->setProgress(2.12));
		$this->assertEquals(2.12, $download->getProgress());
	}

	public function testSetGetProgressReset()
	{
		$download = new Download();
		$this->assertEquals($download, $download->setProgress(20));
		$this->assertEquals(20.00, $download->getProgress());
		$this->assertEquals($download, $download->setProgress(10, true));
		$this->assertEquals(10, $download->getProgress());
	}

	public function testSetInvalidProgress()
	{
		$download = new Download();
		$this->assertEquals($download, $download->setProgress(0));
		$this->assertEquals($download, $download->setProgress(- 2));
		$this->assertEquals(0.00, $download->getProgress());

		$this->assertEquals($download, $download->setProgress(10, true));
		$this->assertEquals($download, $download->setProgress(1000));
		$this->assertEquals(100.00, $download->getProgress());

		$this->assertEquals($download, $download->setProgress(10, true));
		$this->assertEquals($download, $download->setProgress(2));
		$this->assertEquals(10, $download->getProgress());
	}

	public function testSetGetState()
	{
		$download = new Download();
		$this->assertEquals($download, $download->setState(Download::STATE_DOWNLOADING_ERROR));
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $download->getState());
	}

	public function testSetInvalidState()
	{
		$download = new Download();
		$oldState = $download->getState();
		$this->assertEquals($download, $download->setState(101010));
		$this->assertNotEquals(101010, $download->getState());
		$this->assertEquals($oldState, $download->getState());
	}

	public function testSetGetFileName()
	{
		$download = new Download();
		$this->assertEquals($download, $download->setFileName('unit-test.txt'));
		$this->assertEquals('unit-test.txt', $download->getFileName());
	}

	public function testSetGetPid()
	{
		$download = new Download();
		$this->assertEquals($download, $download->setPid(345));
		$this->assertEquals(345, $download->getPid());
	}

	public function testSetGetSize()
	{
		$download = new Download();
		$this->assertEquals($download, $download->setSize(345));
		$this->assertEquals(345, $download->getSize());
	}

	public function testSetGetHash()
	{
		$hash = md5(345);
		$download = new Download();
		$this->assertEquals($download, $download->setHash($hash));
		$this->assertEquals($hash, $download->getHash());
	}

	public function testReset()
	{
		$download = new Download();
		$download->setDestination(__DIR__);
		$download->setFileName('unit');
		$download->setFinishedAt(new \DateTime());
		$download->setStartedAt(new \DateTime());
		$download->setLink('http://foo.bar/sdfsf');
		$download->setMessage('unit');
		$download->setPid(123);
		$download->setProgress(23);
		$download->setSize(1234);
		$download->setHash(1234);
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);

		$download = Download::reset($download);

		$this->assertEquals('http://foo.bar/sdfsf', $download->getLink());
		$this->assertEquals(__DIR__, $download->getDestination());
		$this->assertEquals('unit', $download->getFileName());
		$this->assertEmpty($download->getPid());
		$this->assertEquals(0.00, $download->getProgress());
		$this->assertEquals(1234, $download->getSize());
		$this->assertEquals(1234, $download->getHash());
		$this->assertEmpty($download->getMessage());
		$this->assertEmpty($download->getFinishedAt());
		$this->assertEmpty($download->getStartedAt());
		$this->assertEquals(Download::STATE_DOWNLOADING_NOT_STARTED, $download->getState());
	}
}
