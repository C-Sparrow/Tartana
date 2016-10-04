<?php
namespace Tests\Unit\Tartana\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use Tartana\DependencyInjection\TartanaConfiguration;

class TartanaConfigurationTest extends \PHPUnit_Framework_TestCase
{
	use ConfigurationTestCaseTrait;

	public function testLinksValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid([
				[]
		], 'links');
	}

	public function testLinksFolderValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid([
				[
						'links' => []
				]
		], 'folder');
	}

	public function testLinksValueIsProvided()
	{
		$this->assertConfigurationIsValid([
				[
						'links' => [
								'folder' => '/path/to/dir'
						]
				]
		], 'links');
	}

	public function testExtractValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid([
				[
						'links' => [
								'folder' => '/path/to/dir'
						]
				]
		], 'extract');
	}

	public function testExtractDestinationValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid([
				[
						'links' => [
								'folder' => '/path/to/dir'
						],
						'extract' => []
				]
		], 'destination');
	}

	public function testExtractValueIsProvided()
	{
		$this->assertConfigurationIsValid([
				[
						'extract' => [
								'destination' => '/path/to/dir'
						]
				]
		], 'extract');
	}

	public function testSoundValueIsProvided()
	{
		$this->assertConfigurationIsValid([
				[
						'sound' => [
								'destination' => '/path/to/dir'
						]
				]
		], 'sound');
	}

	public function testSoundValueIsNotProvided()
	{
		$this->assertConfigurationIsInvalid(
			[
						[
								'links' => [
										'folder' => '/path/to/dir'
								],
								'extract' => [
										'destination' => '/path/to/dir'
								]
						]
			],
			'sound'
		);
	}

	public function testProcessedValueContainsRequiredValue()
	{
		$this->assertProcessedConfigurationEquals(
			[
						[
								'links' => [
										'folder' => '/path/to/dir'
								],
								'extract' => [
										'destination' => '/path/to/dir'
								],
								'sound' => [
										'destination' => '/path/to/dir'
								]
						]
				],
			[
						'links' => [
								'folder' => '/path/to/dir'
						],
						'extract' => [
								'destination' => '/path/to/dir'
						],
						'sound' => [
								'destination' => '/path/to/dir'
						]
			]
		);
	}

	protected function getConfiguration()
	{
		return new TartanaConfiguration();
	}
}
