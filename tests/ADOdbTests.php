<?php declare(strict_types=1);
require 'vendor/autoload.php';
$loader = new Riimu\Kit\ClassLoader\ClassLoader();
$loader->addBasePath('/home/mark/v6/vendor/mnewnham/adodb/src');
$loader->register();

use PHPUnit\Framework\TestCase;

final class ADOdbTests extends TestCase
{
    
	protected function setUp(): void
    {
		$this->dtd = new \ADOdb\common\ADODateTimeDefinitions;
        $this->acd = new \ADOdb\ADOConnectionDefinitions;
    }
	
	public function testDateTimeIsObject()
	{
		$result = is_object($this->dtd);
		$this->assertEquals(true,$result);
		
	}
	
	public function testConnectionDefinitionsIsObject()
	{

		$result = is_object($this->acd);
		$this->assertEquals(true,$result);
	}
	
	protected function tearDown() : void
	{
		$this->acd = null;
	}

/*
    public function testCannotBeCreatedFromInvalidEmailAddress(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Email::fromString('invalid');
    }

    public function testCanBeUsedAsString(): void
    {
        $this->assertEquals(
            'user@example.com',
            Email::fromString('user@example.com')
        );
    }
	*/
}

