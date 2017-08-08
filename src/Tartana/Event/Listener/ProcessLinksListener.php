<?php
namespace Tartana\Event\Listener;

use Tartana\Domain\Command\ProcessLinks;
use Tartana\Event\CommandEvent;
use Tartana\Mixins\HostFactoryAwareTrait;

class ProcessLinksListener
{

	use HostFactoryAwareTrait;

	public function onProcessLinksBefore(CommandEvent $event)
	{
		if (!$event->getCommand() instanceof ProcessLinks) {
			return;
		}

		$links = [];
		foreach ($event->getCommand()->getLinks() as $link) {
			$downloader = $this->getDownloader($link);
			if (empty($downloader)) {
				$links[] = $link;
				continue;
			}

			$links = array_merge($links, $downloader->fetchLinkList($link));
		}

		$event->setCommand(new ProcessLinks(array_unique($links)));
	}
}
