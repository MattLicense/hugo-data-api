<?php
/**
 * AuthController.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/12
 */

namespace Hugo\Data\Controller;

use Hugo\Data\Model\User,
    Hugo\Data\Storage\DB\MySQL,
    Hugo\Data\OAuth\AuthServer,
    Symfony\Component\HttpFoundation\Response,
    Hugo\Data\Exception\InvalidTokenException,
    Hugo\Data\Exception\InvalidRequestException;

/**
 * Class AuthController
 * @package Hugo\Data\Controller
 */
class AuthController extends AbstractController {

    /**
     * GET /auth/checktoken
     *
     * @return Response
     * @throws \Hugo\Data\Exception\InvalidRequestException
     */
    public function getChecktoken()
    {
        $authServer = new AuthServer();
        if(!$authServer->verifyRequest($this->request)) {
            $this->log->error("Unauthorised request from {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("Invalid authorization token provided in headers", Constants::HTTP_UNAUTHORISED);
        }

        return new Response(json_encode($authServer->getToken()->toArray(), JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * POST /auth/token/
     *
     * @return Response
     * @throws \Hugo\Data\Exception\InvalidRequestException
     */
    public function postToken()
    {
        $authServer = new AuthServer();
        if(!$authServer->verifyAccessRequest($this->request)) {
            $this->log->error("Unauthorised access request from {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("Unauthorised access request, check Authorization header", Constants::HTTP_UNAUTHORISED);
        }

        $token = $authServer->generateToken('bearer');
        $tokenArray = $token->toArray();
        $tokenArray['scope'] = $token->scope[$tokenArray['scope']];

        $user = new User(new MySQL(['db' => 'hugo_oauth', 'table' => 'users']), $tokenArray['user_id']);
        unset($tokenArray['user_id']);
        $tokenArray['user'] = $user->user_logon;

        return new Response(json_encode($tokenArray, JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * DELETE /auth/token/
     *
     * @return Response
     * @throws \Hugo\Data\Exception\InvalidRequestException
     * @throws \Exception
     */
    public function deleteToken()
    {
        $authServer = new AuthServer();
        if(!$authServer->verifyRequest($this->request)) {
            $this->log->error("Unauthorised request attempted from {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("Unauthorised access token, ensure Authorization header is correct", Constants::HTTP_FORBIDDEN);
        }

        $token = $authServer->getTokenFromHeaders($this->request);

        if($token === null) {
            $this->log->error("Token couldn't be retrieved from headers from IP: {ip]", ['ip' => $this->request->getClientIp()]);
            throw new InvalidTokenException("Token could not be retrieved from headers, ensure Authorization header is correct", Constants::HTTP_FORBIDDEN);
        }

        $tokenValue = $token->getToken();

        if(!$token->delete()) {
            $this->log->error("Error deleting token {token}", ['token' => $token->getToken()]);
            throw new \Exception("Error deleting token {$token->getToken()}", Constants::HTTP_SERVER_ERROR);
        }

        return new Response(json_encode(['Success' => 'Token '. $tokenValue .' deleted'], JSON_PRETTY_PRINT).
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * GET /auth/user/{id}
     *
     * @param null $id
     * @return Response
     */
    public function getUser($id = null)
    {
        $store = new MySQL(['db' => 'hugo_oauth', 'table' => 'users']);
        if(null === $id) {  // GET /auth/user/
            $response = User::listArray($store);
        } else if(strtolower($id) == 'roles') { // GET /auth/user/roles/
            $response = $store->read('user_roles');
        } else { // GET /auth/user/{id}
            $user = new User($store, $id);
            $userArray = $user->toArray();

            $userRole = $store->read('user_roles', ['id', 'user_role'], ['id' => $userArray['user_role']])[0];

            unset($userArray['user_secret']);   // don't display password hash
            $userArray['active'] = (bool)$userArray['active'];  // return a true boolean (instead of 0, 1)
            $userArray['user_role'] = $userRole['user_role'];   // return a user-friendly role

            $response = $userArray;
        }

        return new Response(json_encode($response, JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * POST /auth/user/
     *
     * @return Response
     * @throws \Hugo\Data\Exception\InvalidRequestException
     * @throws \Exception
     */
    public function postUser()
    {
        $authServer = new AuthServer();
        if(!$authServer->verifyRequest($this->request)) {
            $this->log->error("Unauthorised request attempted from {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("Unauthorised access token, ensure Authorization header is correct", Constants::HTTP_FORBIDDEN);
        }

        $user = new User(new MySQL(['db' => 'hugo_oauth', 'table' => 'users']));
        $user->processParameters($this->request->request);

        // make sure that the user has been saved to the database
        if(!$user->save()) {
            $this->log->error("Error saving user to database, check MySQL logs");
            throw new \Exception("Error saving user to database, check logs", Constants::HTTP_SERVER_ERROR);
        }

        // $userArray to be used in the response, so we suppress the user_secret (password hash)
        $userArray = $user->toArray();
        unset($userArray['user_secret']);

        return new Response(json_encode($userArray, JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * PUT /auth/user/{id}
     *
     * @param $id
     * @return Response
     * @throws \Hugo\Data\Exception\InvalidRequestException
     * @throws \Exception
     */
    public function putUser($id = null)
    {
        if($id === null) {
            $this->log->error("Attempted PUT /auth/user/ without specifying user id from IP {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("User ID must be specified at this end point", Constants::HTTP_BAD_REQ);
        }

        $authServer = new AuthServer();
        if(!$authServer->verifyRequest($this->request)) {
            $this->log->error("Unauthorised request attempted from {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("Unauthorised access token, ensure Authorization header is correct", Constants::HTTP_FORBIDDEN);
        }

        $user = new User(new MySQL(['db' => 'hugo_oauth', 'table' => 'users']), $id);
        $userName       = (bool)$this->request->request->get('user_name') ? $this->request->request->get('user_name') : $user->user_name;
        $userLogon      = (bool)$this->request->request->get('user_logon') ? $this->request->request->get('user_logon') : $user->user_logon;
        $userSecret     = (bool)$this->request->request->get('user_secret') ? password_hash($this->request->request->get('user_secret'), PASSWORD_BCRYPT) : $user->user_secret;
        $userRole       = (bool)$this->request->request->get('user_role') ? $this->request->request->get('user_role') : $user->user_role;
        $active         = (bool)$this->request->request->get('active');

        // set the user characteristics
        $user->set(['user_name' => $userName, 'user_logon' => $userLogon, 'user_secret' => $userSecret, 'user_role' => $userRole, 'active' => $active]);

        // make sure that the user has been saved to the database
        if(!$user->save()) {
            $this->log->error("Error saving user to database, check MySQL logs");
            throw new \Exception("Error saving user to database, check logs", Constants::HTTP_SERVER_ERROR);
        }

        // $userArray to be used in the response, so we suppress the user_secret (password hash)
        $userArray = $user->toArray();
        unset($userArray['user_secret']);

        return new Response(json_encode($userArray, JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

    /**
     * DELETE /auth/user/{id}
     *
     * @param $id
     * @return Response
     * @throws \Hugo\Data\Exception\InvalidRequestException
     * @throws \Exception
     */
    public function deleteUser($id = null)
    {
        if($id === null) {
            $this->log->error("Attempted DELETE /auth/user/ without specifying user id from IP {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("User ID must be specified at this end point", Constants::HTTP_BAD_REQ);
        }

        $authServer = new AuthServer();
        if(!$authServer->verifyRequest($this->request)) {
            $this->log->error("Unauthorised request attempted from {ip}", ['ip' => $this->request->getClientIp()]);
            throw new InvalidRequestException("Unauthorised access token, ensure Authorization header is correct", Constants::HTTP_FORBIDDEN);
        }

        $user = new User(new MySQL(['db' => 'hugo_oauth', 'table' => 'users']), $id);
        if(!$user->delete()) {
            $this->log->error("Error deleting user, check MySQL logs");
            throw new \Exception("Error deleting user, check logs", Constants::HTTP_SERVER_ERROR);
        }

        return new Response(json_encode(['success' => 'User ' . $user->user_logon . ' deleted'], JSON_PRETTY_PRINT),
                            Constants::HTTP_OK,
                            ['Content-Type' => Constants::CONTENT_TYPE]);
    }

} 