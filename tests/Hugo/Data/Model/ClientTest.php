<?php
/**
 * ClientTest.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2014/02
 */

namespace Hugo\Data\Model;


class ClientTest extends \PHPUnit_Framework_TestCase {

    public $goodParamBag;
    public $badParamBag;
    public $mockStore;

    public function setUp()
    {
        $this->goodParamBag = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\ParameterBag', ['get'])
                                   ->disableOriginalConstructor()
                                   ->getMock();
        $this->goodParamBag->expects($this->any())
                           ->method('get')
                           ->will($this->returnCallback([$this,'parameterBagSuccess']));
        $this->badParamBag = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\ParameterBag', ['get'])
                                  ->disableOriginalConstructor()
                                  ->getMock();
        $this->badParamBag->expects($this->any())
                          ->method('get')
                          ->will($this->returnCallback([$this,'parameterBagFailure']));
        $this->mockStore = $this->getMockBuilder('\\Hugo\\Data\\Storage\\DB\\MySQL')
                                ->disableOriginalConstructor()
                                ->getMock();
    }

    public function parameterBagSuccess()
    {
        $args = func_get_args();

        switch($args[0]) {
            case 'client_name':
                return "Sport England";
                break;
            case 'client_website':
                return "http://www.sportengland.co.uk";
                break;
            case 'contact_name':
                return "John Smith";
                break;
            case 'contact_phone':
                return "07777777777";
                break;
            case 'contact_email':
                return "john@sportengland.co.uk";
                break;
            default:
                return;
        }
    }

    public function parameterBagFailure()
    {
        $args = func_get_args();

        switch($args[0]) {
            case 'client_website':
                return "http://www.sportengland.co.uk";
                break;
            case 'contact_name':
                return "John Smith";
                break;
            case 'contact_phone':
                return "07777777777";
                break;
            case 'contact_email':
                return "john@sportengland.co.uk";
                break;
            default:
                return;
        }
    }

    public function testProcessParameters()
    {
        $client = new Client($this->mockStore);
        $this->assertTrue($client->processParameters($this->goodParamBag));

        $this->assertEquals('Sport England', $client->client_name);
        $this->assertEquals('http://www.sportengland.co.uk', $client->client_website);
        $this->assertEquals('John Smith', $client->contact_name);
        $this->assertEquals('07777777777', $client->contact_phone);
        $this->assertEquals('john@sportengland.co.uk', $client->contact_email);
    }

    /**
     * @expectedException \Hugo\Data\Exception\InvalidRequestException
     */
    public function testProcessParametersExceptionThrown()
    {
        $client = new Client($this->mockStore);
        $client->processParameters($this->badParamBag);
    }

}
 