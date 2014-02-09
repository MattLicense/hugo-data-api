<?php
/**
 * Created by PhpStorm.
 * User: Matt
 * Date: 11/16/13
 * Time: 4:04 PM
 */

namespace Hugo\Data\Routing;


use Symfony\Component\HttpFoundation\Request;

interface RouterInterface {

    public function route(Request $request);

    public function register($route, callable $callback);

} 