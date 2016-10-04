<?php
namespace Tartana\Component\Decrypter;

use Tartana\Mixins\LoggerAwareTrait;

class DecrypterFactory
{
	use LoggerAwareTrait;

	/**
	 *
	 * @param string $fileName
	 * @return \Tartana\Component\Decrypter\DecrypterInterface
	 */
	public function createDecryptor($fileName)
	{
		$className = 'Tartana\\Component\\Decrypter\\' . ucfirst(strtolower(pathinfo($fileName, PATHINFO_EXTENSION)));

		// Check if the class exists for the host to download
		if (! class_exists($className)) {
			return null;
		}

		$decrypter = new $className();
		if (! $decrypter instanceof DecrypterInterface) {
			return null;
		}

		$decrypter->setLogger($this->getLogger());

		return $decrypter;
	}
}
