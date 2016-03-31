<?php
namespace Tartana\Component\Decrypter;
use League\Flysystem\Adapter\Local;
use Monolog\Logger;
use Tartana\Mixins\LoggerAwareTrait;

abstract class BaseDecrypter implements DecrypterInterface
{

	use LoggerAwareTrait;

	public function decrypt ($dlc)
	{
		$this->log('Started file decrypting', Logger::INFO);

		if (@file_exists(realpath($dlc)))
		{
			$dlc = realpath($dlc);
			$fs = new Local(dirname($dlc));
			$content = $fs->read($fs->removePathPrefix($dlc))['contents'];
		}
		else
		{
			$content = $dlc;
		}

		if (! $content)
		{
			throw new \RuntimeException('Empty content.');
		}

		$links = $this->getLinks($content);

		$this->log('Finished file decrypting', Logger::INFO);

		return $links;
	}

	abstract protected function getLinks ($content);
}