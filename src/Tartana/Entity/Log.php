<?php
namespace Tartana\Entity;

class Log extends Base
{

	protected $channel;

	protected $message;

	protected $date;

	protected $level;

	protected $context;

	protected $extra;

	public function __construct ($channel, $message, $date, $level, $context, $extra)
	{
		$this->channel = $channel;
		$this->message = $message;
		$this->date = $date;
		$this->level = $level;
		$this->context = $context;
		$this->extra = $extra;
	}

	public function getChannel ()
	{
		return $this->channel;
	}

	public function getMessage ()
	{
		return $this->message;
	}

	/**
	 *
	 * @return \DateTime
	 */
	public function getDate ()
	{
		return $this->date;
	}

	public function getLevel ()
	{
		return $this->level;
	}

	public function getContext ()
	{
		return $this->context;
	}

	public function getExtra ()
	{
		return $this->extra;
	}
}
