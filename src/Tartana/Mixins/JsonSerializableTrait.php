<?php
namespace Tartana\Mixins;

trait JsonSerializableTrait
{

	public function jsonSerialize()
	{
		return get_object_vars($this);
	}
}
