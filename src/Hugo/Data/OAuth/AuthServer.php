<?php
/**
 * AuthServer.php
 * data-api
 * @author: Matt
 * @date:   2014/02
 */

namespace Hugo\Data\OAuth;

use Hugo\Data\Model\User,
    Hugo\Data\Storage\DB\MySQL,
    Hugo\Data\Storage\DataSource,
    Hugo\Data\OAuth\Token\Bearer,
    Hugo\Data\Application\Logger,
    Hugo\Data\OAuth\Token\TokenFactory,
    Hugo\Data\OAuth\Token\TokenTypeInterface,
    Symfony\Component\HttpFoundation\Request;

class AuthServer {

    /**
     * @var \Hugo\Data\Model\User
     */
    private $user;

    /**
     * @var \Hugo\Data\OAuth\Token\TokenFactory
     */
    private $tokenFactory;

    /**
     * @var \Hugo\Data\OAuth\Token\TokenTypeInterface
     */
    private $token;

    /**
     * @var array
     */
    private $config = [];

    /**
     * @param \Hugo\Data\Storage\DataSource $store
     */
    public function __construct(DataSource $store = null)
    {
        $this->config['store'] = is_null($store) ? new MySQL(['db' => 'hugo_oauth', 'table' => 'token']) : $store;
        $this->log = new Logger();
        $this->tokenFactory = new TokenFactory($this->config['store']);
    }

    /**
     * @param Request $request
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function verifyAccessRequest(Request $request)
    {
        $authHeader = explode(' ', $request->headers->get("Authorization"));

        // check for Basic authorisation
        if($authHeader[0] !== "Basic") {
            $this->log->error('Attempted to use {header} to verify access request', ['header' => $authHeader[0]]);
            throw new \InvalidArgumentException("Basic Authorization is required for this end point", 405);
        }
        // make sure that the grant type is client_credentials
        $grantType = $request->request->get('grant_type');
        if($grantType !== "client_credentials") {
            $this->log->error('Attempted to use {grant-type} to verify access request', ['grant-type' => $grantType]);
            throw new \InvalidArgumentException("Only client_credentials grants are currently supported", 501);
        }

        $this->user = new User(new MySQL(['db' => 'hugo_oauth', 'table' => 'users']));
        return $this->user->verifyUser($authHeader[1]);
    }


    /**
     * @param Request $request
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function verifyRequest(Request $request)
    {
        $authHeader = explode(' ', $request->headers->get("Authorization"));

        // token scope references controller sections
        $pathArray = explode('/', trim($request->getPathInfo(), '/'));
        $controller = array_shift($pathArray);  // first part of URI should be the controller

        switch(strtolower($authHeader[0])) {
            case 'bearer':
                $this->log->info("Attempting to authenticate bearer token: {token}", ['token' => $authHeader[1]]);
                $this->token = new Bearer(new MySQL(['db' => 'hugo_oauth', 'table' => 'token']));
                break;
            default:
                $header = trim($authHeader[0]);
                $this->log->error("Attempted unsupported type of HTTP Authorization: {auth}", ['auth' => $header]);
                throw new \InvalidArgumentException("{$header} Authorization is not supported for this end point", 405);
        }

        return $this->token->verifyToken($authHeader[1], $controller);
    }

    public function hasToken(Request $request)
    {
        $authHeader = explode(' ', $request->headers->get("Authorization"));
    }

    /**
     * @param $type
     * @return Token\TokenTypeInterface
     */
    public function generateToken($type)
    {
        $token = $this->tokenFactory->getToken($type, $this->user);
        $token->generateToken();

        return $token;
    }

    /**
     * @param Request $request
     * @return Token\TokenTypeInterface | null
     * @throws \InvalidArgumentException
     */
    public function getTokenFromHeaders(Request $request)
    {
        $authHeader = explode(' ', $request->headers->get("Authorization"));
        $pathArray = explode('/', trim($request->getPathInfo(), '/'));
        $controller = array_shift($pathArray);

        switch(strtolower($authHeader[0])) {
            case 'bearer':
                $this->token = new Bearer(new MySQL(['db' => 'hugo_oauth', 'table' => 'token']));
                break;
            default:
                throw new \InvalidArgumentException("{$authHeader[0]} Authorization is not supported for this end point", 405);
        }

        if($this->token->verifyToken($authHeader[1], $controller)) {
            return $this->token;
        }

        return null;
    }

    /**
     * @param TokenTypeInterface $token
     * @return bool
     */
    public function deleteToken(TokenTypeInterface $token)
    {
        return $token->delete();
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return TokenTypeInterface
     */
    public function getToken()
    {
        return $this->token;
    }

    public function __destruct()
    {
        $store = $this->config['store'];
        $store->close();
    }

}