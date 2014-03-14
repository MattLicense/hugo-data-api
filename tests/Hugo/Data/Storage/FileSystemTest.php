<?php
/**
 * FileSystemTest.php
 * data-api
 * @author: Matt
 * @date:   2013/12
 */

namespace Hugo\Data\Storage;
require_once(__DIR__."/../../../../vendor/autoload.php");

class FileSystemTest extends \PHPUnit_Framework_TestCase {

    private $file = '/media/vagrant/www/api.hugowolferton.co.uk/logs/test.log';

    protected function setUp()
    {
        if(file_exists($this->file)) {
            unlink($this->file);
        }
    }

    protected function tearDown()
    {
        if(file_exists($this->file)) {
            unlink($this->file);
        }
    }

    public function testCreate()
    {
        $mockModel = $this->getMockBuilder('\\Hugo\\Data\\Model\\LogItem', ['__toString'])
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->expects($this->any())
                  ->method('__toString')
                  ->will($this->returnValue('Test FileSystem::write()'));

        $fileSystem = new FileSystem($this->file);

        $this->assertTrue($fileSystem->create($mockModel));
        $this->assertFileExists($this->file);
        $this->assertStringEqualsFile($this->file, 'Test FileSystem::write()');
    }

    public function testReadLastLine()
    {
        $mockModel = $this->getMockBuilder('\\Hugo\\Data\\Model\\LogItem', ['__toString'])
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->expects($this->any())
                  ->method('__toString')
                  ->will($this->returnValue('Test FileSystem::read()'));

        $fileSystem = new FileSystem($this->file);
        $fileSystem->create($mockModel);
        $line = $fileSystem->read();    // read the last line

        $this->assertEquals('Test FileSystem::read()', $line);
    }

    public function testReadSpecificLine()
    {
        $mockModel = $this->getMockBuilder('\\Hugo\\Data\\Model\\LogItem', ['__toString'])
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->expects($this->any())
                  ->method('__toString')
                  ->will($this->returnValue('Test FileSystem::read() Line 1'.PHP_EOL.'Test FileSystem::read() Line 2'));

        $fileSystem = new FileSystem($this->file);
        $fileSystem->create($mockModel);
        $line = trim($fileSystem->read(0));
        $this->assertEquals('Test FileSystem::read() Line 1', $line);
    }

    public function testUpdate()
    {
        $mockModel = $this->getMockBuilder('\\Hugo\\Data\\Model\\LogItem', ['__toString'])
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->expects($this->any())
                  ->method('__toString')
                  ->will($this->returnValue('Test FileSystem::insert() Line 1'));

        $mockModel2 = $this->getMockBuilder('\\Hugo\\Data\\Model\\LogItem', ['__toString'])
                           ->disableOriginalConstructor()
                           ->getMock();
        $mockModel2->expects($this->any())
                   ->method('__toString')
                   ->will($this->returnValue('Test FileSystem::insert() Line 2'));

        $fileSystem = new FileSystem($this->file);
        $fileSystem->create($mockModel);
        $this->assertTrue($fileSystem->update($mockModel2));
        $this->assertEquals(file_get_contents($this->file),
                            'Test FileSystem::insert() Line 1'.PHP_EOL.'Test FileSystem::insert() Line 2');
    }

    public function testDelete()
    {
        $mockModel = $this->getMockBuilder('\\Hugo\\Data\\Model\\LogItem', ['__toString'])
                          ->disableOriginalConstructor()
                          ->getMock();
        $mockModel->expects($this->any())
                  ->method('__toString')
                  ->will($this->returnValue('Test FileSystem::delete()'));

        $fileSystem = new FileSystem($this->file);
        $fileSystem->create($mockModel);
        $this->assertTrue($fileSystem->delete());
        $this->assertFileNotExists($this->file);
    }

    /**
     * @expectedException \Hugo\Data\Exception\IOException
     */
    public function testDeleteException()
    {
        $mockModel = $this->getMockBuilder('\\Hugo\\Data\\Model\\LogItem', ['__toString'])
                          ->disableOriginalConstructor()
                          ->getMock();

        $fileSystem = new FileSystem($this->file);
        $fileSystem->delete($mockModel);
    }

}
 