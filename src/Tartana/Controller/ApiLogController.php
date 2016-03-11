<?php
namespace Tartana\Controller;
use Tartana\Domain\Command\DeleteLogs;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiLogController extends Controller
{

	/**
	 * @Route("/api/v1/log/find", name="api_v1_log_find")
	 */
	public function findAction ()
	{
		$logs = $this->container->get('LogRepository')->findLogs(1000);

		$data = [
				'success' => true,
				'message' => '',
				'data' => $logs
		];

		return new JsonResponse($data);
	}

	/**
	 * @Route("/api/v1/log/deleteall", name="api_v1_log_deleteall")
	 */
	public function deleteallAction ()
	{
		$commandBus = $this->container->get('CommandBus');
		$commandBus->handle(new DeleteLogs());

		$data = [
				'success' => true,
				'message' => ''
		];

		return new JsonResponse($data);
	}
}
