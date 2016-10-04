<?php
namespace Tartana\Event;

use League\Flysystem\Adapter\AbstractAdapter;
use Symfony\Component\EventDispatcher\Event;

class ProcessingProgressEvent extends Event
{

	private $source = null;

	private $destination = null;

	private $file = null;

	private $progress = null;

	public function __construct(AbstractAdapter $source, AbstractAdapter $destination, $file, $progress)
	{
		$this->source = $source;
		$this->destination = $destination;
		$this->file = $file;

		$progress = (int) $progress;
		if ($progress < 0) {
			$progress = 0;
		}
		if ($progress > 100) {
			$progress = 100;
		}
		$this->progress = $progress;
	}

	public function getSource()
	{
		return $this->source;
	}

	public function getDestination()
	{
		return $this->destination;
	}

	public function getFile()
	{
		return $this->file;
	}

	public function getProgress()
	{
		return $this->progress;
	}
}
