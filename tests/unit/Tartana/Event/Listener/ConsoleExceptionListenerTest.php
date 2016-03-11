<?php
namespace Tests\Unit\Tartana\Event\Listener;
use Monolog\Logger;
use Tartana\Event\Listener\ConsoleExceptionListener;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Tartana\Console\Command\DefaultCommand;
use Joomla\Registry\Registry;
use Tartana\Domain\DownloadRepository;
use SimpleBus\Message\Bus\MessageBus;

class ConsoleExceptionListenerTest extends \PHPUnit_Framework_TestCase
{

	public function testHasFilesToProcess ()
	{
		$logger = $this->getMockBuilder(Logger::class)
			->disableOriginalConstructor()
			->getMock();
		$logger->expects($this->once())
			->method('log')
			->with($this->equalTo(400), $this->stringContains('hello unit test'));

		$event = new ConsoleExceptionEvent(
				new DefaultCommand($this->getMockBuilder(DownloadRepository::class)->getMock(), $this->getMockBuilder(MessageBus::class)->getMock(),
						new Registry()), new ArrayInput([]), new BufferedOutput(), new \Exception('hello unit test'), 1);

		$listener = new ConsoleExceptionListener();
		$listener->setLogger($logger);
		$listener->onConsoleException($event);
	}
}
