<?php
namespace Tartana\Handler;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Tartana\Domain\Command\SaveParameters;
use Symfony\Component\Yaml\Yaml;

class SaveParametersHandler
{

	private $parameterFile = null;

	public function __construct($parameterFile)
	{
		$this->parameterFile = $parameterFile;
	}

	public function handle(SaveParameters $command)
	{
		if (! file_exists($this->parameterFile)) {
			return;
		}

		$fs = new Local(dirname($this->parameterFile));
		$originalParameters = Yaml::parse($fs->read($fs->removePathPrefix($this->parameterFile))['contents']);

		if (! key_exists('parameters', $originalParameters)) {
		// Not a valid parameters file
			return;
		}

		$originalParameters = $originalParameters['parameters'];
		foreach ($originalParameters as $key => $originalParameter) {
			if (key_exists($key, $command->getParameters())) {
				$originalParameters[$key] = $command->getParameters()[$key];
			}
		}

		$fs->write($fs->removePathPrefix($this->parameterFile), Yaml::dump([
				'parameters' => $originalParameters
		]), new Config());

		$fs = new Local(TARTANA_PATH_ROOT . '/var');
		$fs->deleteDir('cache');
		$fs->createDir('cache', new Config());
	}
}
