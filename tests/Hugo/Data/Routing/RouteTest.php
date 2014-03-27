<?php
/**
 * RouteTest.php
 * data-api
 * @author: Matt
 * @date:   2013/12
 */

namespace Hugo\Data\Routing;

use Symfony\Component\HttpFoundation\Response;

class RouteTest extends \PHPUnit_Framework_TestCase {

    protected $requestWithArgs;
    protected $requestNoArgs;
    protected $routeNoArgs;
    protected $routeWithArgs;

    protected function setUp()
    {
        $this->requestWithArgs = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\Request', ['getPathInfo'])
                                      ->disableOriginalConstructor()
                                      ->getMock();
        $this->requestWithArgs->expects($this->any())
                              ->method('getPathInfo')
                              ->will($this->returnValue('/test/argument/'));

        $this->requestNoArgs = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\Request', ['getPathInfo'])
                                    ->disableOriginalConstructor()
                                    ->getMock();
        $this->requestNoArgs->expects($this->any())
                            ->method('getPathInfo')
                            ->will($this->returnValue('/testing/string'));

        $this->routeNoArgs = new Route('/testing/string', function() {
            return new Response("Testing");
        });
        $this->routeWithArgs = new Route('/test/:argument', function($argument) {
            return new Response("Testing with {$argument}");
        });
    }

    public function testIsArgument()
    {
        $argumentPart = ':argument';
        $nonArgPart = 'notAnArgument';

        $this->assertTrue($this->routeWithArgs->isArgument($argumentPart));
        $this->assertFalse($this->routeWithArgs->isArgument($nonArgPart));
    }
    public function testMatches()
    {
        // testing the Route with arguments
        $this->assertTrue($this->routeWithArgs->matches($this->requestWithArgs));
        $this->assertFalse($this->routeWithArgs->matches($this->requestNoArgs));

        // testing the Route with no arguments
        $this->assertTrue($this->routeNoArgs->matches($this->requestNoArgs));
        $this->assertFalse($this->routeNoArgs->matches($this->requestWithArgs));
    }

    public function testApply()
    {
        // testing no argument routes
        $this->assertInstanceOf('\\Symfony\\Component\\HttpFoundation\\Response',
                                $this->routeNoArgs->apply($this->requestNoArgs));

        // testing route with arguments
        $this->assertInstanceOf('\\Symfony\\Component\\HttpFoundation\\Response',
                                $this->routeWithArgs->apply($this->requestWithArgs));
    }

    public function testGetArgVals()
    {
        $this->assertEquals(['argument'], $this->routeWithArgs->getArgVals($this->requestWithArgs));
    }

}
 