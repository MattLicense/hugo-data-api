<?php
/**
 * Router.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/11
 */

namespace Hugo\Data\Routing;

use Psr\Log\LoggerInterface,
    Hugo\Data\Controller\ControllerFactory,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Hugo\Data\Exception\DuplicateRouteException;

/**
 * Class Router
 * @package Hugo\Data\Routing
 */
class Router implements RouterInterface {

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     * @var \Hugo\Data\Controller\ControllerFactory
     */
    private $controllerFactory;

    /**
     * @var array   Array of Route objects
     */
    protected $routes = array();

    /**
     * @param ControllerFactory $controllerFactory
     * @param LoggerInterface $log
     */
    public function __construct(ControllerFactory $controllerFactory, LoggerInterface $log)
    {
        $this->log = $log;
        $this->controllerFactory = $controllerFactory;
    }

    /**
     * Calls the function matched to the route, either via a defined route in the private $routes array
     * or by using the ControllerFactory to attempt to match the request to a controller
     *
     * @param Request $request
     * @return Response
     */
    public function route(Request $request)
    {
        // defined routes take precedence over controllers
        foreach($this->routes as $route) {
            if($route->matches($request)) {
                return $route->apply($request);
            }
        }

        // if no matching route is found, we'll try match it to a controller
        // if no matching controller is found, NullController is returned.
        $controller = $this->controllerFactory->create($request);
        return $controller->handle();
    }

    /**
     * Defines a new route from a
     *
     * @param $route
     * @param callable $callback
     * @return void
     * @throws \Hugo\Data\Exception\DuplicateRouteException
     */
    public function register($route, callable $callback)
    {
        if(array_key_exists($route, $this->routes))
            throw new DuplicateRouteException("Route {$route} already exists");

        $this->routes[$route] = new Route($route, $callback);
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

}