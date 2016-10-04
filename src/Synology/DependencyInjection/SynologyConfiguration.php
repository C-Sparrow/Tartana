<?php
namespace Synology\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class SynologyConfiguration implements ConfigurationInterface
{

	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('synology');

		$rootNode->
			children()
				->booleanNode('enabled')
					->isRequired()
				->end()
				->scalarNode('address')
					->isRequired()
				->end()
				->scalarNode('username')
					->isRequired()
				->end()
				->scalarNode('password')
					->isRequired()
				->end()
				->scalarNode('downloads')
					->isRequired()
				->end()
				->scalarNode('downloadShare')
					->isRequired()
				->end()
			->end()
		->end();

		return $treeBuilder;
	}
}
