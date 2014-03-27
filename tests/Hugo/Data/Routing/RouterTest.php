<?php
/**
 * RouterTest.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/09
 */

namespace Hugo\Data\Routing;

use Symfony\Component\HttpFoundation\Response;

class RouterTest extends \PHPUnit_Framework_TestCase {

    private $controllerFactory;
    private $log;

    protected function setUp()
    {
        $testResponse = new Response('No content here');
        $nullController = $this->getMockBuilder('\\Hugo\\Data\\Controller\\NullController', ['handle'])
                               ->disableOriginalConstructor()
                               ->getMock();
        $nullController->expects($this->any())
                       ->method('handle')
                       ->will($this->returnValue($testResponse));

        $this->controllerFactory = $this->getMockBuilder('\\Hugo\\Data\\Controller\\ControllerFactory', ['create'])
                                        ->disableOriginalConstructor()
                                        ->getMock();
        $this->controllerFactory->expects($this->any())
                                ->method('create')
                                ->will($this->returnValue($nullController));


        $this->log = $this->getMockBuilder('\\Hugo\\Data\\Application\\Logger')
                          ->disableOriginalConstructor()
                          ->getMock();
    }

    public function testRegister()
    {
        $router = new Router($this->controllerFactory, $this->log);
        $this->assertCount(0, $router->getRoutes());
        $router->register("test/", function() {
            return new Response("test");
        });

        $this->assertCount(1, $router->getRoutes());
        $this->assertArrayHasKey("test/", $router->getRoutes());
        $this->assertInstanceOf('\\Hugo\\Data\\Routing\\Route', $router->getRoutes()['test/']);
    }

    public function testRoute()
    {
        $router = new Router($this->controllerFactory, $this->log);
        $router->register("test/", function() {
            return new Response("test");
        });

        $request = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\Request', ['getPathInfo'])
                        ->disableOriginalConstructor()
                        ->getMock();
        $request->expects($this->any())
                ->method('getPathInfo')
                ->will($this->returnValue('/test/'));

        // testing defined route
        $response = $router->route($request);
        $this->assertInstanceOf('\\Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertEquals('test', $response->getContent());

        // testing mock NullController
        $request = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\Request', ['getPathInfo'])
            ->disableOriginalConstructor()
            ->getMock();
        $request->expects($this->any())
            ->method('getPathInfo')
            ->will($this->returnValue('/testing/'));

        // testing defined route
        $response = $router->route($request);
        $this->assertInstanceOf('\\Symfony\\Component\\HttpFoundation\\Response', $response);
        $this->assertEquals('No content here', $response->getContent());
    }

}
