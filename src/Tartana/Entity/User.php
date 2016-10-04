<?php
namespace Tartana\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Tartana\Mixins\JsonSerializableTrait;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser implements \JsonSerializable
{

	use JsonSerializableTrait{
		jsonSerialize as traitJsonSerialize;
	}

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	public function __construct()
	{
		parent::__construct();
	}

	public function jsonSerialize()
	{
		$vars = $this->traitJsonSerialize();

		// Unset sensitive fields
		unset($vars['password']);
		unset($vars['plainPassword']);

		return $vars;
	}
}
