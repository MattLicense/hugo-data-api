<?php
/**
 * LoggerTest.php
 * data-api
 * @author: Matt
 * @date:   2013/11
 */

namespace Hugo\Data\Application;

use Psr\Log\Test\LoggerInterfaceTest;

class LoggerTest extends LoggerInterfaceTest {

    protected $logger;
    protected $store;

    protected function setUp()
    {
        $this->store = $this->getMockBuilder('\\Hugo\\Data\\Storage\\FileSystem', ['write'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $this->store->expects($this->any())
                    ->method('write')
                    ->will($this->returnValue(true));

        $this->logger = new Logger($this->store);
    }

    protected function tearDown()
    {
        $this->logger->resetLogs();
    }

    public function getLogs()
    {
        return $this->logger->getLogs();
    }

    public function getLogger()
    {
        return $this->logger;
    }

}
 