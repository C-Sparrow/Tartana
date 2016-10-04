<?php
namespace Tests\Functional\Tartana\Console\Command\Extract;

use Joomla\Registry\Registry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Console\Command\Extract\SevenzCommand;

class SevenzCommandTest extends ExtractBaseTestCase
{

	protected $archivesPath = '7z';

	protected function createCommand(EventDispatcherInterface $dispatcher, Runner $runner, Registry $config = null)
	{
		if ($config === null) {
			$config = new Registry();
		}
		return new SevenzCommand($dispatcher, $runner, $config);
	}
}
