<?php
/**
 * ControllerInterface.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/09
 */

namespace Hugo\Data\Controller;

use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

interface ControllerInterface
{

    /**
     * @param Request $request
     */
    public function __construct(Request $request);

    /**
     * @return Response
     */
    public function handle();
}