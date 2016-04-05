<?php
namespace Tartana\Event\Listener;

class SoundConverterListener extends AbstractProcessingListener
{

	protected function getConfigurationKey ()
	{
		return 'sound.destination';
	}

	protected function getFileExtensionsForCommand ()
	{
		return [
				'mp4' => 'convert:sound'
		];
	}
}
