<?php
namespace Tartana\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class WsseUserToken extends AbstractToken
{

	public $created;

	public $digest;

	public $nonce;

	public function __construct(array $roles = array())
	{
		parent::__construct($roles);

		$this->setAuthenticated(count($roles) > 0);
	}

	public function getCredentials()
	{
		return '';
	}

	public static function generateToken($username, $password, $roles = [])
	{
		$token = new self($roles);
		$token->setUser($username);
		$token->created = date('c');
		$token->nonce = self::makeNonce();
		$token->digest = base64_encode(sha1(base64_decode($token->nonce) . $token->created . $password, true));
		return $token;
	}

	private static function makeNonce()
	{
		$chars = "123456789abcdefghijklmnopqrstuvwxyz";
		$random = "" . microtime();
		$random .= mt_rand();
		$mi = strlen($chars) - 1;
		for ($i = 0; $i < 10; $i ++) {
			$random .= $chars[mt_rand(0, $mi)];
		}
		$nonce = md5($random);
		return $nonce;
	}
}
