<?php
namespace Tests\Unit\Tartana\Console\Command\Extract;
use Joomla\Registry\Registry;
use Tartana\Console\Command\Extract\UnrarCommand;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tests\Unit\Tartana\Console\Command\Extract\ExtractBaseTestCase;

class UnrarCommandTest extends ExtractBaseTestCase
{

	protected $archivesPath = 'rars';

	protected function createCommand (EventDispatcherInterface $dispatcher, Registry $config = null)
	{
		if ($config === null)
		{
			$config = new Registry();
		}
		return new UnrarCommand($dispatcher, $config);
	}
}
