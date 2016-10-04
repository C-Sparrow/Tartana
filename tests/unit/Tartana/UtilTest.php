<?php
namespace Test\Unit\Tartana;

use Tartana\Util;

class UtilTest extends \PHPUnit_Framework_TestCase
{

	public function testPidRunning()
	{
		$this->assertTrue(Util::isPidRunning(getmypid()));
		$this->assertFalse(Util::isPidRunning(851351385135123));
	}

	public function testReadableSize()
	{
		$this->assertEquals('1 kB', Util::readableSize(1024));
		$this->assertEquals('1 MB', Util::readableSize(1024 * 1024));
		$this->assertEquals('1 units kbytes', Util::readableSize(1024, [
				'units bytes',
				'units kbytes'
		]));
		$this->assertEquals('1', Util::readableSize(1024, []));
	}

	public function testRealPath()
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

	public function testClone()
	{
		$obj = new \stdClass();
		$obj->test = 'unit';

		$clone = Util::cloneObjects([
				$obj
		]);
		$obj->test = 'unit edited';

		$this->assertEquals('unit', $clone[0]->test);
	}

	public function testStartsWith()
	{
		$this->assertTrue(Util::startsWith('test', 'test'));
		$this->assertTrue(Util::startsWith('test hello', 'test'));
		$this->assertTrue(Util::startsWith('test hello no', ''));
		$this->assertFalse(Util::startsWith(' test hello no', 'test'));
		$this->assertFalse(Util::startsWith(' test hello ', 'test'));
		$this->assertFalse(Util::startsWith('te', 'test'));
		$this->assertFalse(Util::startsWith('', 'test'));
	}

	public function testEndsWith()
	{
		$this->assertTrue(Util::endsWith('test', 'test'));
		$this->assertTrue(Util::endsWith(' hello test', 'test'));
		$this->assertTrue(Util::endsWith('hello test no', ''));
		$this->assertFalse(Util::endsWith(' hello test no', 'test'));
		$this->assertFalse(Util::endsWith(' hello test ', 'test'));
		$this->assertFalse(Util::endsWith('te', 'test'));
		$this->assertFalse(Util::endsWith('', 'test'));
	}

	public function testShorten()
	{
		$text = 'unit with so many charachters test';
		$this->assertEquals('unit...test', Util::shorten($text, 8));
		$this->assertEquals($text, Util::shorten($text, strlen($text)));
		$this->assertEquals($text, Util::shorten($text, strlen($text) + 1));
		$this->assertEquals('uni...est', Util::shorten($text, 7));
	}

	public function testParseUrl()
	{
		$uri = Util::parseUrl('http://user:pass@mirrors.kernel.org:8000/link/test.html?hello=foo#bar');

		$this->assertEquals('http', $uri['scheme']);
		$this->assertEquals('user', $uri['user']);
		$this->assertEquals('pass', $uri['pass']);
		$this->assertEquals('mirrors.kernel.org', $uri['host']);
		$this->assertEquals('mirrors', $uri['subdomain']);
		$this->assertEquals('kernel.org', $uri['registerableDomain']);
		$this->assertEquals('org', $uri['publicSuffix']);
		$this->assertEquals('8000', $uri['port']);
		$this->assertEquals('/link/test.html', $uri['path']);
		$this->assertEquals('hello=foo', $uri['query']);
		$this->assertEquals('bar', $uri['fragment']);
	}

	public function testParseInvalidUrl()
	{
		$uri = Util::parseUrl('://invalid://');

		$this->assertEquals('', $uri['scheme']);
		$this->assertEquals('', $uri['user']);
		$this->assertEquals('', $uri['pass']);
		$this->assertEquals('', $uri['host']);
		$this->assertEquals('', $uri['subdomain']);
		$this->assertEquals('', $uri['registerableDomain']);
		$this->assertEquals('', $uri['publicSuffix']);
		$this->assertEquals('', $uri['port']);
		$this->assertEquals('', $uri['path']);
		$this->assertEquals('', $uri['query']);
		$this->assertEquals('', $uri['fragment']);
	}

	public function testCleanHost()
	{
		$this->assertEquals('foobar', Util::cleanHostName('foo.bar'));
		$this->assertEquals('foobar', Util::cleanHostName(':foo-.bar'));
		$this->assertEquals('foobar', Util::cleanHostName(Util::parseUrl('http://foo.bar')));
		$this->assertEquals('', Util::cleanHostName([]));
	}
}
