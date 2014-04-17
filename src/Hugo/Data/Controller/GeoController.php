<?php
/**
 * GeographyController.php
 * data-api
 * @author: Matt
 * @date:   2014/03
 */

namespace Hugo\Data\Controller;

use Hugo\Data\Storage\DB\MySQL,
    Symfony\Component\HttpFoundation\Response;

class GeoController extends AbstractController {

    /**
     * GET /geo/locals/
     *
     * @return Response
     */
    public function getLocals()
    {
        $store = new MySQL(['db' => 'hugo_geography', 'table' => 'local_authority']);

        return new Response(json_encode($store->read('local_authority', []), JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * GET /geo/leps
     *
     * @return Response
     */
    public function getLeps()
    {
        $store = new MySQL(['db' => 'hugo_geography', 'table' => 'lep']);

        return new Response(json_encode($store->read('lep', []), JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * GET /geo/regions
     *
     * @return Response
     */
    public function getRegions()
    {
        $store = new MySQL(['db' => 'hugo_geography', 'table' => 'region']);

        return new Response(json_encode($store->read('region', []), JSON_PRETTY_PRINT),
            Constants::HTTP_OK,
            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

} 