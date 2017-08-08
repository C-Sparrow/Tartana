<?php
namespace Tests\Unit\Synology\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use Synology\DependencyInjection\SynologyConfiguration;

class SynologyConfigurationTest extends \PHPUnit_Framework_TestCase
{
	use ConfigurationTestCaseTrait;

	public function testEnabledValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid([
			[]
		], 'enabled');
	}

	public function testAddressValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid([
			[
				'enabled' => true
			]
		], 'address');
	}

	public function testUsernameValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid([
			[
				'enabled' => true,
				'address' => 'http://localhost:5001'
			]
		], 'username');
	}

	public function testPasswordValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid(
			[
				[
					'enabled' => true,
					'address' => 'http://localhost:5001',
					'username' => 'admin'
				]
			],
			'password'
		);
	}

	public function testDownloadsValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid(
			[
				[
					'enabled' => true,
					'address' => 'http://localhost:5001',
					'username' => 'admin',
					'password' => 'admin'
				]
			],
			'downloads'
		);
	}

	public function testDownloadShareValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid(
			[
				[
					'enabled' => true,
					'address' => 'http://localhost:5001',
					'username' => 'admin',
					'password' => 'admin',
					'downloads' => '/path/to/dir'
				]
			],
			'downloadShare'
		);
	}

	public function testProcessedValueContainsRequiredValue()
	{
		$this->assertProcessedConfigurationEquals(
			[
				[
					'enabled' => true,
					'address' => 'http://localhost:5001',
					'username' => 'admin',
					'password' => 'admin',
					'downloads' => '/path/to/dir',
					'downloadShare' => 'to/share'
				]
			],
			[
				'enabled' => true,
				'address' => 'http://localhost:5001',
				'username' => 'admin',
				'password' => 'admin',
				'downloads' => '/path/to/dir',
				'downloadShare' => 'to/share'
			]
		);
	}

	protected function getConfiguration()
	{
		return new SynologyConfiguration();
	}
}
