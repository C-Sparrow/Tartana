<?php
namespace Tests\Unit\Tartana\Domain\Command;

use Tartana\Entity\User;
use Tartana\Security\Authentication\Provider\WsseProvider;
use Tartana\Security\Authentication\Token\WsseUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class WsseProviderTest extends \PHPUnit_Framework_TestCase
{

	public function testAuthenticate()
	{
		$user = new User();
		$user->setPassword('admin');

		$userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();
		$userProvider->expects($this->once())
			->method('loadUserByUsername')
			->will($this->returnValue($user));
		$provider = new WsseProvider($userProvider);

		$token = WsseUserToken::generateToken('admin', 'admin');
		$token->setUser($user);

		$authenticatedToken = $provider->authenticate($token);
		$this->assertNotEmpty($authenticatedToken);
		$this->assertEquals($token->getUser(), $authenticatedToken->getUser());
	}

	public function testAuthenticateWrongPassword()
	{
		$this->setExpectedException(AuthenticationException::class);

		$user = new User();
		$user->setPassword('wrong');

		$userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();
		$userProvider->expects($this->once())
			->method('loadUserByUsername')
			->will($this->returnValue($user));
		$provider = new WsseProvider($userProvider);
		$provider->authenticate(WsseUserToken::generateToken('admin', 'admin'));
	}

	public function testAuthenticateTokenExpired()
	{
		$this->setExpectedException(AuthenticationException::class);

		$user = new User();
		$user->setPassword('wrong');

		$userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();
		$userProvider->expects($this->once())
			->method('loadUserByUsername')
			->will($this->returnValue($user));
		$provider = new WsseProvider($userProvider);

		$token          = WsseUserToken::generateToken('admin', 'admin');
		$token->created = date('c', time() - 1000);
		$provider->authenticate($token);
	}

	public function testAuthenticateTokenInFuture()
	{
		$this->setExpectedException(AuthenticationException::class);

		$user = new User();
		$user->setPassword('wrong');

		$userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();
		$userProvider->expects($this->once())
			->method('loadUserByUsername')
			->will($this->returnValue($user));
		$provider = new WsseProvider($userProvider);

		$token          = WsseUserToken::generateToken('admin', 'admin');
		$token->created = date('c', time() + 1000);
		$provider->authenticate($token);
	}

	public function testAuthenticateNoUser()
	{
		$this->setExpectedException(AuthenticationException::class);

		$userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();
		$provider     = new WsseProvider($userProvider);
		$provider->authenticate(WsseUserToken::generateToken('admin', 'admin'));
	}

	public function testSupports()
	{
		$userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();
		$provider     = new WsseProvider($userProvider);
		$this->assertTrue($provider->supports(WsseUserToken::generateToken('admin', 'admin')));
	}

	public function testNotSupports()
	{
		$userProvider = $this->getMockBuilder(UserProviderInterface::class)->getMock();
		$provider     = new WsseProvider($userProvider);
		$this->assertFalse($provider->supports($this->getMockBuilder(TokenInterface::class)
			->getMock()));
	}
}
