<?php
/**
 * AuthController.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/12
 */

namespace Hugo\Data\Controller;

use Hugo\Data\Storage\DB\MySQL;
use Hugo\Data\Model\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthController
 * @package Hugo\Data\Controller
 */
class AuthController extends AbstractController {

    /**
     * @return Response
     */
    public function getIndex()
    {
        return new Response(phpinfo(),
                            Constants::HTTP_OK);
    }

    /**
     * End point actions:
     * 1) Get the user from the Authorization header
     * 2) Check if user is active
     * 3) Get new token for user and return it
     *
     * @return Response
     * @throws \InvalidArgumentException
     */
    public function postToken()
    {
        $authHeader = explode(' ', $this->request->headers->get("Authorization"));
        // check for Basic authorisation
        if($authHeader[0] !== "Basic") {
            throw new \InvalidArgumentException("Basic Authorization is required for this end point", 400);
        }
        // make sure that the grant type is client_credentials
        if($this->request->request->get('grant_type') !== "client_credentials") {
            throw new \InvalidArgumentException("Only client_credentials grants are currently supported", 501);
        }

        $user = new User(new MySQL(['db' => 'hugo_oauth']));
        if($user->verifyUser($authHeader[1])) {

        }

        return new Response(json_encode(['headers' => 'test', 'grant-type' => $this->request->request->get('grant_type')], JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * @return Response
     */
    public function postUser()
    {
        //
    }

    /**
     * @param $id
     * @return Response
     */
    public function putUser($id = null)
    {
        //
    }

    /**
     * @param $id
     * @return Response
     */
    public function deleteUser($id = null)
    {
        //
    }

} 