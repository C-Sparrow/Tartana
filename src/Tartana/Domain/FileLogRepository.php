<?php
namespace Tartana\Domain;
use Tartana\Entity\Log;

class FileLogRepository implements LogRepository
{

	private $file = null;

	public function __construct ($file)
	{
		$this->file = $file;
	}

	public function findLogs ($count = 10)
	{
		if (! file_exists($this->file))
		{
			return [];
		}

		$lines = $this->getLastLines($this->file, $count);
		$logs = [];
		foreach ($lines as $line)
		{
			$log = $this->parse($line);
			if ($log)
			{
				$logs[] = $log;
			}
		}
		return $logs;
	}

	/**
	 *
	 * @param string $log
	 */
	private function parse ($log)
	{
		if (! is_string($log) || strlen($log) === 0)
		{
			return null;
		}

		preg_match("/\[(?P<date>.*)\] (?P<channel>\w+).(?P<level>\w+): (?P<message>.*[^ ]+) (?P<context>[^ ]+) (?P<extra>[^ ]+)/", $log, $data);

		if (! isset($data['date']))
		{
			return null;
		}

		return new Log($data['channel'], $data['message'], \DateTime::createFromFormat('Y-m-d H:i:s', $data['date']), $data['level'],
				json_decode($data['context'], true), json_decode($data['extra'], true));
	}

	/**
	 * Gets the last lines from the given file
	 *
	 * @param string $path
	 * @param integer $lineCount
	 * @param integer $blockSize
	 *
	 * @return string[]
	 * @see http://stackoverflow.com/questions/6451232/reading-large-files-from-end
	 */
	private function getLastLines ($path, $lineCount, $blockSize = 512)
	{
		$lines = [];

		// we will always have a fragment of a non-complete line
		// keep this in here till we have our next entire line.
		$leftover = "";

		$fh = fopen($path, 'r');
		// go to the end of the file
		fseek($fh, 0, SEEK_END);
		do
		{
			// need to know whether we can actually go back
			// $blockSize bytes
			$canRead = $blockSize;
			if (ftell($fh) < $blockSize)
			{
				$canRead = ftell($fh);
			}

			// go back as many bytes as we can
			// read them to $data and then move the file pointer
			// back to where we were.
			fseek($fh, - $canRead, SEEK_CUR);
			$data = @fread($fh, $canRead);
			$data .= $leftover;
			fseek($fh, - $canRead, SEEK_CUR);

			// split lines by \n. Then reverse them,
			// now the last line is most likely not a complete
			// line which is why we do not directly add it, but
			// append it to the data read the next time.
			$splitData = array_reverse(explode("\n", $data));
			$newLines = array_slice($splitData, 0, - 1);

			// When empty lines, ignore them
			foreach ($newLines as $key => $newLine)
			{
				if (trim($newLine))
				{
					continue;
				}
				unset($newLines[$key]);
			}

			$lines = array_merge($lines, $newLines);
			$leftover = $splitData[count($splitData) - 1];
		}
		while (count($lines) < $lineCount && ftell($fh) != 0);
		if (ftell($fh) == 0)
		{
			$lines[] = $leftover;
		}
		fclose($fh);
		// Usually, we will read too many lines, correct that here.
		return array_slice($lines, 0, $lineCount);
	}
}