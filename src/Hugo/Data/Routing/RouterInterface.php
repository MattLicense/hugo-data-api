<?php
/**
 * RouterInterface.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/11
 */

namespace Hugo\Data\Routing;


use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/**
 * Interface RouterInterface
 * @package Hugo\Data\Routing
 */
interface RouterInterface {

    /**
     * @param Request $request
     * @return Response
     */
    public function route(Request $request);

    /**
     * @param $route
     * @param callable $callback
     * @return void
     */
    public function register($route, callable $callback);

} 