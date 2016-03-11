<?php
namespace Tests\Unit\Local\DependencyInjection;
use Local\DependencyInjection\LocalExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;

class LocalExtensionTest extends AbstractExtensionTestCase
{

	public function testLoadEnabled ()
	{
		$this->load([
				'enabled' => true,
				'downloads' => '/path/to/folder'
		]);

		$this->assertContainerBuilderHasParameter('local.config', [
				'enabled' => true,
				'downloads' => '/path/to/folder'
		]);

		$this->assertContainerBuilderHasService('DownloadRepository');
		$this->assertContainerBuilderHasService('Local.DownloadCommand');
		$this->assertContainerBuilderHasService('LocalDeleteDownloadsHandler');
		$this->assertContainerBuilderHasService('LocalProcessLinksHandler');
		$this->assertContainerBuilderHasService('LocalSaveDownloadsHandler');
		$this->assertContainerBuilderHasService('LocalStartDownloadsHandler');
	}

	public function testLoadDisabled ()
	{
		$this->load([
				'enabled' => false,
				'downloads' => '/path/to/folder'
		]);

		$this->assertContainerBuilderHasParameter('local.config', [
				'enabled' => false,
				'downloads' => '/path/to/folder'
		]);

		$this->assertContainerBuilderNotHasService('DownloadRepository');
		$this->assertContainerBuilderNotHasService('Local.DownloadCommand');
		$this->assertContainerBuilderNotHasService('LocalDeleteDownloadsHandler');
		$this->assertContainerBuilderNotHasService('LocalProcessLinksHandler');
		$this->assertContainerBuilderNotHasService('LocalSaveDownloadsHandler');
		$this->assertContainerBuilderNotHasService('LocalStartDownloadsHandler');
		$this->assertContainerBuilderNotHasService('UpdateExtractStateListener.Progress');
		$this->assertContainerBuilderNotHasService('UpdateExtractStateListener.Finish');
	}

	protected function getContainerExtensions ()
	{
		return [
				new LocalExtension()
		];
	}
}