<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 13/11/13
 * Time: 23:18
 */

namespace Hugo\Data\Routing;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Route
 *
 * Note: $callback should return an instance of Response
 *
 * @package Hugo\Data\Routing
 */
class Route {

    private $route;
    private $callback;

    private $routeParts = [];
    private $routeArgs  = [];

    public function __construct($route, callable $callback)
    {
        $this->route = trim($route, '/');
        $this->callback = $callback;
        $this->routeParts = explode('/', $this->route);

        foreach($this->routeParts as $part) {
            if($this->isArgument($part)) { // arguments in routes given by /:argument/
                $this->routeArgs[] = substr($part,0,1);
            }
        }
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function apply(Request $request)
    {
        $arguments = $this->getArgVals($request);

        return call_user_func_array($this->callback, $arguments);
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function matches(Request $request)
    {
        $matches = true;
        $requestParts = explode('/', trim($request->getPathInfo(), '/'));

        for($i = 0; $i < count($this->routeParts); ++$i) {
            // if the Route part isn't an argument, then the Request part should match the current Route part.
            if(!$this->isArgument($this->routeParts[$i]) && $this->routeParts[$i] != $requestParts[$i]) {
                $matches = false;
                break;
            }
        }

        return $matches;
    }

    /**
     * @param $routePart
     * @return bool
     */
    public function isArgument($routePart)
    {
        return (substr($routePart,0,1) === ":");
    }

    /**
     * @param Request $request
     * @return array
     */
    public function getArgVals(Request $request)
    {
        $args = [];
        $requestParts = explode('/', trim($request->getPathInfo(), '/'));

        for($i = 0; $i < count($requestParts); ++$i) {
            if($this->isArgument($this->routeParts[$i])) {
                $args[] = $requestParts[$i];
            }
        }

        return $args;
    }

} 