<?php
namespace Tests\Unit\Tartana\Host;

use Tartana\Host\Common\Http;
use Tartana\Host\Common\Https;
use Tartana\Host\HostFactory;
use Tartana\Host\HostInterface;
use Tartana\Host\Localhost;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class HostFactoryTest extends TartanaBaseTestCase
{

	public function testLocalHost()
	{
		$factory    = new HostFactory();
		$downloader = $factory->createHostDownloader('file://localhost/var');

		$this->assertInstanceOf(HostInterface::class, $downloader);
		$this->assertInstanceOf(Localhost::class, $downloader);
	}

	public function testInvalidHost()
	{
		$factory    = new HostFactory();
		$downloader = $factory->createHostDownloader('file:///invalidhost/var');

		$this->assertEmpty($downloader);
	}

	public function testInvalidUrl()
	{
		$factory    = new HostFactory();
		$downloader = $factory->createHostDownloader('d://///invalidhost.://jkgasd');

		$this->assertEmpty($downloader);
	}

	public function testHttpHost()
	{
		$factory    = new HostFactory();
		$downloader = $factory->createHostDownloader('http://foo.bar/kladwe');

		$this->assertInstanceOf(HostInterface::class, $downloader);
		$this->assertInstanceOf(Http::class, $downloader);
	}

	public function testHttpsHost()
	{
		$factory    = new HostFactory();
		$downloader = $factory->createHostDownloader('https://foo.bar/kladwe');

		$this->assertInstanceOf(HostInterface::class, $downloader);
		$this->assertInstanceOf(Https::class, $downloader);
	}

	public function testNoHttpHost()
	{
		$factory    = new HostFactory();
		$downloader = $factory->createHostDownloader('webcal://foo.bar/kladwe');

		$this->assertEmpty($downloader);
	}

	public function testNotCorrectInterface()
	{
		$factory    = new HostFactory();
		$downloader = $factory->createHostDownloader('http://Hostfactorytestinvalidhost.bar/kladwe');

		$this->assertEmpty($downloader);
	}

	public function testCommandBusSet()
	{
		$commandBus = $this->getMockCommandBus();
		$factory    = new HostFactory();
		$factory->setCommandBus($commandBus);
		$downloader = $factory->createHostDownloader('http://foo.bar/kladwe');

		$this->assertNotEmpty($downloader);
		$this->assertEquals($commandBus, $downloader->getCommandBus());
	}
}
namespace Tartana\Host;

class Hostfactorytestinvalidhostbar
{
}
