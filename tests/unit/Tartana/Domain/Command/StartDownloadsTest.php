<?php
namespace Tests\Unit\Tartana\Domain\Command;
use Tartana\Domain\Command\StartDownloads;
use Tartana\Domain\DownloadRepository;

class StartDownloadsTest extends \PHPUnit_Framework_TestCase
{

	public function testStartDownloadsCommand ()
	{
		$repository = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$command = new StartDownloads($repository);

		$this->assertEquals($repository, $command->getRepository());
	}
}