<?php
namespace Tests\Unit\Tartana\Domain\Command;
use Tartana\Domain\Command\SaveDownloads;
use Tartana\Entity\Download;

class SaveDownloadsTest extends \PHPUnit_Framework_TestCase
{

	public function testSaveDownloadsCommand ()
	{
		$downloads = [
				new Download()
		];
		$command = new SaveDownloads($downloads);

		$this->assertEquals($downloads, $command->getDownloads());
	}
}