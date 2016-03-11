<?php
namespace Tests\Unit\Tartana\Domain\Command;
use Tartana\Domain\Command\SaveParameters;

class SaveParametersTest extends \PHPUnit_Framework_TestCase
{

	public function testGetParameters ()
	{
		$command = new SaveParameters([
				'unit' => 'test'
		]);

		$this->assertEquals([
				'unit' => 'test'
		], $command->getParameters());
	}

	public function testGetParametersEmpty ()
	{
		$command = new SaveParameters([]);

		$this->assertEmpty($command->getParameters());
	}
}