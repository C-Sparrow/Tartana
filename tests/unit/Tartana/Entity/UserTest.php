<?php
namespace Tests\Unit\Tartana\Domain\Command;
use Tartana\Entity\User;

class UserTest extends \PHPUnit_Framework_TestCase
{

	public function testEmptyUser ()
	{
		$user = new User();

		$this->assertEmpty($user->getId());
		$this->assertEmpty($user->getUsername());
		$this->assertFalse(isset($jsonSerialized['password']));
		$this->assertFalse(isset($jsonSerialized['plainPassword']));
	}

	public function testUserJsonSerialize ()
	{
		$user = new User();
		$user->setPassword('admin');
		$user->setPlainPassword('admin');

		$jsonSerialized = $user->jsonSerialize();

		$this->assertFalse(isset($jsonSerialized['password']));
		$this->assertFalse(isset($jsonSerialized['plainPassword']));
	}
}