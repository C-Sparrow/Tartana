<?php
namespace Tartana;

use Tartana\DependencyInjection\Security\Factory\WsseFactory;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @codeCoverageIgnore
 */
class Tartana extends Bundle
{

	public function build(ContainerBuilder $container)
	{
		parent::build($container);

		$extension = $container->getExtension('security');
		$extension->addSecurityListenerFactory(new WsseFactory());
	}
}
