<?php
namespace Test\Unit\Tartana;
use Tartana\Util;

class UtilTest extends \PHPUnit_Framework_TestCase
{

	public function testPidRunning ()
	{
		$this->assertTrue(Util::isPidRunning(getmypid()));
		$this->assertFalse(Util::isPidRunning(851351385135123));
	}

	public function testReadableSize ()
	{
		$this->assertEquals('1 kB', Util::readableSize(1024));
		$this->assertEquals('1 MB', Util::readableSize(1024 * 1024));
		$this->assertEquals('1 units kbytes', Util::readableSize(1024, [
				'units bytes',
				'units kbytes'
		]));
		$this->assertEquals('1', Util::readableSize(1024, []));
	}

	public function testRealPath ()
	{
		// Check absolute path
		$this->assertEquals(__DIR__, Util::realPath(__DIR__));

		// Check relative path
		$this->assertEquals(TARTANA_PATH_ROOT . '/app', Util::realPath('app'));

		// Check invalid path
		$this->assertNull(Util::realPath(__DIR__ . '/invalid'));
		$this->assertNull(Util::realPath('invalid'));

		// Check empty pathes
		$this->assertNull(Util::realPath(''));
		$this->assertNull(Util::realPath(null));
		$this->assertNull(Util::realPath(0));
	}

	public function testClone ()
	{
		$obj = new \stdClass();
		$obj->test = 'unit';

		$clone = Util::cloneObjects([
				$obj
		]);
		$obj->test = 'unit edited';

		$this->assertEquals('unit', $clone[0]->test);
	}

	public function testStartsWith ()
	{
		$this->assertTrue(Util::startsWith('test', 'test'));
		$this->assertTrue(Util::startsWith('test hello', 'test'));
		$this->assertTrue(Util::startsWith('test hello no', ''));
		$this->assertFalse(Util::startsWith(' test hello no', 'test'));
		$this->assertFalse(Util::startsWith(' test hello ', 'test'));
		$this->assertFalse(Util::startsWith('te', 'test'));
		$this->assertFalse(Util::startsWith('', 'test'));
	}

	public function testEndsWith ()
	{
		$this->assertTrue(Util::endsWith('test', 'test'));
		$this->assertTrue(Util::endsWith(' hello test', 'test'));
		$this->assertTrue(Util::endsWith('hello test no', ''));
		$this->assertFalse(Util::endsWith(' hello test no', 'test'));
		$this->assertFalse(Util::endsWith(' hello test ', 'test'));
		$this->assertFalse(Util::endsWith('te', 'test'));
		$this->assertFalse(Util::endsWith('', 'test'));
	}

	public function testShorten ()
	{
		$text = 'unit with so many charachters test';
		$this->assertEquals('unit...test', Util::shorten($text, 8));
		$this->assertEquals($text, Util::shorten($text, strlen($text)));
		$this->assertEquals($text, Util::shorten($text, strlen($text) + 1));
		$this->assertEquals('uni...est', Util::shorten($text, 7));
	}
}