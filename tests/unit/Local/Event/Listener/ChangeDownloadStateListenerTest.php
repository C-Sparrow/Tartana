<?php
namespace Tests\Unit\Local\Event\Listener;

use Joomla\Registry\Registry;
use League\Flysystem\Adapter\Local;
use Local\Event\Listener\ChangeDownloadStateListener;
use Tartana\Domain\Command\ChangeDownloadState;
use Tartana\Domain\Command\DeleteDownloads;
use Tartana\Entity\Download;
use Tartana\Event\CommandEvent;
use Tests\Unit\Local\LocalBaseTestCase;

class ChangeDownloadStateListenerTest extends LocalBaseTestCase
{

	public function testCorrectInvalidPath()
	{
		$fs = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setDestination(__DIR__ . 'invalid/unit');
		$download1 = new Download();
		$download1->setDestination($fs->applyPathPrefix('unit1'));

		$listener = new ChangeDownloadStateListener(new Registry([
				'downloads' => $fs->getPathPrefix()
		]));
		$listener->onChangeDownloadStateAfter(
			new CommandEvent(
				new ChangeDownloadState(
					[
								$download,
								$download1
						],
					Download::$STATES_ALL,
					Download::STATE_DOWNLOADING_NOT_STARTED
				)
			)
		);

						$this->assertEquals($fs->applyPathPrefix('unit'), $download->getDestination());
						$this->assertEquals($fs->applyPathPrefix('unit1'), $download1->getDestination());
	}

	public function testCorrectInvalidPathWrongState()
	{
		$fs = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setDestination(__DIR__ . 'invalid/unit');

		$listener = new ChangeDownloadStateListener(new Registry([
				'downloads' => $fs->getPathPrefix()
		]));
		$listener->onChangeDownloadStateAfter(
			new CommandEvent(new ChangeDownloadState(
				[
						$download
				],
				Download::$STATES_ALL,
				Download::STATE_DOWNLOADING_COMPLETED
			))
		);

				$this->assertEquals(__DIR__ . 'invalid/unit', $download->getDestination());
	}

	public function testCorrectInvalidPathWrongCommand()
	{
		$fs = new Local(__DIR__ . '/test');

		$download = new Download();
		$download->setDestination(__DIR__ . 'invalid/unit');

		$listener = new ChangeDownloadStateListener(new Registry([
				'downloads' => $fs->getPathPrefix()
		]));
		$listener->onChangeDownloadStateAfter(new CommandEvent(new DeleteDownloads([
				$download
		])));

		$this->assertEquals(__DIR__ . 'invalid/unit', $download->getDestination());
	}

	public function testCorrectInvalidPathWrongStateWrongDestination()
	{
		$download = new Download();
		$download->setDestination(__DIR__ . 'invalid/unit');

		$listener = new ChangeDownloadStateListener(new Registry([
				'downloads' => __DIR__ . '/wrong'
		]));
		$listener->onChangeDownloadStateAfter(
			new CommandEvent(new ChangeDownloadState(
				[
						$download
				],
				Download::$STATES_ALL,
				Download::STATE_DOWNLOADING_NOT_STARTED
			))
		);

				$this->assertEquals(__DIR__ . 'invalid/unit', $download->getDestination());
	}

	protected function tearDown()
	{
		$fs = new Local(__DIR__);
		$fs->deleteDir('test/');
	}
}
