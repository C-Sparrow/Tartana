<?php
namespace Tartana\Handler;
use Joomla\Registry\Registry;
use Tartana\Component\Dlc\Decrypter;
use Tartana\Domain\Command\ParseLinks;
use Tartana\Domain\Command\ProcessLinks;
use Tartana\Mixins\LoggerAwareTrait;
use Tartana\Util;
use SimpleBus\Message\Bus\MessageBus;

class ParseLinksHandler
{

	use LoggerAwareTrait;

	private $dlcDecrypter = null;

	private $commandBus = null;

	private $configuration = null;

	public function __construct (Decrypter $dlcDecrypter, MessageBus $commandBus, Registry $configuration)
	{
		$this->dlcDecrypter = $dlcDecrypter;
		$this->commandBus = $commandBus;
		$this->configuration = $configuration;
	}

	public function handle (ParseLinks $file)
	{
		$this->log('Started to parse links for the file ' . $file->getFolder()
			->applyPathPrefix($file->getPath()));

		$links = [];
		if (Util::endsWith($file->getPath(), '.dlc'))
		{
			$content = $file->getFolder()->read($file->getPath())['contents'];
			$links = array_merge($this->dlcDecrypter->decrypt($content));
		}
		if (Util::endsWith($file->getPath(), '.txt'))
		{
			$content = $file->getFolder()->read($file->getPath())['contents'];
			$links = array_merge($links, explode(PHP_EOL, $content));
		}
		$this->log('Found ' . count($links) . ' links to process');

		$linksHostFilter = $this->configuration->get('links.hostFilter');
		$this->log('Host filter is: ' . $linksHostFilter);

		foreach ($links as $key => $value)
		{
			$value = trim($value);
			if ($this->configuration->get('links.convertToHttps', false) && strpos($value, 'http://') === 0)
			{
				$links[$key] = str_replace('http://', 'https://', $value);
			}
			if (! empty($value) && (! $linksHostFilter || strpos($value, $linksHostFilter) !== false))
			{
				continue;
			}
			unset($links[$key]);
		}

		if (! empty($links))
		{
			$this->log('Sending process links command');
			$this->commandBus->handle(new ProcessLinks($links));
			$this->log('Deleting file');
			$file->getFolder()->delete($file->getPath());
		}

		$this->log('Finished to parse links for the file ' . $file->getFolder()
			->applyPathPrefix($file->getPath()));
	}
}