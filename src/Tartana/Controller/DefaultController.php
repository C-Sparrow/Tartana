<?php
namespace Tartana\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{

	/**
	 * @Route("/", name="homepage")
	 */
	public function indexAction()
	{
		return $this->render(
			'default/index.html.twig',
			[
						'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..')
			]
		);
	}

	/**
	 * @Route("/dashboard", name="dashboard")
	 */
	public function dashboardAction()
	{
		return $this->render(
			'default/dashboard.html.twig',
			[
						'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..')
			]
		);
	}

	/**
	 * @Route("/login", name="login")
	 */
	public function loginAction()
	{
		return $this->render(
			'default/login.html.twig',
			[
						'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..')
			]
		);
	}

	/**
	 * @Route("/downloads", name="downloads")
	 */
	public function downloadsAction()
	{
		return $this->render(
			'default/downloads.html.twig',
			[
						'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..')
			]
		);
	}

	/**
	 * @Route("/parameters", name="parameters")
	 */
	public function parametersAction()
	{
		return $this->render(
			'default/parameters.html.twig',
			[
						'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..')
			]
		);
	}

	/**
	 * @Route("/logs", name="logs")
	 */
	public function logsAction()
	{
		return $this->render('default/logs.html.twig', [
				'base_dir' => realpath($this->container->getParameter('kernel.root_dir') . '/..')
		]);
	}
}
