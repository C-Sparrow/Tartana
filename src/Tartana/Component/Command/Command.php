<?php
namespace Tartana\Component\Command;

use Symfony\Component\Process\ProcessUtils;

class Command
{

	private $command = null;

	private $arguments = [];

	private $async = false;

	private $captureError = true;

	private $outputFile = null;

	private $append = false;

	public function __construct($command)
	{
		$this->command = $command;
	}

	/**
	 * Returns a command which is set up to run commands on Tartana itself.
	 *
	 * @param string $commandName
	 * @return Command
	 */
	public static function getAppCommand($commandName)
	{
		$command = new Command('php');
		$command->addArgument(TARTANA_PATH_ROOT . '/cli/app.php');
		$command->addArgument($commandName);

		return $command;
	}

	/**
	 * Returns the command.
	 *
	 * @return string
	 */
	public function getCommand()
	{
		return $this->command;
	}

	/**
	 * Returns the arguments.
	 *
	 * @return string[]
	 */
	public function getArguments()
	{
		return $this->arguments;
	}

	/**
	 * Adds the given argument to the list.
	 *
	 * @param string $argument
	 * @param boolean $escape
	 * @return \Tartana\Component\Command\Command
	 */
	public function addArgument($argument, $escape = true)
	{
		// Ignore empty arguments
		if (!$argument) {
			return $this;
		}

		if ($escape) {
			$argument = ProcessUtils::escapeArgument($argument);
		}
		$this->arguments[] = $argument;

		return $this;
	}

	/**
	 * Replaces the given old argument with the new one.
	 * If the old argument doesn't exist nothing will be done.
	 *
	 * @param string $oldArgument
	 * @param string $newArgument
	 * @param boolean $escape
	 * @return \Tartana\Component\Command\Command
	 */
	public function replaceArgument($oldArgument, $newArgument, $escape = true)
	{
		if ($escape) {
			$newArgument = ProcessUtils::escapeArgument($newArgument);
		}
		$oldArgumentEscaped = ProcessUtils::escapeArgument($oldArgument);

		foreach ($this->arguments as $key => $arg) {
			if ($arg == $oldArgument || $arg == $oldArgumentEscaped) {
				$this->arguments[$key] = $newArgument;
			}
		}
		return $this;
	}

	/**
	 * Returns the async state of the command.
	 *
	 * @return boolean
	 */
	public function isAsync()
	{
		return $this->async;
	}

	/**
	 * Tells the command it should have async state.
	 *
	 * @param boolean $async
	 * @return \Tartana\Component\Command\Command
	 */
	public function setAsync($async)
	{
		if ($async && !$this->getOutputFile()) {
			// Pipe to dev null
			$this->setOutputFile('/dev/null');
		}
		$this->async = $async;
		return $this;
	}

	/**
	 * Returns if the the error output should be captured in the output.
	 *
	 * @return boolean
	 */
	public function isCaptureErrorInOutput()
	{
		return $this->captureError;
	}

	/**
	 * Tells the command it should capture errors in the output.
	 *
	 * @param boolean $captureError
	 * @return \Tartana\Component\Command\Command
	 */
	public function setCaptureErrorInOutput($captureError)
	{
		$this->captureError = $captureError;
		return $this;
	}

	/**
	 * Returns the output file of the command.
	 *
	 * @return string|null
	 */
	public function getOutputFile()
	{
		return $this->outputFile;
	}

	/**
	 * Tells the command it should pipe the output to the given file.
	 *
	 * @param string $outputFile
	 * @return \Tartana\Component\Command\Command
	 */
	public function setOutputFile($outputFile)
	{
		$this->outputFile = $outputFile;
		return $this;
	}

	/**
	 * Returns if the the output should be appended to the output file.
	 *
	 * @return boolean
	 * @see \Tartana\Component\Command\Command::getOutputFile()
	 */
	public function isAppend()
	{
		return $this->append;
	}

	/**
	 * Tells the command it should append the output to the file.
	 *
	 * @param boolean $append
	 * @return \Tartana\Component\Command\Command
	 * @see \Tartana\Component\Command\Command::getOutputFile()
	 */
	public function setAppend($append)
	{
		$this->append = $append;
		return $this;
	}

	public function __toString()
	{
		$buffer = $this->getCommand();

		// Add the arguments
		if (!empty($this->arguments)) {
			$buffer .= ' ' . trim(implode(' ', $this->getArguments()));
		}

		// Output to the file if set
		if ($this->getOutputFile()) {
			$buffer .= ' >' . ($this->isAppend() ? '>' : '') . ' ' . $this->getOutputFile();
		}

		// Redirect std error to stdout
		if ($this->isCaptureErrorInOutput()) {
			$buffer .= ' 2>&1';
		}

		// Run the command in async mode if set
		if ($this->isAsync()) {
			$buffer .= ' & echo $!';
		}

		return trim($buffer);
	}
}
