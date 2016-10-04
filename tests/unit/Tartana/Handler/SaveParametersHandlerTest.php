<?php
namespace Test\Unit\Tartana\Handler;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Domain\Command\SaveParameters;
use Tartana\Handler\SaveParametersHandler;
use Symfony\Component\Yaml\Yaml;

class SaveParametersHandlerTest extends \PHPUnit_Framework_TestCase
{

	const PARAMETER_FILE = 'test.yml';

	public function testHandleSetParameter()
	{
		$fs = new Local(__DIR__ . '/test');

		$data = [
				'parameters' => [
						'unit' => 'test'
				]
		];

		$fs->write(self::PARAMETER_FILE, Yaml::dump($data), new Config());

		$data['parameters']['unit'] = 'changed';
		$handler = new SaveParametersHandler($fs->applyPathPrefix(self::PARAMETER_FILE));
		$handler->handle(new SaveParameters($data['parameters']));

		$this->assertTrue($fs->has(self::PARAMETER_FILE));

		$savedParameters = Yaml::parse($fs->read(self::PARAMETER_FILE)['contents']);
		$this->assertArrayHasKey('parameters', $savedParameters);
		$this->assertEquals($data, $savedParameters);
	}

	public function testHandleSetParameterOnInvalidFile()
	{
		$fs = new Local(__DIR__ . '/test');

		$data = [
				'unit' => 'test'
		];

		$fs->write(self::PARAMETER_FILE, Yaml::dump($data), new Config());

		$handler = new SaveParametersHandler($fs->applyPathPrefix(self::PARAMETER_FILE));
		$handler->handle(new SaveParameters([
				'parameters' => [
						'unit' => 'changed'
				]
		]));

		$this->assertTrue($fs->has(self::PARAMETER_FILE));

		$savedParameters = Yaml::parse($fs->read(self::PARAMETER_FILE)['contents']);
		$this->assertArrayNotHasKey('parameters', $savedParameters);
		$this->assertEquals($data, $savedParameters);
	}

	public function testHandleSetParameterNoFile()
	{
		$fs = new Local(__DIR__ . '/test');

		$handler = new SaveParametersHandler($fs->applyPathPrefix(self::PARAMETER_FILE));
		$handler->handle(new SaveParameters([
				'unit' => 'test'
		]));

		$this->assertFalse($fs->has(self::PARAMETER_FILE));
	}

	protected function setUp()
	{
		$fs = new Local(__DIR__);
		if ($fs->has('test')) {
			$fs->deleteDir('test');
		}
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		if ($fs->has('test')) {
			$fs->deleteDir('test');
		}

		$fs = new Local(TARTANA_PATH_ROOT);
		$fs->write('var/cache/.gitkeep', '', new Config());
	}
}
