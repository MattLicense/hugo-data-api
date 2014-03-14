<?php
/**
 * index.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/09
 */

require_once("../vendor/autoload.php");

use Hugo\Data\Controller\ControllerFactory,
    Hugo\Data\Application\Logger,
    Hugo\Data\Storage\FileSystem,
    Hugo\Data\Routing\Router,
    Hugo\Data\API;

$log = new Logger(new FileSystem('/media/vagrant/www/api.hugowolferton.co.uk/logs/api.log'));
$router = new Router(new ControllerFactory(), $log);

$api = new API($router, $log);

$api->register("/", function() {
    return new \Symfony\Component\HttpFoundation\Response(
        json_encode(['api' => [
            'access-point'  => 'https://api.hugowolferton.co.uk'
        ,   'documentation' => 'http://docs.api.hugowolferton.co.uk'
        ,   'description'   => 'Hugo Wolferton Data Analysis API'
        ,   'version'       => '1.0'
        ]], JSON_PRETTY_PRINT),
        200,
        ['Content-Type' => 'application/json;charset=utf-8']
    );
});

$api->run();