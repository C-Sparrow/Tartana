<?php
namespace Tartana\Component\Decrypter;

use Tartana\Util;

class Txt extends BaseDecrypter
{

	public function getLinks($content)
	{
		$mustUnset = false;
		$links = explode(PHP_EOL, $content);
		foreach ($links as $key => $link) {
			$uri = Util::parseUrl($link);
			if (! $uri['scheme']) {
				unset($links[$key]);
				$mustUnset = true;
			}
		}

		if ($mustUnset && empty($links)) {
			throw new \RuntimeException('Invalid content');
		}

		return $links;
	}
}
