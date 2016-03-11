<?php
namespace Tartana\DataFixtures\ORM;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * @codeCoverageIgnore
 */
class LoadUserData implements FixtureInterface, ContainerAwareInterface
{
	use ContainerAwareTrait;

	public function load (ObjectManager $manager)
	{
		$userManager = $this->container->get('fos_user.user_manager');

		$user = $userManager->createUser();
		$user->setUsername('admin');
		$user->setEmail('email@domain.com');
		$user->setPlainPassword('admin');
		$user->setEnabled(true);
		$user->setRoles(array(
				'ROLE_ADMIN'
		));

		// Update the user
		$userManager->updateUser($user, true);
	}
}