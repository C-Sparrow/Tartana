<?php
namespace Tartana\Handler;
use Joomla\Registry\Registry;
use SimpleBus\Message\Bus\MessageBus;
use Tartana\Component\Decrypter\DecrypterFactory;
use Tartana\Domain\Command\ParseLinks;
use Tartana\Domain\Command\ProcessLinks;
use Tartana\Mixins\LoggerAwareTrait;
use Monolog\Logger;

class ParseLinksHandler
{

	use LoggerAwareTrait;

	private $factory = null;

	private $commandBus = null;

	private $configuration = null;

	public function __construct (DecrypterFactory $factory, MessageBus $commandBus, Registry $configuration)
	{
		$this->factory = $factory;
		$this->commandBus = $commandBus;
		$this->configuration = $configuration;
	}

	public function handle (ParseLinks $file)
	{
		$this->log('Started to parse links for the file ' . $file->getFolder()
			->applyPathPrefix($file->getPath()));

		$links = [];
		$decrypter = $this->factory->createDecryptor($file->getPath());
		if (empty($decrypter))
		{
			$this->log('Found no decrypter for the file ' . $file->getPath());
		}
		else
		{
			try
			{
				$links = $decrypter->decrypt($file->getFolder()
					->read($file->getPath())['contents']);
			}
			catch (\Exception $e)
			{
				$this->log('Exception decrypting file ' . $file->getPath() . ' ' . $e->getMessage(), Logger::ERROR);
			}
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
			if (! empty($value) && (! $linksHostFilter || preg_match("/" . $linksHostFilter . "/", $value)))
			{
				continue;
			}
			unset($links[$key]);
		}

		if (! empty($links))
		{
			$this->log('Sending process links command');
			$this->commandBus->handle(new ProcessLinks(array_values($links)));
			$this->log('Deleting file');
			$file->getFolder()->delete($file->getPath());
		}

		$this->log('Finished to parse links for the file ' . $file->getFolder()
			->applyPathPrefix($file->getPath()));
	}
}