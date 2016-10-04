<?php
namespace Tests\Unit\Tartana\Domain\Command;

use Tartana\Security\Authentication\Token\WsseUserToken;
use Tartana\Security\Firewall\WsseListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;

class WsseListenerTest extends \PHPUnit_Framework_TestCase
{

	public function testValidToken()
	{
		$storage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
		$storage->expects($this->once())
			->method('setToken');
		$auth = $this->getMockBuilder(AuthenticationManagerInterface::class)->getMock();
		$auth->expects($this->once())
			->method('authenticate')
			->with($this->callback(function (WsseUserToken $token) {
				return $token->getUser() == 'admin';
			}));
		$listener = new WsseListener($storage, $auth);

		$request = new Request([
				'GET'
		], [], [], [], [], [
				'HTTP_X-WSSE' => $this->makeToken('admin', 'admin')
		]);
		$event = $this->getMockBuilder(GetResponseEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getRequest')->willReturn($request);
		$event->expects($this->never())
			->method('setResponse');
		$listener->handle($event);
	}

	public function testInValidToken()
	{
		$storage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
		$storage->expects($this->never())
			->method('setToken');
		$auth = $this->getMockBuilder(AuthenticationManagerInterface::class)->getMock();
		$auth->expects($this->never())
			->method('authenticate');
		$listener = new WsseListener($storage, $auth);

		$request = new Request([
				'GET'
		], [], [], [], [], [
				'HTTP_X-WSSE' => 'invalid'
		]);
		$event = $this->getMockBuilder(GetResponseEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getRequest')->willReturn($request);
		$event->expects($this->once())
			->method('setResponse')
			->with($this->callback(function (Response $response) {
				return $response->getStatusCode() == 403;
			}));
		$listener->handle($event);
	}

	public function testFailedAuthentication()
	{
		$storage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
		$storage->expects($this->never())
			->method('setToken');
		$auth = $this->getMockBuilder(AuthenticationManagerInterface::class)->getMock();
		$auth->expects($this->once())
			->method('authenticate')
			->will($this->throwException(new AuthenticationException()));
		$listener = new WsseListener($storage, $auth);

		$request = new Request([
				'GET'
		], [], [], [], [], [
				'HTTP_X-WSSE' => $this->makeToken('admin', 'admin')
		]);
		$event = $this->getMockBuilder(GetResponseEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getRequest')->willReturn($request);
		$event->expects($this->once())
			->method('setResponse')
			->with($this->callback(function (Response $response) {
				return $response->getStatusCode() == 403;
			}));
		$listener->handle($event);
	}

	public function testInvalidNonce()
	{
		$storage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
		$storage->expects($this->once())
			->method('setToken')
			->will($this->throwException(new NonceExpiredException()));
		$auth = $this->getMockBuilder(AuthenticationManagerInterface::class)->getMock();
		$auth->expects($this->once())
			->method('authenticate');
		$listener = new WsseListener($storage, $auth);

		$request = new Request([
				'GET'
		], [], [], [], [], [
				'HTTP_X-WSSE' => $this->makeToken('admin', 'admin')
		]);
		$event = $this->getMockBuilder(GetResponseEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getRequest')->willReturn($request);
		$event->expects($this->once())
			->method('setResponse')
			->with($this->callback(function (Response $response) {
				return $response->getStatusCode() == 403;
			}));
		$listener->handle($event);
	}

	public function testErrorThrown()
	{
		$storage = $this->getMockBuilder(TokenStorageInterface::class)->getMock();
		$storage->expects($this->once())
			->method('setToken')
			->will($this->throwException(new \Exception()));
		$auth = $this->getMockBuilder(AuthenticationManagerInterface::class)->getMock();
		$auth->expects($this->once())
			->method('authenticate');
		$listener = new WsseListener($storage, $auth);

		$request = new Request([
				'GET'
		], [], [], [], [], [
				'HTTP_X-WSSE' => $this->makeToken('admin', 'admin')
		]);
		$event = $this->getMockBuilder(GetResponseEvent::class)
			->disableOriginalConstructor()
			->getMock();
		$event->method('getRequest')->willReturn($request);
		$event->expects($this->once())
			->method('setResponse')
			->with($this->callback(function (Response $response) {
				return $response->getStatusCode() == 403;
			}));
		$listener->handle($event);
	}

	private function makeToken($username, $password)
	{
		$data = WsseUserToken::generateToken($username, $password);
		return sprintf(
			'UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"',
			$username,
			$data->digest,
			$data->nonce,
			$data->created
		);
	}
}
