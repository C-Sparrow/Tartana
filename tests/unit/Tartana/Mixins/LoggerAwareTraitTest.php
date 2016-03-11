<?php
namespace Tests\Unit\Tartana\Mixins;
use Monolog\Logger;
use Monolog\Handler\TestHandler;

class LoggerAwareTraitTest extends \PHPUnit_Framework_TestCase
{

	public function testLogNoLogger ()
	{
		$trait = $this->getObjectForTrait('Tartana\Mixins\LoggerAwareTrait');

		$trait->log('hello');

		$this->assertEmpty($trait->getLogger());
	}

	public function testLogSetNullLogger ()
	{
		$trait = $this->getObjectForTrait('Tartana\Mixins\LoggerAwareTrait');

		$trait->setLogger(null);
		$trait->log('hello');

		$this->assertEmpty($trait->getLogger());
	}

	public function testLogWithLoggerDefaultPriority ()
	{
		$trait = $this->getObjectForTrait('Tartana\Mixins\LoggerAwareTrait');

		$h = new TestHandler();
		$l = new Logger('test');
		$l->pushHandler($h);

		$trait->setLogger($l);
		$trait->log('test');

		$this->assertTrue($h->hasDebugRecords());
		$this->assertTrue($h->hasDebugThatContains('test'));
	}

	public function testLogWithLoggerEmergency ()
	{
		$trait = $this->getObjectForTrait('Tartana\Mixins\LoggerAwareTrait');

		$h = new TestHandler();
		$l = new Logger('test');
		$l->pushHandler($h);

		$trait->setLogger($l);
		$trait->log('test', Logger::EMERGENCY);

		$this->assertFalse($h->hasDebugRecords());
		$this->assertTrue($h->hasEmergencyRecords());
		$this->assertTrue($h->hasEmergencyThatContains('test'));
	}
}