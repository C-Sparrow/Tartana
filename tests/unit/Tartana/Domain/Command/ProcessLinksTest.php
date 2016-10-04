<?php
namespace Tests\Unit\Tartana\Domain\Command;

use Tartana\Domain\Command\ProcessLinks;

class ProcessLinksTest extends \PHPUnit_Framework_TestCase
{

	public function testProcessLinksCommand()
	{
		$command = new ProcessLinks([
				'http://foo.bar/sdf'
		]);

		$this->assertCount(1, $command->getLinks());
		$this->assertEquals('http://foo.bar/sdf', $command->getLinks()[0]);
	}
}
