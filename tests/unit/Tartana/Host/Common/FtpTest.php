<?php
namespace Tests\Unit\Tartana\Host\Common;
use GuzzleHttp\Promise;
use Joomla\Registry\Registry;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use Tartana\Entity\Download;
use Tartana\Host\Common\Ftp;
use Tartana\Host\Common\Ftps;
use Tartana\Host\Localhost;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class FtpTest extends TartanaBaseTestCase
{

	private $scheme = 'ftp';

	public function testInstantiable ()
	{
		// Flysystem can't run FTP properly on HHVM
		// https://github.com/thephpleague/flysystem/blob/master/tests/FtpTests.php#L329
		if (! defined('FTP_BINARY'))
		{
			$this->markTestSkipped('The FTP_BINARY constant is not defined');
		}
	}

	/**
	 * @depends testInstantiable
	 */
	public function testHasFtpAdapter ()
	{
		$download = new Download();
		$download->setId(2);
		$download->setLink($this->scheme . '://localhost/path/test.txt');
		$download->setDestination(__DIR__);

		$manager = $this->getMockBuilder(MountManager::class)->getMock();
		$this->callWithConsecutive($manager->expects($this->exactly(2))
			->method('mountFilesystem'),
				[
						[
								$this->anything(),
								$this->callback(
										function  (Filesystem $f) {
											return $f->getAdapter() instanceof \League\Flysystem\Adapter\Ftp;
										})
						]
				]);

		$downloader = $this->getFtp($manager);
		Promise\unwrap($downloader->download([
				$download
		]));
	}

	/**
	 * @depends testInstantiable
	 */
	public function testCredentialsInUrl ()
	{
		$download = new Download();
		$download->setId(2);
		$download->setLink($this->scheme . '://foo:bar@localhost/path/test.txt');
		$download->setDestination(__DIR__);

		$manager = $this->getMockBuilder(MountManager::class)->getMock();
		$this->callWithConsecutive($manager->expects($this->exactly(2))
			->method('mountFilesystem'),
				[
						[
								$this->anything(),
								$this->callback(
										function  (Filesystem $f) {
											$a = $f->getAdapter();
											return $a->getUsername() == 'foo' && $a->getPassword() == 'bar';
										})
						]
				]);

		$downloader = $this->getFtp($manager);
		Promise\unwrap($downloader->download([
				$download
		]));
	}

	/**
	 * @depends testInstantiable
	 */
	public function testCredentialsInConfiguration ()
	{
		$download = new Download();
		$download->setId(2);
		$download->setLink($this->scheme . '://pingpong.com/path/test.txt');
		$download->setDestination(__DIR__);

		$manager = $this->getMockBuilder(MountManager::class)->getMock();
		$this->callWithConsecutive($manager->expects($this->exactly(2))
			->method('mountFilesystem'),
				[
						[
								$this->anything(),
								$this->callback(
										function  (Filesystem $f) {
											$a = $f->getAdapter();
											return $a->getUsername() == 'foo2' && $a->getPassword() == 'bar2';
										})
						]
				]);

		$downloader = $this->getFtp($manager,
				new Registry(
						[
								'ftp' => [
										'localhost' => [
												'username' => 'foo',
												'password' => 'bar'
										],
										'pingpong.com' => [
												'username' => 'foo1',
												'password' => 'bar1'
										],
										'pingpongcom' => [
												'username' => 'foo2',
												'password' => 'bar2'
										]
								]
						]));
		Promise\unwrap($downloader->download([
				$download
		]));
	}

	/**
	 * @depends testInstantiable
	 */
	public function testCredentialsInConfigurationSubDomain ()
	{
		$download = new Download();
		$download->setId(2);
		$download->setLink($this->scheme . '://sub.pingpong.com/path/test.txt');
		$download->setDestination(__DIR__);

		$manager = $this->getMockBuilder(MountManager::class)->getMock();
		$this->callWithConsecutive($manager->expects($this->exactly(2))
			->method('mountFilesystem'),
				[
						[
								$this->anything(),
								$this->callback(
										function  (Filesystem $f) {
											$a = $f->getAdapter();
											return $a->getUsername() == 'foo2' && $a->getPassword() == 'bar2';
										})
						]
				]);

		$downloader = $this->getFtp($manager,
				new Registry(
						[
								'ftp' => [
										'pingpongcom' => [
												'username' => 'foo',
												'password' => 'bar'
										],
										'subpingpongcom' => [
												'username' => 'foo2',
												'password' => 'bar2'
										]
								]
						]));
		Promise\unwrap($downloader->download([
				$download
		]));
	}

	/**
	 * @depends testInstantiable
	 */
	public function testNotHasFtpAdapter ()
	{
		$download = new Download();
		$download->setId(2);
		$download->setLink($this->scheme . ':///path/test.txt');
		$download->setDestination(__DIR__);

		$manager = $this->getMockBuilder(MountManager::class)->getMock();
		$manager->expects($this->never())
			->method('mountFilesystem');

		$downloader = $this->getFtp($manager);
		Promise\unwrap($downloader->download([
				$download
		]));
	}

	/**
	 *
	 * @return Ftp|Ftps
	 */
	private function getFtp (MountManager $manager, Registry $config = null)
	{
		if (! $config)
		{
			$config = new Registry();
		}
		$class = '\\Tartana\\Host\\Common\\' . ucfirst($this->scheme);
		$downloader = new $class($config, $manager);
		return $downloader;
	}
}