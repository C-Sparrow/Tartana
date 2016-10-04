<?php
namespace Tests\Unit\Local\DependencyInjection;

use Local\DependencyInjection\LocalConfiguration;
use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;

class LocalConfigurationTest extends \PHPUnit_Framework_TestCase
{
	use ConfigurationTestCaseTrait;

	public function testEnabledValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid([
				[]
		], 'enabled');
	}

	public function testDownloadsValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid([
				[
						'enabled' => true
				]
		], 'downloads');
	}

	public function testProcessedValueContainsRequiredValue()
	{
		$this->assertProcessedConfigurationEquals([
				[
						'enabled' => true,
						'downloads' => '/path/to/dir'
				]
		], [
				'enabled' => true,
				'downloads' => '/path/to/dir'
		]);
	}

	protected function getConfiguration()
	{
		return new LocalConfiguration();
	}
}
