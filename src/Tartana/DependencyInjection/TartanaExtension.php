<?php
namespace Tartana\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class TartanaExtension extends Extension
{

	public function load(array $configs, ContainerBuilder $container)
	{
		$config = $this->processConfiguration($this->getExtensionConfiguration(), $configs);
		$container->setParameter($this->getAlias() . '.config', $config);

		if (! isset($config['enabled']) || $config['enabled']) {
		// If we are extending, we need the directory of the main class
			$reflection = new \ReflectionClass($this);
			$directory = dirname($reflection->getFileName());
			$loader = new YamlFileLoader($container, new FileLocator($directory . '/../Resources/config'));
			$loader->load('services.yml');
		}
	}

	/**
	 *
	 * @return Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface
	 */
	protected function getExtensionConfiguration()
	{
		return new TartanaConfiguration();
	}
}
