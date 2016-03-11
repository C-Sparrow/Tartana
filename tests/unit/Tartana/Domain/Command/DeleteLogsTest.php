<?php
namespace Tests\Unit\Tartana\Domain\Command;
use Tartana\Domain\Command\DeleteLogs;

class DeleteLogsTest extends \PHPUnit_Framework_TestCase
{

	public function testDeleteLogsCommand ()
	{
		$command = new DeleteLogs();
	}
}