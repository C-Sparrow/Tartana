<?php
namespace Tartana\Security\Authentication\Provider;

use Tartana\Security\Authentication\Token\WsseUserToken;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class WsseProvider implements AuthenticationProviderInterface
{

	use ContainerAwareTrait;

	private $userProvider;

	public function __construct(UserProviderInterface $userProvider)
	{
		$this->userProvider = $userProvider;
	}

	public function authenticate(TokenInterface $token)
	{
		$user = $this->userProvider->loadUserByUsername($token->getUsername());

		// Verify Token and register it
		if ($user && $token instanceof WsseUserToken && $this->validateDigest($token->digest, $token->nonce, $token->created, $user->getPassword())) {
			$authenticatedToken = new WsseUserToken($user->getRoles());
			$authenticatedToken->setUser($user);

			return $authenticatedToken;
		}

		throw new AuthenticationException('The WSSE authentication failed. For user : ' . $user);
	}

	private function validateDigest($digest, $nonce, $created, $secret)
	{
		// Validate timestamp is recent within 5 minutes
		$seconds = time() - strtotime($created);
		if ($seconds > 300) {
			throw new AuthenticationException('Expired timestamp.  Seconds: ' . $seconds);
		}

		// Validate Secret
		$expected = base64_encode(sha1(base64_decode($nonce) . $created . $secret, true));

		// Return TRUE if our newly-calculated digest is the same as the one
		// provided in the validateDigest() call
		return $expected === $digest;
	}

	public function supports(TokenInterface $token)
	{
		return $token instanceof WsseUserToken;
	}
}
