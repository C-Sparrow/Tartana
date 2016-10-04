<?php
namespace Tartana\Domain\Command;

use League\Flysystem\Adapter\AbstractAdapter;

class ParseLinks
{

	private $fs;

	private $path;

	public function __construct(AbstractAdapter $fs, $path)
	{
		$this->fs = $fs;
		$this->path = $path;
	}

	public function getPath()
	{
		return $this->path;
	}

	public function getFolder()
	{
		return $this->fs;
	}
}
