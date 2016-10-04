<?php
namespace Tests\Unit\Tartana\Mixins;

class JsonSerializableTraitTest extends \PHPUnit_Framework_TestCase
{

	public function testEmpty()
	{
		$object = $this->getObjectForTrait('Tartana\Mixins\JsonSerializableTrait');

		$this->assertEquals([], $object->jsonSerialize());
	}

	public function testFields()
	{
		$object = $this->getObjectForTrait('Tartana\Mixins\JsonSerializableTrait');
		$object->test = 'hello';

		$this->assertNotEmpty($object->jsonSerialize());
		$this->assertEquals([
				'test' => 'hello'
		], $object->jsonSerialize());
	}
}
