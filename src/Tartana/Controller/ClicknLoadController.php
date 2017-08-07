<?php
namespace Tartana\Controller;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Tartana\Util;

class ClicknLoadController extends Controller
{

	/**
	 * @Route("/flash/addcrypted2", name="clicknload")
	 */
	public function addcrypted2Action(Request $request)
	{
		$data = array(
			'success' => false,
			'message' => $this->get('Translator')->trans('TARTANA_CLICKNLOAD_ERROR_ADDING_TO_QUEUE')
		);

		preg_match('/\'(.*?)\'/', $request->get('jk'), $matches);

		if (count($matches) < 1) {
			return;
		}

		$key = hex2bin($matches[1]);

		$cp = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', 'cbc', '');
		@mcrypt_generic_init($cp, $key, $key);
		$dec = mdecrypt_generic($cp, base64_decode($request->get('crypted')));
		mcrypt_generic_deinit($cp);
		mcrypt_module_close($cp);

		$folder = $this->container->getParameter('tartana.config')['links']['folder'];
		$folder = Util::realPath($folder);

		if (!empty($folder)) {
			$fs = new Local($folder);

			$name = $request->get('package', rand(0, 1000)) . '.txt';

			// Sanitize content
			$content = '';
			foreach (preg_split('/\r\n|\r|\n/', $dec) as $file) {
				if (strpos($file, 'http://') !== 0) {
					continue;
				}

				$content .= $file . PHP_EOL;
			}

			$content = trim($content);

			if ($content) {
				$fs->write($name, $content, new Config());

				$data = array(
					'success' => true,
					'message' => $this->get('Translator')->trans('TARTANA_CLICKNLOAD_CONTENT_ADDED_TO_QUEUE')
				);
			}
		}

		return new JsonResponse($data);
	}
}
