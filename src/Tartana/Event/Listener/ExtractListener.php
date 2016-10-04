<?php
namespace Tartana\Event\Listener;

use Tartana\Component\Command\Command;
use Tartana\Util;

class ExtractListener extends AbstractProcessingListener
{

	protected function getConfigurationKey()
	{
		return 'extract.destination';
	}

	protected function getFileExtensionsForCommand()
	{
		return [
				'rar' => 'unrar',
				'zip' => 'unzip',
				'7z' => '7z'
		];
	}

	protected function prepareCommand(Command $command)
	{
		$command->addArgument(Util::realPath($this->configuration->get('extract.passwordFile')));

		return $command;
	}
}
