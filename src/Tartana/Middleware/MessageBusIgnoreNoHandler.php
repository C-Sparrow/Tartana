<?php
namespace Tartana\Middleware;

use SimpleBus\Message\Bus\Middleware\MessageBusMiddleware;
use SimpleBus\Message\CallableResolver\Exception\UndefinedCallable;

/**
 * Ignores if there is no handler for the a command.
 */
class MessageBusIgnoreNoHandler implements MessageBusMiddleware
{

	public function handle($message, callable $next)
	{
		try {
			$next($message);
		} catch (UndefinedCallable $e) {
		// Ignoring it
		}
	}
}
