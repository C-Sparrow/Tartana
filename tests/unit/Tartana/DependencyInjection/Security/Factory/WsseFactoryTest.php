<?php
namespace Tests\Unit\Tartana\Domain\Command;

use Tartana\DependencyInjection\Security\Factory\WsseFactory;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class WsseFactoryTest extends \PHPUnit_Framework_TestCase
{

	public function testSetDefinitions()
	{
		$container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
		$definition = $this->getMockBuilder(Definition::class)->getMock();
		$container->method('setDefinition')->willReturn($definition);
		$factory = new WsseFactory();

		$data = $factory->create($container, 'unit', [], null, 'hello');

		$this->assertNotEmpty($data);
		$this->assertEquals('security.authentication.provider.wsse.unit', $data[0]);
		$this->assertEquals('security.authentication.listener.wsse.unit', $data[1]);
		$this->assertEquals('hello', $data[2]);
	}

	public function testGetPosition()
	{
		$factory = new WsseFactory();

		$this->assertEquals('pre_auth', $factory->getPosition());
	}

	public function testGetKey()
	{
		$factory = new WsseFactory();

		$this->assertEquals('wsse', $factory->getKey());
	}

	public function testAddConfiguration()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('unit');

		$factory = new WsseFactory();
		$factory->addConfiguration($rootNode);

		$tree = $treeBuilder->buildTree();
		$this->assertEquals('lifetime', $tree->getChildren()['lifetime']->getName());
	}
}
