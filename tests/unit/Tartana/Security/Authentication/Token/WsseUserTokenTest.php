<?php
namespace Tests\Unit\Tartana\Domain\Command;
use Tartana\Security\Authentication\Token\WsseUserToken;

class WsseUserTokenTest extends \PHPUnit_Framework_TestCase
{

	public function testEmptyToken ()
	{
		$token = new WsseUserToken();

		$this->assertEmpty($token->getCredentials());
		$this->assertFalse($token->isAuthenticated());
	}

	public function testTokenWithRoles ()
	{
		$token = new WsseUserToken([
				'unit-test'
		]);

		$this->assertEmpty($token->getCredentials());
		$this->assertTrue($token->isAuthenticated());
	}
}