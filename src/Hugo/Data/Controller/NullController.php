<?php
/**
 * NullController.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/09
 */

namespace Hugo\Data\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class NullController implements ControllerInterface
{

    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function handle()
    {
        return new Response(json_encode(["error" => "Nothing defined at {$this->request->getPathInfo()}"], JSON_PRETTY_PRINT),
                            Constants::HTTP_NOT_FOUND,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }
}