<?php
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TartanaKernel extends Kernel
{

	public function registerBundles ()
	{
		$bundles = [
				new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
				new Symfony\Bundle\SecurityBundle\SecurityBundle(),
				new Symfony\Bundle\TwigBundle\TwigBundle(),
				new Symfony\Bundle\MonologBundle\MonologBundle(),
				new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
				new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
				new Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle(),
				new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
				new SimpleBus\SymfonyBridge\SimpleBusCommandBusBundle(),
				new FOS\RestBundle\FOSRestBundle(),
				new FOS\UserBundle\FOSUserBundle(),
				new Tartana\Tartana(),
				new Local\Local(),
				new Synology\Synology()
		];

		if (in_array($this->getEnvironment(), [
				'dev',
				'test',
				'test_synology'
		], true))
		{
			$bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
			$bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
			$bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
			$bundles[] = new Liip\FunctionalTestBundle\LiipFunctionalTestBundle();
			$bundles[] = new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle();
		}

		return $bundles;
	}

	public function getRootDir ()
	{
		return __DIR__;
	}

	public function getCacheDir ()
	{
		return dirname(__DIR__) . '/var/cache/' . $this->getEnvironment();
	}

	public function getLogDir ()
	{
		return dirname(__DIR__) . '/var/logs';
	}

	public function registerContainerConfiguration (LoaderInterface $loader)
	{
		$loader->load($this->getRootDir() . '/config/internal/config_' . $this->getEnvironment() . '.yml');
	}
}
