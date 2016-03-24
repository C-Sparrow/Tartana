<?php
namespace Tests\Unit\Tartana\Console\Command\Extract;
use Joomla\Registry\Registry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tartana\Component\Command\Command;
use Tartana\Console\Command\Extract\SevenzCommand;
use Tests\Unit\Tartana\Console\Command\Extract\ExtractBaseTestCase;

class SevenzCommandTest extends ExtractBaseTestCase
{

	protected $archivesPath = 'zips';

	protected function createCommand (EventDispatcherInterface $dispatcher, Registry $config = null)
	{
		if ($config === null)
		{
			$config = new Registry();
		}
		return new SevenzCommand($dispatcher, $config);
	}
}
