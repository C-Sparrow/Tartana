<?php
namespace Tartana\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class TartanaConfiguration implements ConfigurationInterface
{

	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root('tartana');

		$rootNode->
			children()
				->arrayNode('links')
				->isRequired()
					->children()
						->scalarNode('folder')
							->isRequired()
						->end()
						->booleanNode('convertToHttps')->end()
						->scalarNode('hostFilter')->end()
					->end()
				->end()
				->arrayNode('extract')
				->isRequired()
					->children()
						->scalarNode('destination')
							->isRequired()
						->end()
						->scalarNode('passwordFile')->end()
						->scalarNode('deleteFiles')->end()
					->end()
				->end()
				->arrayNode('sound')
				->isRequired()
					->children()
						->scalarNode('destination')
							->isRequired()
						->end()
						->scalarNode('hostFilter')->end()
					->end()
				->end()
			->end()
		->end();

		return $treeBuilder;
	}
}
