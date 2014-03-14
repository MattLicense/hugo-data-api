<?php
/**
 * LogItemTest.php
 * data-api
 * @author: Matt
 * @date:   2013/12
 */

namespace Hugo\Data\Model;
require_once(__DIR__."/../../../../vendor/autoload.php");

class LogItemTest extends \PHPUnit_Framework_TestCase {

    protected $store;

    protected $config = [
        'level'     => 'warning',
        'message'   => 'Warning message with context {context} given',
        'context'   => ['context' => 'like this']
    ];

    public function testLogLevel()
    {
        $logItem = new LogItem($this->store);
        $logItem->set($this->config);

        $this->assertEquals($this->config['level'], $logItem->getLogLevel());
    }

    public function testLogMessage()
    {
        $logItem = new LogItem($this->store);
        $logItem->set($this->config);

        $this->assertEquals('Warning message with context like this given', $logItem->getMessage());
    }

    public function testToArray()
    {
        $logItem = new LogItem($this->store);
        $logItem->set($this->config);

        $date = new \DateTime('now', new \DateTimeZone("Europe/London"));
        $expected = ['date' => $date->format('Y-m-d H:i:s'),
                     'level' => 'WARNING',
                     'message' => 'Warning message with context like this given'];

        $this->assertEquals($expected, $logItem->toArray());
    }

    public function testToString()
    {
        $logItem = new LogItem($this->store);
        $logItem->set($this->config);

        $date = new \DateTime('now', new \DateTimeZone("Europe/London"));
        $expected = $date->format('Y-m-d H:i:s') .' - WARNING - Warning message with context like this given';

        $this->assertEquals($expected, (string)$logItem);
    }

}
 