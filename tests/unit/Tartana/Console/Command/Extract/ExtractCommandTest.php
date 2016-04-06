<?php
namespace Tests\Unit\Tartana\Console\Command\Extract;
use Joomla\Registry\Registry;
use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tartana\Component\Command\Command;
use Tartana\Console\Command\Extract\ExtractCommand;
use Tests\Unit\Tartana\TartanaBaseTestCase;
use Tartana\Event\ProcessingCompletedEvent;

class ExtractCommandTest extends TartanaBaseTestCase
{

	public function testExecute ()
	{
		$fs = new Local(__DIR__);

		$command = $this->getMockForAbstractClass(ExtractCommand::class,
				[
						$this->getMockDispatcher(),
						$this->getMockRunner(
								[
										[
												$this->callback(
														function  (Command $command) {
															return $command->getCommand() == 'extract';
														}),
												$this->callback(
														function  ($callback) {

															if ($callback)
															{
																$callback('unit test');
															}
															return $callback != null;
														})
										]
								]),
						new Registry()
				]);
		$command->expects($this->once())
			->method('getExtractCommand')
			->willReturn(new Command('extract'))
			->with($this->callback(function  ($pw) {
			return $pw == '';
		}),
				$this->callback(
						function  (AbstractAdapter $source) use ( $fs) {
							return $source->getPathPrefix() == $fs->applyPathPrefix('test/');
						}),
				$this->callback(
						function  (AbstractAdapter $destination) use ( $fs) {
							return $destination->getPathPrefix() == $fs->applyPathPrefix('test1/');
						}));
		$command->expects($this->once())
			->method('isSuccessfullFinished')
			->willReturn(true);

		$fs->write('test/test.txt', 'unit', new Config());
		$command->expects($this->once())
			->method('getFilesToDelete')
			->willReturn([
				'test.txt'
		]);

		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
				]);

		$this->assertContains($fs->applyPathPrefix('test'), trim($commandTester->getDisplay()));
		$this->assertContains('unit test', trim($commandTester->getDisplay()));
		$this->assertFalse($fs->has('test/extract.out'));
	}

	public function testExecuteFailed ()
	{
		$fs = new Local(__DIR__);

		$command = $this->getMockForAbstractClass(ExtractCommand::class,
				[
						$this->getMockDispatcher(),
						$this->getMockRunner([
								$this->anything()
						], [
								'unit test'
						]),
						new Registry()
				]);
		$command->expects($this->once())
			->method('getExtractCommand')
			->willReturn(new Command('extract'));
		$command->expects($this->once())
			->method('isSuccessfullFinished')
			->willReturn(false);

		$command->expects($this->never())
			->method('getFilesToDelete');

		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
				]);

		$this->assertTrue($fs->has('test/extract.out'));
		$this->assertEquals('unit test', $fs->read('test/extract.out')['contents']);
	}

	public function testExecuteWithPasswordFile ()
	{
		$fs = new Local(__DIR__);
		$fs->write('test/pw.txt', 'unitpasswordtest', new Config());

		$command = $this->getMockForAbstractClass(ExtractCommand::class,
				[
						$this->getMockDispatcher(),
						$this->getMockRunner([
								$this->anything(),
								$this->anything()
						]),
						new Registry()
				]);
		$method = $command->expects($this->exactly(2))
			->method('getExtractCommand');
		$method->willReturn(new Command('extract'));
		$this->callWithConsecutive($method,
				[
						[
								$this->callback(function  ($pw) {
									return $pw == '';
								})
						],
						[
								$this->callback(function  ($pw) {
									return $pw == 'unitpasswordtest';
								})
						]
				]);
		$command->expects($this->exactly(2))
			->method('isSuccessfullFinished')
			->will($this->onConsecutiveCalls(false, true));

		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1'),
						'pwfile' => $fs->applyPathPrefix('test/pw.txt')
				]);

		$this->assertFalse($fs->has('test/extract.out'));
	}

	public function testExecuteDispatcher ()
	{
		$fs = new Local(__DIR__);
		$command = $this->getMockForAbstractClass(ExtractCommand::class,
				[
						$this->getMockDispatcher(
								[
										'processing.completed',
										$this->callback(
												function  (ProcessingCompletedEvent $event) {
													return $event->isSuccess();
												})
								]),
						$this->getMockRunner([
								$this->anything()
						]),
						new Registry()
				]);
		$command->expects($this->once())
			->method('getExtractCommand')
			->willReturn(new Command('extract'));
		$command->expects($this->once())
			->method('isSuccessfullFinished')
			->willReturn(true);

		$application = new Application();
		$application->add($command);

		$commandTester = new CommandTester($command);

		$commandTester->execute(
				[
						'command' => $command->getName(),
						'source' => $fs->applyPathPrefix('test'),
						'destination' => $fs->applyPathPrefix('test1')
				]);
	}

	protected function getMockDispatcher ($callbacks = [])
	{
		if (empty($callbacks))
		{
			$callbacks = [
					'processing.completed',
					$this->anything()
			];
		}

		return parent::getMockDispatcher($callbacks);
	}

	protected function tearDown ()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test1');
		$fs->deleteDir('test');
	}
}
