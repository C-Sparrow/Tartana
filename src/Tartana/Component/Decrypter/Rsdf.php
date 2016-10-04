<?php
namespace Tartana\Component\Decrypter;

class Rsdf extends BaseDecrypter
{

	public function getLinks($content)
	{
		$key = pack('H*', "8C35192D964DC3182C6F84F3252239EB4A320D2500000000");
		$iv = pack('H*', "a3d5a33cb95ac1f5cbdb1ad25cb0a7aa");
		$cipher = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CFB, '');
		mcrypt_generic_init($cipher, $key, $iv);

		$content = @pack('H*', $content);

		$links = [];
		if (stripos($content, "\xDA") !== false) {
			$links = explode("\xDA", $content);
		} elseif (stripos($content, "\n") !== false) {
			$links = explode("\n", $content);
		}

		$urls = [];
		foreach ($links as $link) {
			if (empty($link)) {
				continue;
			}

			$text = @mdecrypt_generic($cipher, base64_decode($link));
			if (empty($text)) {
				continue;
			}

			$urls[] = $text;
		}

		if (empty($urls) && error_get_last()) {
			throw new \RuntimeException(error_get_last()['message']);
		}

		return $urls;
	}
}
