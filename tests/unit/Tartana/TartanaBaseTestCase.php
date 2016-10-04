<?php
namespace Tests\Unit\Tartana;

use SimpleBus\Message\Bus\MessageBus;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Tartana\Component\Command\Runner;
use Tartana\Domain\DownloadRepository;
use Tartana\Entity\Download;
use Tartana\Host\HostFactory;

class TartanaBaseTestCase extends \PHPUnit_Framework_TestCase
{

	protected function getMockCommandBus($callbacks = [])
	{
		foreach ($callbacks as $key => $callback) {
			$callbacks[$key] = [
					$callback
			];
		}
		$commandBus = $this->getMockBuilder(MessageBus::class)->getMock();

		$method = $commandBus->expects($this->exactly(count($callbacks)))
			->method('handle');
		$this->callWithConsecutive($method, $callbacks);

		return $commandBus;
	}

	protected function getMockRunner($callbacks = [], $returnData = [])
	{
		foreach ($callbacks as $key => $callback) {
			if (is_array($callback)) {
				continue;
			}

			$callbacks[$key] = [
					$callback
			];
		}
		$runner = $this->getMockBuilder(Runner::class)->getMock();

		$method = $runner->expects($this->exactly(count($callbacks)))
			->method('execute')
			->will($this->callOnConsecutiveCalls($returnData));
		$this->callWithConsecutive($method, $callbacks);

		return $runner;
	}

	protected function getMockHostFactory($hosts = [])
	{
		if (! is_array($hosts)) {
			$hosts = [
					$hosts
			];
		}
		$factory = $this->getMockBuilder(HostFactory::class)->getMock();
		$factory->expects($this->exactly(count($hosts)))
			->method('createHostDownloader')
			->will($this->callOnConsecutiveCalls($hosts));

		return $factory;
	}

	protected function getMockRepository($downloads = null)
	{
		if ($downloads === null) {
			$download = new Download();
			$download->setLink('http://devnull.org/klad');
			$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
			$downloads = [
					[
							$download
					]
			];
		}

		$repositoryMock = $this->getMockBuilder(DownloadRepository::class)->getMock();
		$repositoryMock->method('findDownloads')->will($this->callOnConsecutiveCalls($downloads));
		$repositoryMock->method('findDownloadsByDestination')->will($this->callOnConsecutiveCalls($downloads));

		return $repositoryMock;
	}

	protected function getMockDispatcher($callbacks = [])
	{
		if (count($callbacks) == 2 && is_string($callbacks[0])) {
			$callbacks = [
					[
							$this->equalTo($callbacks[0]),
							$callbacks[1]
					]
			];
		}
		$dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)->getMock();

		$method = $dispatcher->expects($this->exactly(count($callbacks)))
			->method('dispatch');
		$this->callWithConsecutive($method, $callbacks);
		return $dispatcher;
	}

	protected function callOnConsecutiveCalls(array $data)
	{
		return call_user_func_array(array(
				$this,
				'onConsecutiveCalls'
		), $data);
	}

	protected function callWithConsecutive(\PHPUnit_Framework_MockObject_Builder_MethodNameMatch $method, array $data)
	{
		call_user_func_array(array(
				$method,
				'withConsecutive'
		), $data);
	}
}
