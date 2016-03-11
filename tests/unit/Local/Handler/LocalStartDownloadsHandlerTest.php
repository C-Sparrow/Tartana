<?php
namespace Tests\Unit\Local\Handler;
use League\Flysystem\Adapter\Local;
use Local\Domain\LocalDownloadRepository;
use Local\Handler\LocalStartDownloadsHandler;
use Tartana\Component\Command\Command;
use Tartana\Component\Command\Runner;
use Tartana\Domain\Command\StartDownloads;

class LocalStartDownloadsHandlerTest extends \PHPUnit_Framework_TestCase
{

	public function testStartDownloads ()
	{
		$runner = $this->getMockBuilder(Runner::class)->getMock();
		$runner->expects($this->once())
			->method('execute')
			->with($this->callback(function  (Command $command) {
			return $command->getArguments()[1] == "'download'";
		}));

		$handler = new LocalStartDownloadsHandler($runner);
		$handler->handle(new StartDownloads($this->getMockBuilder(LocalDownloadRepository::class)
			->disableOriginalConstructor()
			->getMock()));
	}
}
