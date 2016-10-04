<?php
namespace Tartana\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ApiUserController extends Controller
{

	/**
	 * @Route("/api/v1/user/find", name="api_v1_user_find")
	 */
	public function findAction(Request $request)
	{
		return new JsonResponse($this->getData($request));
	}

	/**
	 * @Route("/api/v1/user/salt", name="api_v1_user_salt")
	 */
	public function saltAction(Request $request)
	{
		$data = $this->getData($request);

		if ($data['success']) {
			$user = $data['data'][0];
			$data['data'] = [];
			$data['data']['salt'] = $user->getSalt();
		}

		return new JsonResponse($data);
	}

	private function getData(Request $request)
	{
		$data = [
				'success' => true,
				'message' => ''
		];

		$userManager = $this->container->get('fos_user.user_manager');

		$user = $userManager->findUserByUsername($request->get('username'));
		if (! $user) {
			$data['success'] = false;
			$data['message'] = $this->container->get('Translator')->trans('TARTANA_EXTRACT_MESSAGE_USER_NOT_FOUND');
		} else {
			$data['data'][] = $user;
		}

		return $data;
	}
}
