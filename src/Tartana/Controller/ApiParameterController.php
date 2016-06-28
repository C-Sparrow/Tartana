<?php
namespace Tartana\Controller;

use League\Flysystem\Adapter\Local;
use Tartana\Domain\Command\SaveParameters;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

class ApiParameterController extends Controller
{

	/**
	 * @Route("/api/v1/parameter/find", name="api_v1_parameter_find")
	 */
	public function findAction()
	{
		$data = [];

		$root = new Local($this->container->getParameter('kernel.root_dir') . '/config');
		$parameters = Yaml::parse($root->read('parameters.yml')['contents']);
		if (is_array($parameters))
		{
			foreach ($parameters['parameters'] as $key => $parameter)
			{
				$p = [
						'value' => $parameter,
						'key' => $key
				];
				$label = str_replace('tartana.', '', $key);
				$label = str_replace('.', ' ', $label);
				$label = ucfirst($label);
				$p['label'] = $label;

				$data[] = $p;
			}
		}
		$data = [
				'success' => true,
				'message' => '',
				'data' => $data
		];

		return new JsonResponse($data);
	}

	/**
	 * @Route("/api/v1/parameter/set", name="api_v1_parameter_set")
	 */
	public function setAction(Request $request)
	{
		$parameters = $request->request->all();
		$labels = [];
		foreach ($parameters as $key => $p)
		{
			// http://stackoverflow.com/questions/68651/get-php-to-stop-replacing-characters-in-get-or-post-arrays
			$newKey = str_replace('_', '.', $key);
			unset($parameters[$key]);
			$parameters[$newKey] = $p;

			$labels[] = $this->transformKeyToLabel($newKey);
		}
		$commandBus = $this->container->get('CommandBus');
		$commandBus->handle(new SaveParameters($parameters));

		$data = [
				'success' => true,
				'message' => sprintf($this->container->get('Translator')->trans('TARTANA_VIEW_PARAMETERS_SET_PARAMETER_SUCCESS'),
						implode(',', $labels))
		];

		return new JsonResponse($data);
	}

	private function transformKeyToLabel($key)
	{
		$label = str_replace('tartana.', '', $key);
		$label = str_replace('.', ' ', $label);
		$label = ucfirst($label);

		return $label;
	}
}
