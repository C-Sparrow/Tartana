<?php
namespace Tests\Unit\Tartana\Domain\Command;

use Tartana\Entity\Base;

class BaseTest extends \PHPUnit_Framework_TestCase
{

	public function testEmptyBase()
	{
		$entity = new Base();

		$this->assertEmpty($entity->jsonSerialize());
		$this->assertEmpty($entity->toArray());
	}

	public function testBaseWithFields()
	{
		$entity       = new Base();
		$entity->test = 'hello';

		$expected = [
			'test' => 'hello'
		];
		$this->assertNotEmpty($entity->jsonSerialize());
		$this->assertEquals($expected, $entity->jsonSerialize());
		$this->assertNotEmpty($entity->toArray());
		$this->assertEquals($expected, $entity->toArray());
	}
}
