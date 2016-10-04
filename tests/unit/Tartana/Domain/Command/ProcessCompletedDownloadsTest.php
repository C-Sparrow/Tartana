<?php
namespace Tests\Unit\Tartana\Domain\Command;

use Tartana\Domain\Command\ProcessCompletedDownloads;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;

class ProcessCompletedDownloadsTest extends \PHPUnit_Framework_TestCase
{

	public function testProcessCompletedDownloadsCommand()
	{
		$repository = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$command = new ProcessCompletedDownloads($repository, [
				new Download()
		]);

		$this->assertEquals($repository, $command->getRepository());
		$this->assertNotEmpty($command->getDownloads());
	}
}
