<?php
namespace Tests\Unit\Tartana\Handler;

use Tartana\Domain\Command\ProcessLinks;
use Tartana\Middleware\MessageBusIgnoreNoHandler;
use SimpleBus\Message\Bus\MessageBus;
use SimpleBus\Message\Bus\Middleware\MessageBusMiddleware;
use SimpleBus\Message\Bus\Middleware\MessageBusSupportingMiddleware;

class MessageBusIgnoreNoHandlerTest extends \PHPUnit_Framework_TestCase
{

	public function testNoHandlerNoException()
	{
		$messageBus = new MessageBusSupportingMiddleware([
				new MessageBusIgnoreNoHandler()
		]);
		$messageBus->handle(new ProcessLinks([]));
	}

	/**
	 * @expectedException RuntimeException
	 * @expectedExceptionMessage unit test
	 */
	public function testIgnoreOtherExceptions()
	{
		$messageBus = new MessageBusSupportingMiddleware([
				new MessageBusIgnoreNoHandler(),
				new MessageBusIgnoreNoHandlerTestMiddleware()
		]);
		$messageBus->handle(new ProcessLinks([]));
	}
}

class MessageBusIgnoreNoHandlerTestMiddleware implements MessageBusMiddleware
{

	public function handle($message, callable $next)
	{
		throw new \RuntimeException('unit test');
	}
}
