<?php
namespace Tests\Unit\Tartana\Domain\Command;
use Tartana\Domain\Command\DeleteDownloads;
use Tartana\Entity\Download;

class DeleteDownloadsTest extends \PHPUnit_Framework_TestCase
{

	public function testDeleteDownloadsCommand ()
	{
		$downloads = [
				new Download()
		];
		$command = new DeleteDownloads($downloads);

		$this->assertEquals($downloads, $command->getDownloads());
	}
}