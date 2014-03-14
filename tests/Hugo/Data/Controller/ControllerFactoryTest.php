<?php
/**
 * ControllerFactoryTest.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/09
 */

namespace Hugo\Data\Controller;
require_once(__DIR__."/../../../../vendor/autoload.php");

class ControllerFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testCreateController()
    {
        $controllerFactory = new ControllerFactory();

        $request = $this->getMockBuilder('\\Symfony\\Component\\HttpFoundation\\Request', array('getPathInfo'))
                        ->getMock();

        $controller = $controllerFactory->create($request);
        $this->assertInstanceOf('\\Hugo\\Data\\Controller\\ControllerInterface', $controller);
    }

}