<?php
namespace Local\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Tartana\Entity\Download;

/**
 * @codeCoverageIgnore
 */
class LoadDownloadData implements FixtureInterface
{

	public function load(ObjectManager $manager)
	{
		// Not started download
		$download = new Download();
		$download->setLink('http://foo.bar/notstarted');
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$manager->persist($download);

		// Started download
		$download = new Download();
		$download->setLink('http://foo.bar/started');
		$download->setState(Download::STATE_DOWNLOADING_STARTED);
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$manager->persist($download);

		// Completed download
		$download = new Download();
		$download->setLink('http://foo.bar/completed');
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$manager->persist($download);

		// Error download
		$download = new Download();
		$download->setLink('http://foo.bar/error');
		$download->setState(Download::STATE_DOWNLOADING_ERROR);
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$manager->persist($download);

		// Not started to process download
		$download = new Download();
		$download->setLink('http://foo.bar/processingnotstarted');
		$download->setState(Download::STATE_PROCESSING_NOT_STARTED);
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$manager->persist($download);

		// Started to process download
		$download = new Download();
		$download->setLink('http://foo.bar/processingstarted');
		$download->setState(Download::STATE_PROCESSING_STARTED);
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$manager->persist($download);

		// Process download
		$download = new Download();
		$download->setLink('http://foo.bar/processingcompleted');
		$download->setState(Download::STATE_PROCESSING_COMPLETED);
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test/processingcompleted');
		$manager->persist($download);

		// Error on process download
		$download = new Download();
		$download->setLink('http://foo.bar/processerror');
		$download->setState(Download::STATE_PROCESSING_ERROR);
		$download->setDestination(TARTANA_PATH_ROOT . '/var/tmp/test');
		$manager->persist($download);

		$manager->flush();
	}
}
