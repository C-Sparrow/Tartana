<?php
namespace Tartana\Controller;

use Tartana\Util;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use League\Flysystem\Adapter\Local;

class ApiFileController extends Controller
{

	/**
	 * @Route("/api/v1/file/add", name="api_v1_file_add")
	 */
	public function fileAddAction(Request $request)
	{
		$folder = $this->container->getParameter('tartana.config')['links']['folder'];
		$folder = Util::realPath($folder);

		if (!empty($folder)) {
			$fs = new Local($folder);

			// Moving the uploaded file to the location of the links repository
			foreach ($request->files as $file) {
				$file->move($fs->getPathPrefix(), $file->getClientOriginalName());
			}
		}

		$data = array(
			'success' => true,
			'message' => $this->get('Translator')->trans('TARTANA_TEXT_FILE_ADDED_TO_QUEUE')
		);

		return new JsonResponse($data);
	}
}
