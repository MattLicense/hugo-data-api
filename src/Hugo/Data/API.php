<?php
/**
 * API.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/09
 */

namespace Hugo\Data;

use Psr\Log\LoggerInterface,
    Hugo\Data\Storage\DB\MySQL,
    Hugo\Data\Routing\RouterInterface,
    Hugo\Data\Application\AppInterface,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

class API implements AppInterface, RouterInterface
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $log;

    /**
     * @var \Hugo\Data\Routing\RouterInterface
     */
    private $router;

    /**
     * @param \Hugo\Data\Routing\RouterInterface $router
     * @param \Psr\Log\LoggerInterface $log
     */
    public function __construct(RouterInterface $router, LoggerInterface $log)
    {
        $this->log = $log;
        $this->router = $router;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function run(Request $request = null)
    {
        if(null === $request) {
            $request = Request::createFromGlobals();
        } else {
            $this->log->debug('Request made from {req}', ['req' => $request->getPathInfo()]);
        }

        return $this->handle($request)->send();
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        try {
            $response = $this->router->route($request);
        } catch(\Exception $e) {
            $code = ($e->getCode() < 200 || $e->getCode() > 510) ? 500 : $e->getCode();    // make sure that the exception code is a valid HTTP code
            $this->log->error(get_class($e). ' ['. $code .'] - ' . $e->getMessage());
            $response = new Response(json_encode([  'error' => $e->getMessage(),
                                                    'response-code' => $code,
                                                    'exception' => get_class($e)], JSON_PRETTY_PRINT),
                                     $code,
                                     ['Content-Type' => 'application/json;charset=utf-8']);
        }

        return $response;
    }

    /**
     * @param $route
     * @param callable $callback
     */
    public function register($route, callable $callback)
    {
        $this->router->register($route, $callback);
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function route(Request $request)
    {
        return $this->router->route($request);
    }
}