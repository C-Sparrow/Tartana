<?php
namespace Tartana\Mixins;
use Tartana\Host\HostFactory;

trait HostFactoryAwareTrait
{

	private $factory = null;

	public function getHostFactory ()
	{
		return $this->factory;
	}

	public function setHostFactory (HostFactory $factory = null)
	{
		$this->factory = $factory;
	}

	public function getDownloader ($url)
	{
		if ($this->getHostFactory())
		{
			return $this->getHostFactory()->createHostDownloader($url);
		}
		return null;
	}
}
