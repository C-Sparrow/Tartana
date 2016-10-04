<?php
namespace Tests\Functional\Tartana\Console\Command\Extract;

use Joomla\Registry\Registry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tartana\Component\Command\Runner;
use Tartana\Console\Command\Extract\UnrarCommand;

class UnrarCommandTest extends ExtractBaseTestCase
{

	protected $archivesPath = 'rars';

	protected function createCommand(EventDispatcherInterface $dispatcher, Runner $runner, Registry $config = null)
	{
		if ($config === null) {
			$config = new Registry();
		}
		return new UnrarCommand($dispatcher, $runner, $config);
	}
}
