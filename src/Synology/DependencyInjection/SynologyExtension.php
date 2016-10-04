<?php
namespace Synology\DependencyInjection;

use Tartana\DependencyInjection\TartanaExtension;

class SynologyExtension extends TartanaExtension
{

	protected function getExtensionConfiguration()
	{
		return new SynologyConfiguration();
	}
}
