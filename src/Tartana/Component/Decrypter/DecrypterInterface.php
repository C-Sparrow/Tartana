<?php
namespace Tartana\Component\Decrypter;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

interface DecrypterInterface
{

	/**
	 * Decrypts the given string or path.
	 *
	 * @param string $string
	 * @return string[]
	 * @throws \RuntimeException if something goes wrong
	 */
	public function decrypt ($string);

	public function setLogger (LoggerInterface $logger = null);
}