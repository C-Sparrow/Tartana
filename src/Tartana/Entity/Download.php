<?php
namespace Tartana\Entity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="tartana_download",
 * indexes={@ORM\Index(name="destination_idx", columns={"destination"})})
 */
class Download extends Base
{

	const STATE_DOWNLOADING_NOT_STARTED = 1;

	const STATE_DOWNLOADING_STARTED = 2;

	const STATE_DOWNLOADING_COMPLETED = 3;

	const STATE_DOWNLOADING_ERROR = 4;

	const STATE_PROCESSING_NOT_STARTED = 5;

	const STATE_PROCESSING_STARTED = 6;

	const STATE_PROCESSING_COMPLETED = 7;

	const STATE_PROCESSING_ERROR = 8;

	public static $STATES_ALL = [
			self::STATE_DOWNLOADING_NOT_STARTED,
			self::STATE_DOWNLOADING_STARTED,
			self::STATE_DOWNLOADING_COMPLETED,
			self::STATE_DOWNLOADING_ERROR,
			self::STATE_PROCESSING_NOT_STARTED,
			self::STATE_PROCESSING_STARTED,
			self::STATE_PROCESSING_COMPLETED,
			self::STATE_PROCESSING_ERROR
	];

	/**
	 * @ORM\Column(type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(type="string", length=255, unique=true)
	 */
	protected $link;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	protected $destination;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	protected $file_name;

	/**
	 * @ORM\Column(type="decimal", scale=2, nullable=true)
	 */
	protected $progress = 0.00;

	/**
	 * @ORM\Column(type="smallint")
	 */
	protected $state = self::STATE_DOWNLOADING_NOT_STARTED;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $started_at;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	protected $finished_at;

	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $size = 0;

	/**
	 * @ORM\Column(type="integer", nullable=true)
	 */
	protected $pid = 0;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	protected $message;

	/**
	 * @ORM\Column(type="string", length=255, nullable=true)
	 */
	protected $hash;

	/**
	 * Get id
	 *
	 * @return integer|string
	 */
	public function getId ()
	{
		return $this->id;
	}

	/**
	 * Set id
	 *
	 * @return Download
	 */
	public function setId ($id)
	{
		$this->id = $id;

		return $this;
	}

	/**
	 * Set link
	 *
	 * @param string $link
	 *
	 * @return Download
	 */
	public function setLink ($link)
	{
		$this->link = $link;

		return $this;
	}

	/**
	 * Get link
	 *
	 * @return string
	 */
	public function getLink ()
	{
		return $this->link;
	}

	/**
	 * Set progress, if allow reset is set, then a progress smaller than the
	 * actual one can be set.
	 *
	 * @param string $progress
	 * @param boolean $allowReset
	 *
	 * @return Download
	 */
	public function setProgress ($progress, $allowReset = false)
	{
		$progress = (float) $progress;
		if ($progress < 0)
		{
			$progress = 0;
		}
		if ($progress > 100)
		{
			$progress = 100;
		}
		if ($this->progress > $progress && ! $allowReset)
		{
			return $this;
		}
		$this->progress = number_format($progress, 2);

		return $this;
	}

	/**
	 * Get progress
	 *
	 * @return string
	 */
	public function getProgress ()
	{
		return $this->progress;
	}

	/**
	 * Set size in bytes.
	 *
	 * @param string $size
	 *
	 * @return Download
	 */
	public function setSize ($size)
	{
		$this->size = (int) $size;

		return $this;
	}

	/**
	 * Get size
	 *
	 * @return integer
	 */
	public function getSize ()
	{
		return $this->size;
	}

	/**
	 * Set state
	 *
	 * @param integer $state
	 *
	 * @return Download
	 * @see Download::STATE_DOWNLOADING_NOT_STARTED
	 * @see Download::STATE_DOWNLOADING_STARTED
	 * @see Download::STATE_DOWNLOADING_COMPLETED
	 * @see Download::STATE_DOWNLOADING_ERROR
	 * @see Download::STATE_PROCESSING_NOT_STARTED
	 * @see Download::STATE_PROCESSING_STARTED
	 * @see Download::STATE_PROCESSING_COMPLETED
	 * @see Download::STATE_PROCESSING_ERROR
	 */
	public function setState ($state)
	{
		// Check if it is a valid state
		if (! in_array($state,
				[
						self::STATE_DOWNLOADING_NOT_STARTED,
						self::STATE_DOWNLOADING_STARTED,
						self::STATE_DOWNLOADING_COMPLETED,
						self::STATE_DOWNLOADING_ERROR,
						self::STATE_PROCESSING_NOT_STARTED,
						self::STATE_PROCESSING_STARTED,
						self::STATE_PROCESSING_COMPLETED,
						self::STATE_PROCESSING_ERROR
				]))
		{
			return $this;
		}
		$this->state = $state;

		return $this;
	}

	/**
	 * Get state
	 *
	 * @return integer
	 */
	public function getState ()
	{
		return $this->state;
	}

	/**
	 * Set startedAt
	 *
	 * @param \DateTime $startedAt
	 *
	 * @return Download
	 */
	public function setStartedAt (\DateTime $startedAt = null)
	{
		$this->started_at = $startedAt;

		return $this;
	}

	/**
	 * Get startedAt
	 *
	 * @return \DateTime
	 */
	public function getStartedAt ()
	{
		return $this->started_at;
	}

	/**
	 * Set finishedAt
	 *
	 * @param \DateTime $finishedAt
	 *
	 * @return Download
	 */
	public function setFinishedAt (\DateTime $finishedAt = null)
	{
		$this->finished_at = $finishedAt;

		return $this;
	}

	/**
	 * Get finishedAt
	 *
	 * @return \DateTime
	 */
	public function getFinishedAt ()
	{
		return $this->finished_at;
	}

	/**
	 * Set message
	 *
	 * @param string $message
	 *
	 * @return Download
	 */
	public function setMessage ($message)
	{
		$this->message = $message;

		return $this;
	}

	/**
	 * Get message
	 *
	 * @return string
	 */
	public function getMessage ()
	{
		return $this->message;
	}

	/**
	 * Set destination
	 *
	 * @param string $destination
	 *
	 * @return Download
	 */
	public function setDestination ($destination)
	{
		$this->destination = $destination;

		return $this;
	}

	/**
	 * Get destination
	 *
	 * @return string
	 */
	public function getDestination ()
	{
		return $this->destination;
	}

	/**
	 * Set fileName
	 *
	 * @param string $fileName
	 *
	 * @return Download
	 */
	public function setFileName ($fileName)
	{
		$this->file_name = $fileName;

		return $this;
	}

	/**
	 * Get fileName
	 *
	 * @return string
	 */
	public function getFileName ()
	{
		return $this->file_name;
	}

	/**
	 * Set pid
	 *
	 * @param integer $pid
	 *
	 * @return Download
	 */
	public function setPid ($pid)
	{
		$this->pid = $pid;

		return $this;
	}

	/**
	 * Get pid
	 *
	 * @return integer
	 */
	public function getPid ()
	{
		return $this->pid;
	}

	/**
	 * Set hash
	 *
	 * @param string $hash
	 *
	 * @return Download
	 */
	public function setHash ($hash)
	{
		$this->hash = $hash;

		return $this;
	}

	/**
	 * Get hash
	 *
	 * @return string
	 */
	public function getHash ()
	{
		return $this->hash;
	}

	/**
	 * Resets the given download to a not started state including the dependant
	 * settings.
	 *
	 * @param Download $download
	 * @return Download
	 * @see Download::STATE_DOWNLOADING_NOT_STARTED
	 */
	public static function reset (Download $download)
	{
		$download->setState(Download::STATE_DOWNLOADING_NOT_STARTED);
		$download->setProgress(0, true);
		$download->setMessage(null);
		$download->setPid(0);
		$download->setStartedAt(null);
		$download->setFinishedAt(null);

		return $download;
	}
}
