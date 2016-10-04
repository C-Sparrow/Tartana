<?php
namespace Local\DependencyInjection;

use Tartana\DependencyInjection\TartanaExtension;

class LocalExtension extends TartanaExtension
{

	protected function getExtensionConfiguration()
	{
		return new LocalConfiguration();
	}
}
