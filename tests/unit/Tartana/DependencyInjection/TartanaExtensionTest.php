<?php
namespace Tests\Unit\Tartana\DependencyInjection;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Tartana\DependencyInjection\TartanaExtension;

class TartanaExtensionTest extends AbstractExtensionTestCase
{

	public function testLoadEnabled ()
	{
		$this->load(
				[
						'links' => [
								'folder' => '/path/to/folder',
								'convertToHttps' => true,
								'hostFilter' => 'foo.com'
						],
						'extract' => [
								'destination' => '/path/to/folder',
								'passwordFile' => '/path/to/folder/pw.txt',
								'deleteFiles' => true
						],
						'sound' => [
								'destination' => '/path/to/folder',
								'hostFilter' => 'foo.com'
						]
				]);

		$this->assertContainerBuilderHasParameter('tartana.config',
				[
						'links' => [
								'folder' => '/path/to/folder',
								'convertToHttps' => true,
								'hostFilter' => 'foo.com'
						],
						'extract' => [
								'destination' => '/path/to/folder',
								'passwordFile' => '/path/to/folder/pw.txt',
								'deleteFiles' => true
						],
						'sound' => [
								'destination' => '/path/to/folder',
								'hostFilter' => 'foo.com'
						]
				]);

		$this->assertContainerBuilderHasService('config');
		$this->assertContainerBuilderHasService('CommandBus');
		$this->assertContainerBuilderHasService('EventDispatcher');
		$this->assertContainerBuilderHasService('HostFactory');
		$this->assertContainerBuilderHasService('CommandRunner');
		$this->assertContainerBuilderHasService('DecrypterFactory');
		$this->assertContainerBuilderHasService('ClientInterface');
		$this->assertContainerBuilderHasService('LogRepository');

		// Commands
		$this->assertContainerBuilderHasService('Tartana.DefaultCommand');
		$this->assertContainerBuilderHasService('Tartana.UnrarCommand');
		$this->assertContainerBuilderHasService('Tartana.UnzipCommand');
		$this->assertContainerBuilderHasService('Tartana.SevenzCommand');
		$this->assertContainerBuilderHasService('Tartana.ConvertSoundCommand');
		$this->assertContainerBuilderHasService('Tartana.ServerCommand');
		$this->assertContainerBuilderHasService('Tartana.DownloadControlCommand');
		$this->assertContainerBuilderHasService('Tartana.UpdateCommand');

		// Handlers
		$this->assertContainerBuilderHasService('ParseLinksHandler');
		$this->assertContainerBuilderHasService('ProcessCompletedDownloadsHandler');
		$this->assertContainerBuilderHasService('ChangeDownloadStateHandler');
		$this->assertContainerBuilderHasService('DeleteFileLogsHandler');
		$this->assertContainerBuilderHasService('SaveParametersHandler');

		// Listeners
		$this->assertContainerBuilderHasService('ExtractListener.Start');
		$this->assertContainerBuilderHasService('ExtractListener.Finish');
		$this->assertContainerBuilderHasService('ExtractListener.Command');
		$this->assertContainerBuilderHasService('SoundConverterListener.Start');
		$this->assertContainerBuilderHasService('SoundConverterListener.Finish');
		$this->assertContainerBuilderHasService('SoundConverterListener.Command');
		$this->assertContainerBuilderHasService('ProcessLinksListener.Command');
		$this->assertContainerBuilderHasService('ConsoleExceptionListener');
		$this->assertContainerBuilderHasService('UpdateExtractStateListener.Progress');
		$this->assertContainerBuilderHasService('UpdateExtractStateListener.Finish');

		// Middleware
		$this->assertContainerBuilderHasService('MessageBusIgnoreNoHandler');
		$this->assertContainerBuilderHasService('MessageBusEventDispatcher');

		// Security
		$this->assertContainerBuilderHasService('wsse.security.authentication.provider');
		$this->assertContainerBuilderHasService('wsse.security.authentication.listener');
	}

	protected function getContainerExtensions ()
	{
		return [
				new TartanaExtension()
		];
	}
}