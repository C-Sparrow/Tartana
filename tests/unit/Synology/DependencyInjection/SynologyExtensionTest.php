<?php
namespace Tests\Unit\Synology\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Synology\DependencyInjection\SynologyExtension;

class SynologyExtensionTest extends AbstractExtensionTestCase
{

	public function testLoadEnabled()
	{
		$this->load(
			[
						'enabled' => true,
						'address' => 'http://localhost:5001',
						'username' => 'admin',
						'password' => 'admin',
						'downloads' => '/path/to/dir',
						'downloadShare' => 'to/share'
			]
		);

		$this->assertContainerBuilderHasParameter(
			'synology.config',
			[
						'enabled' => true,
						'address' => 'http://localhost:5001',
						'username' => 'admin',
						'password' => 'admin',
						'downloads' => '/path/to/dir',
						'downloadShare' => 'to/share'
			]
		);

		$this->assertContainerBuilderHasService('DownloadRepository');
		$this->assertContainerBuilderHasService('SynologyProcessLinksHandler');
		$this->assertContainerBuilderHasService('SynologyProcessCompletedDownloadsHandler');
	}

	public function testLoadDisabled()
	{
		$this->load(
			[
						'enabled' => false,
						'address' => 'http://localhost:5001',
						'username' => 'admin',
						'password' => 'admin',
						'downloads' => '/path/to/dir',
						'downloadShare' => 'to/share'
			]
		);

		$this->assertContainerBuilderHasParameter(
			'synology.config',
			[
						'enabled' => false,
						'address' => 'http://localhost:5001',
						'username' => 'admin',
						'password' => 'admin',
						'downloads' => '/path/to/dir',
						'downloadShare' => 'to/share'
			]
		);

		$this->assertContainerBuilderNotHasService('DownloadRepository');
		$this->assertContainerBuilderNotHasService('SynologyProcessLinksHandler');
		$this->assertContainerBuilderNotHasService('SynologyProcessCompletedDownloadsHandler');
	}

	protected function getContainerExtensions()
	{
		return [
				new SynologyExtension()
		];
	}
}
