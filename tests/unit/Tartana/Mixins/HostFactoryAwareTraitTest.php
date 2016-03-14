<?php
namespace Tests\Unit\Tartana\Mixins;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class HostFactoryAwareTraitTest extends TartanaBaseTestCase
{

	public function testGetDownloader ()
	{
		$trait = $this->getObjectForTrait('Tartana\Mixins\HostFactoryAwareTrait');

		$this->assertEmpty($trait->getHostFactory());
		$this->assertEmpty($trait->getDownloader('url'));
	}

	public function testGetDownloaderSetNullFactory ()
	{
		$trait = $this->getObjectForTrait('Tartana\Mixins\HostFactoryAwareTrait');

		$trait->setHostFactory(null);

		$this->assertEmpty($trait->getHostFactory());
		$this->assertEmpty($trait->getDownloader('url'));
	}

	public function testHandleCommandWithCommandBus ()
	{
		$factory = $this->getMockHostFactory(null);

		$trait = $this->getObjectForTrait('Tartana\Mixins\HostFactoryAwareTrait');
		$trait->setHostFactory($factory);
		$trait->getDownloader('url');

		$this->assertEquals($factory, $trait->getHostFactory());
	}
}