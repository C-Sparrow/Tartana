<?php
namespace Tests\Unit\Tartana\Domain\Command;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;

class ChangeDownloadStateTest extends \PHPUnit_Framework_TestCase
{

	public function testChangeDownloadStateCommand ()
	{
		$repository = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$command = new ChangeDownloadState($repository, Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_STARTED);

		$this->assertEquals($repository, $command->getRepository());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $command->getFromState());
		$this->assertEquals(Download::STATE_DOWNLOADING_STARTED, $command->getToState());
	}
}