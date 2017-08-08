<?php
namespace Tests\Connextion\Local\Domain;

use Joomla\Registry\Registry;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Tartana\Entity\Download;
use Synology\Domain\SynologyDownloadRepository;

class SynologyDownloadRepositoryTest extends WebTestCase
{

	public function testFindDownloads()
	{
		if (!file_exists(TARTANA_PATH_ROOT . '/app/config/test_synology.yml')) {
			$this->markTestSkipped('No synology configuration found for real testing');
			return;
		}

		$config = new Registry();
		$config->loadFile(TARTANA_PATH_ROOT . '/app/config/test_synology.yml', 'yaml');
		$config->set('downloads', $config->get('synology.downloads'));

		$client     = static::createClient([
			'environment' => 'test_synology'
		]);
		$repository = new SynologyDownloadRepository($client->getContainer()->get('ClientInterface'), $config);
		$downloads  = $repository->findDownloads();

		foreach ($downloads as $download) {
			$this->assertNotEmpty($download->getId());
			$this->assertNotEmpty($download->getLink());
			$this->assertNotEmpty($download->getDestination());
		}
	}
}
