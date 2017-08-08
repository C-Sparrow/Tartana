<?php
namespace Local\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class LocalConfiguration implements ConfigurationInterface
{

	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode    = $treeBuilder->root('local');

		$rootNode->
		children()
			->booleanNode('enabled')
			->isRequired()
			->end()
			->scalarNode('downloads')
			->isRequired()
			->end()
			->end()
			->end();

		return $treeBuilder;
	}
}
