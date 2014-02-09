<?php
/**
 * ControllerFactory.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/09
 */

namespace Hugo\Data\Controller;

use Symfony\Component\HttpFoundation\Request;

class ControllerFactory {

    protected $request;

    public function create(Request $request)
    {
        $type = explode('/', trim($request->getPathInfo(), '/'))[0];

        $controllerClass = Constants::CONTROLLER_NS . '\\' . ucfirst($type) . 'Controller';

        if(class_exists($controllerClass)) { // see if a relevant controller exists
            $controller = new $controllerClass($request);
        } else { // NullController returns 404
            $controller = new NullController($request);
        }

        return $controller;
    }
}