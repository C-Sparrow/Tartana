<?php
namespace Tests\Unit\Local\Domain;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Joomla\Registry\Registry;
use Local\Domain\LocalDownloadRepository;
use Tartana\Entity\Download;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use Doctrine\ORM\AbstractQuery;
use Tests\Unit\Tartana\TartanaBaseTestCase;

class LocalDownloadRepositoryTest extends TartanaBaseTestCase
{

	public function testFindAllDownloads ()
	{
		$download = new Download();
		$download->setId(1);
		$download->setLink('http://foo.bar/kjuiew');

		$repository = $this->getMockBuilder(EntityRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repository->expects($this->once())
			->method('findAll')
			->will($this->returnValue([
				$download
		]));

		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$entityManager->expects($this->once())
			->method('getRepository')
			->will($this->returnValue($repository));

		$repository = new LocalDownloadRepository($entityManager,
				new Registry([
						'local' => [
								'downloads' => __DIR__ . '/not-esisting'
						]
				]));

		$downloads = $repository->findDownloads();

		$this->assertNotEmpty($downloads);
		$this->assertCount(1, $downloads);

		$this->assertEquals(1, $downloads[0]->getId());
		$this->assertEquals('http://foo.bar/kjuiew', $downloads[0]->getLink());
	}

	public function testFindDownloadsByState ()
	{
		$download = new Download();
		$download->setId(1);
		$download->setLink('http://foo.bar/kjuiew');
		$download->setState(Download::STATE_DOWNLOADING_COMPLETED);

		$repository = $this->getMockBuilder(EntityRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repository->expects($this->once())
			->method('findBy')
			->will($this->returnValue([
				$download
		]));

		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$entityManager->expects($this->once())
			->method('getRepository')
			->will($this->returnValue($repository));

		$repository = new LocalDownloadRepository($entityManager,
				new Registry([
						'local' => [
								'downloads' => __DIR__ . '/not-esisting'
						]
				]));

		$downloads = $repository->findDownloads(Download::STATE_DOWNLOADING_COMPLETED);

		$this->assertNotEmpty($downloads);
		$this->assertCount(1, $downloads);

		$this->assertEquals(1, $downloads[0]->getId());
		$this->assertEquals('http://foo.bar/kjuiew', $downloads[0]->getLink());
		$this->assertEquals(Download::STATE_DOWNLOADING_COMPLETED, $downloads[0]->getState());
	}

	public function testFindDownloadsByDestination ()
	{
		$download = new Download();
		$download->setId(1);
		$download->setLink('http://foo.bar/kjuiew');
		$download->setDestination(__DIR__);

		$query = $this->getMockBuilder(AbstractQuery::class)
			->disableOriginalConstructor()
			->getMock();
		$query->expects($this->once())
			->method('getResult')
			->willReturn([
				$download
		]);
		$builder = $this->getMockBuilder(QueryBuilder::class)
			->disableOriginalConstructor()
			->getMock();
		$builder->expects($this->once())
			->method('getQuery')
			->willReturn($query);
		$builder->expects($this->once())
			->method('setParameter')
			->with($this->equalTo('destination'), $this->equalTo('%' . __DIR__ . '%'));
		$repository = $this->getMockBuilder(EntityRepository::class)
			->disableOriginalConstructor()
			->getMock();
		$repository->expects($this->once())
			->method('createQueryBuilder')
			->willReturn($builder);

		$entityManager = $this->getMockBuilder(EntityManagerInterface::class)
			->disableOriginalConstructor()
			->getMock();
		$entityManager->expects($this->once())
			->method('getRepository')
			->will($this->returnValue($repository));

		$repository = new LocalDownloadRepository($entityManager,
				new Registry([
						'local' => [
								'downloads' => __DIR__ . '/not-existing'
						]
				]));

		$downloads = $repository->findDownloadsByDestination(__DIR__);

		$this->assertNotEmpty($downloads);
		$this->assertCount(1, $downloads);

		$this->assertEquals(1, $downloads[0]->getId());
		$this->assertEquals('http://foo.bar/kjuiew', $downloads[0]->getLink());
		$this->assertEquals(__DIR__, $downloads[0]->getDestination());
	}
}