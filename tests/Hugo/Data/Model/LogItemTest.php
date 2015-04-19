<?php
/**
 * LogItemTest.php
 * data-api
 * @author: Matt
 * @date:   2013/12
 */

namespace Hugo\Data\Model;

class LogItemTest extends \PHPUnit_Framework_TestCase {

    protected $store;
    
    public function setUp()
    {
        $this->store = $this->getMockBuilder('\\Hugo\\Data\\Storage\\FileSystem', ['write'])
                            ->disableOriginalConstructor()
                            ->getMock();
        $this->store->expects($this->any())
                    ->method('write')
                    ->will($this->returnValue(true));
    }

    /**
     * @return array
     */
    public function logProvider()
    {
        return [
            [
                ['level' => 'warning','message' => 'Warning message with context {context} given','context' => ['context' => 'like this']],
                'Warning message with context like this given'
            ],
            [
                ['level' => 'emergency','message' => 'Emergency message without context', 'context' => []],
                'Emergency message without context'
            ],
            [
                ['level' => 'info','message' => 'Info message with {context} not given', 'context' => []],
                'Info message with {context} not given'
            ],
            [
                ['level' => 'debug','message' => 'Debug message with {context} given', 'context' => ['context' => 'some context']],
                'Debug message with some context given'
            ]
        ];
    }

    /**
     * @dataProvider logProvider
     */
    public function testLogLevel($logArray, $expectedMessage)
    {
        $logItem = new LogItem($this->store);
        $logItem->set($logArray);

        $this->assertEquals($logArray['level'], $logItem->getLogLevel());
    }

    /**
     * @dataProvider logProvider
     */
    public function testLogMessage($logArray, $expectedMessage)
    {
        $logItem = new LogItem($this->store);
        $logItem->set($logArray);

        $this->assertEquals($expectedMessage, $logItem->getMessage());
    }

    /**
     * @dataProvider logProvider
     */
    public function testToArray($logArray, $expectedMessage)
    {
        $logItem = new LogItem($this->store);
        $logItem->set($logArray);

        $date = new \DateTime('now', new \DateTimeZone("Europe/London"));
        $expected = ['date' => $date->format('Y-m-d H:i:s'),
                     'level' => strtoupper($logArray['level']),
                     'message' => $expectedMessage];

        $this->assertEquals($expected, $logItem->toArray());
    }

    /**
     * @dataProvider logProvider
     */
    public function testToString($logArray, $expectedMessage)
    {
        $logItem = new LogItem($this->store);
        $logItem->set($logArray);

        $date = new \DateTime('now', new \DateTimeZone("Europe/London"));
        $expected = $date->format('Y-m-d H:i:s') .' - ' . strtoupper($logArray['level']) . ' - ' . $expectedMessage;

        $this->assertEquals($expected, (string)$logItem);
    }

}
 
