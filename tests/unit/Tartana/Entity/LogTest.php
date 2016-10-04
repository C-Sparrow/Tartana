<?php
namespace Tests\Unit\Tartana\Domain\Command;

use Monolog\Logger;
use Tartana\Entity\Log;

class LogTest extends \PHPUnit_Framework_TestCase
{

	public function testCreateLog()
	{
		$date = new \DateTime();
		$entity = new Log('unit', 'unit test message', $date, Logger::EMERGENCY, 'unit test context', 'extra content');

		$this->assertEquals('unit', $entity->getChannel());
		$this->assertEquals('unit test message', $entity->getMessage());
		$this->assertEquals($date, $entity->getDate());
		$this->assertEquals(Logger::EMERGENCY, $entity->getLevel());
		$this->assertEquals('unit test context', $entity->getContext());
		$this->assertEquals('extra content', $entity->getExtra());
	}
}
