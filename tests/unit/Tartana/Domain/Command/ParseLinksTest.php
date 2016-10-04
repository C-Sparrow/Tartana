<?php
namespace Tests\Unit\Tartana\Domain\Command;

use League\Flysystem\Adapter\NullAdapter;
use Tartana\Domain\Command\ParseLinks;

class ParseLinksTest extends \PHPUnit_Framework_TestCase
{

	public function testParseLinksCommand()
	{
		$fs = new NullAdapter();
		$command = new ParseLinks($fs, 'hello.txt');

		$this->assertEquals($fs, $command->getFolder());
		$this->assertEquals('hello.txt', $command->getPath());
	}
}
