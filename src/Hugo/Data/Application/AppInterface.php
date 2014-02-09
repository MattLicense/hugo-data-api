<?php
/**
 * AppInterface.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/10
 */

namespace Hugo\Data\Application;

use Symfony\Component\HttpFoundation\Request,
    Hugo\Data\Routing\RouterInterface,
    Psr\Log\LoggerInterface;

interface AppInterface
{

    public function __construct(RouterInterface $router, LoggerInterface $log);

    public function handle(Request $request);

}