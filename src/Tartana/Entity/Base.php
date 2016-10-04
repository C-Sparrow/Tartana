<?php
namespace Tartana\Entity;

use Tartana\Mixins\JsonSerializableTrait;

class Base implements \JsonSerializable
{
	use JsonSerializableTrait;

	public function toArray()
	{
		return get_object_vars($this);
	}
}
