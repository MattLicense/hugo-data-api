<?php
/**
 * ControllerFactoryTest.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/09
 */

namespace Hugo\Data\Controller;

class ControllerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public $controllerFactory;

    public function setUp()
    {
        $this->controllerFactory = new ControllerFactory();
    }

    public function testCreateController()
    {
        $request = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\Request', array('getPathInfo'))
                        ->getMock();

        $controller = $this->controllerFactory->create($request);
        $this->assertInstanceOf('\\Hugo\\Data\\Controller\\ControllerInterface', $controller);
    }

    public function testControllerMappings()
    {
        $authRequest = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\Request', array('getPathInfo'))->getMock();
        $authRequest->expects($this->once())
                    ->method('getPathInfo')
                    ->will($this->returnValue('/auth/token'));

        $authController = $this->controllerFactory->create($authRequest);
        $this->assertInstanceOf('\\Hugo\\Data\\Controller\\AuthController', $authController);

        $clientRequest = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\Request', array('getPathInfo'))->getMock();
        $clientRequest->expects($this->once())
                      ->method('getPathInfo')
                      ->will($this->returnValue('/client/'));

        $clientController = $this->controllerFactory->create($clientRequest);
        $this->assertInstanceOf('\\Hugo\\Data\\Controller\\ClientController', $clientController);

        $reportRequest = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\Request', array('getPathInfo'))->getMock();
        $reportRequest->expects($this->once())
                      ->method('getPathInfo')
                      ->will($this->returnValue('/report/'));

        $reportController = $this->controllerFactory->create($reportRequest);
        $this->assertInstanceOf('\\Hugo\\Data\\Controller\\ReportController', $reportController);

        $errorRequest = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\Request', array('getPathInfo'))->getMock();
        $errorRequest->expects($this->once())
                     ->method('getPathInfo')
                     ->will($this->returnValue('/undefined/'));

        $errorController = $this->controllerFactory->create($errorRequest);
        $this->assertInstanceOf('\\Hugo\\Data\\Controller\\NullController', $errorController);
    }

}