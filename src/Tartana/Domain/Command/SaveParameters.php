<?php
namespace Tartana\Domain\Command;

class SaveParameters
{

	private $parameters;

	public function __construct(array $parameters)
	{
		$this->parameters = $parameters;
	}

	public function getParameters()
	{
		return $this->parameters;
	}
}
