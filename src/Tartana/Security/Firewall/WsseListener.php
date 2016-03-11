<?php
namespace Tartana\Security\Firewall;
use Monolog\Logger;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Security\Authentication\Token\WsseUserToken;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;

class WsseListener implements ListenerInterface
{

	use LoggerAwareTrait;

	protected $tokenStorage;

	protected $authenticationManager;

	public function __construct (TokenStorageInterface $tokenStorage, AuthenticationManagerInterface $authenticationManager)
	{
		$this->tokenStorage = $tokenStorage;
		$this->authenticationManager = $authenticationManager;
	}

	public function handle (GetResponseEvent $event)
	{
		$request = $event->getRequest();

		// Check if authentication Token is present
		if ($request->headers->has('x-wsse'))
		{

			// Token parser
			$wsseRegex = '/UsernameToken Username="([^"]+)", PasswordDigest="([^"]+)", Nonce="([^"]+)", Created="([^"]+)"/';

			if (preg_match($wsseRegex, $request->headers->get('x-wsse'), $matches))
			{
				$token = new WsseUserToken();
				$token->setUser($matches[1]);

				$token->digest = $matches[2];
				$token->nonce = $matches[3];
				$token->created = $matches[4];

				try
				{
					// Authentication process
					$authToken = $this->authenticationManager->authenticate($token);
					$this->tokenStorage->setToken($authToken);

					return;
				}
				catch (NonceExpiredException $expired)
				{
					$failedMessage = 'WSSE Nonce Expired for ' . $token->getUsername() . '. Why ? ' . $expired->getMessage();
					$this->log($failedMessage, Logger::ERROR);

					// Deny authentication with a '403 Forbidden' HTTP response
					$response = new Response();
					$response->setStatusCode(403);
					$response->setContent($failedMessage);
					$event->setResponse($response);

					return;
				}
				catch (AuthenticationException $failed)
				{
					$failedMessage = 'WSSE Login failed for ' . $token->getUsername() . '. Why ? ' . $failed->getMessage();
					$this->log($failedMessage, Logger::ERROR);

					// Deny authentication with a '403 Forbidden' HTTP response
					$response = new Response();
					$response->setStatusCode(403);
					$response->setContent($failedMessage);
					$event->setResponse($response);

					return;
				}
				catch (\Exception $e)
				{
					$failedMessage = $e->getMessage();
					$this->log($failedMessage, Logger::ERROR);

					// Deny authentication with a '403 Forbidden' HTTP response
					$response = new Response();
					$response->setStatusCode(403);
					$response->setContent($failedMessage);
					$event->setResponse($response);

					return;
				}
			}
		}

		// By default deny authentication
		$response = new Response();
		$response->setStatusCode(403);
		$event->setResponse($response);
	}
}
