<?php
namespace Tartana\Middleware;
use Tartana\Event\CommandEvent;
use SimpleBus\Message\Bus\Middleware\MessageBusMiddleware;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Sends a before and after command event to an event dispatcher.
 * On the before event, the command can be manipulated, before it is passed to
 * the handler.
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
		$next($event->getCommand());
		$this->dispatcher->dispatch('commandbus.command.after', $event);
	}
}