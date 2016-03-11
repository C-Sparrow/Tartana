<?php
namespace Tests\Unit\Tartana\Console\Command\Extract;
use Joomla\Registry\Registry;
use Tartana\Console\Command\Extract\UnzipCommand;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Unit\Tartana\Console\Command\Extract\ExtractBaseTestCase;

class UnzipCommandTest extends ExtractBaseTestCase
{

	protected $archivesPath = 'zips';

	protected function createCommand (EventDispatcherInterface $dispatcher, Registry $config = null)
	{
		if ($config === null)
		{
			$config = new Registry();
		}
		return new UnzipCommand($dispatcher, $config);
	}
}
