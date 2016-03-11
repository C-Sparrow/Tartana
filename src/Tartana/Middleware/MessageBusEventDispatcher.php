<?php
namespace Tartana\Middleware;
use Tartana\Event\CommandEvent;
use SimpleBus\Message\Bus\Middleware\MessageBusMiddleware;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Sends a before and after command event to an event dispatcher.
 */
class MessageBusEventDispatcher implements MessageBusMiddleware
{

	private $dispatcher = null;

	public function __construct (EventDispatcherInterface $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

	public function handle ($message, callable $next)
	{
		$event = new CommandEvent($message);
		$this->dispatcher->dispatch('commandbus.command.before', $event);
		$next($message);
		$this->dispatcher->dispatch('commandbus.command.after', $event);
	}
}