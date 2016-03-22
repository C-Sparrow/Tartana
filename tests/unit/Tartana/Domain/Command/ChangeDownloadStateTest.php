<?php
namespace Tests\Unit\Tartana\Domain\Command;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Entity\Download;

class ChangeDownloadStateTest extends \PHPUnit_Framework_TestCase
{

	public function testChangeDownloadStateCommand ()
	{
		$download = new Download();
		$download->setId(2);
		$command = new ChangeDownloadState([
				$download
		], Download::STATE_DOWNLOADING_ERROR, Download::STATE_DOWNLOADING_STARTED);

		$this->assertEquals($download->getId(), $command->getDownloads()[0]->getId());
		$this->assertEquals(Download::STATE_DOWNLOADING_ERROR, $command->getFromState());
		$this->assertEquals(Download::STATE_DOWNLOADING_STARTED, $command->getToState());
	}
}