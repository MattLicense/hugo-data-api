<?php
/**
 * Bearer.php
 * data-api
 * @author: Matt
 * @date:   2013/12
 */

namespace Hugo\Data\OAuth\Token;

use Hugo\Data\Model\User,
    Hugo\Data\Storage\DataSource,
    Hugo\Data\Exception\InvalidTokenException,
    Hugo\Data\Exception\InvalidRequestException;

/**
 * Class Bearer
 * @package Hugo\Data\OAuth\Token
 */
class Bearer implements TokenTypeInterface {

    /**
     * Token string will be twice this value
     */
    const TOKEN_LENGTH = 24;

    /**
     * @var User
     */
    private $user;

    /**
     * @var DataSource
     */
    private $store;

    /**
     * Used to
     * @var array
     */
    private $_data = [];

    /**
     * Lookup relating user roles to OAuth scope
     * @var array
     */
    public $scope = [
        '1'   => 'report:all client:all',
        '2'   => 'report:all client:all auth:all'
    ];

    /**
     * @param DataSource $store
     * @param null $token
     * @throws \Hugo\Data\Exception\InvalidTokenException
     */
    public function __construct(DataSource $store, $token = null)
    {
        $this->store = $store;
        if(null !== $token) {
            $tokenFromStore = $this->store->read('token', [], ['token' => $token]);
            if(count($tokenFromStore) !== 1) {
                throw new InvalidTokenException("No token {$token} found.");
            }
            $this->_data = $tokenFromStore[0];
        }
    }

    /**
     * @return string
     */
    public function getTokenType()
    {
        return 'bearer';
    }

    /**
     * @return bool
     * @throws \Hugo\Data\Exception\InvalidTokenException
     */
    public function generateToken()
    {
        if(null === $this->_data['user_id']) {
            throw new InvalidTokenException("No user assigned to token", 500);
        }

        $this->_data['token'] = bin2hex(\OAuthProvider::generateToken(self::TOKEN_LENGTH, true));
        $expires = new \DateTime('2 hours');
        $this->_data['expires'] = $expires->format("Y-m-d H:i:s");
        $this->_data['scope'] = $this->user->user_role;

        // check if the user already has a token assigned
        $tokenFromStore = $this->store->read('token', ['id', 'user_id'], ['user_id' => $this->_data['user_id']]);
        if(!(bool)$tokenFromStore) {
            return $this->store->create($this);
        }

        $token = $tokenFromStore[0];
        $this->_data['id'] = $token['id'];
        return $this->store->update($this);

    }

    /**
     * @param $token
     * @param $controller
     * @return bool
     * @throws \Hugo\Data\Exception\InvalidRequestException
     */
    public function verifyToken($token, $controller)
    {
        $tokens = $this->store->read('token', [], ['token' => $token]);
        if(!(bool)$tokens) {
            throw new InvalidRequestException("Invalid authorization token.", 401);
        }

        $tokenFromStore = $tokens[0];

        // check the expiry of the token
        $expiry = isset($tokenFromStore['expires']) ? new \DateTime($tokenFromStore['expires']) : false;
        $this->set($tokenFromStore);
        $date = new \DateTime();
        if((bool)$expiry && $date > $expiry) {
            $this->delete();
            throw new InvalidRequestException("Token has expired, please log in to generate a new one.", 401);
        }

        // check the scope of the token
        $scope = $this->scope[$tokenFromStore['scope']];
        if(strpos($scope, $controller) === false) {
            throw new InvalidRequestException("You don't have permission to access this resource.", 403);
        }

        return $this->updateExpiry();
    }

    /**
     * @return bool
     */
    private function updateExpiry()
    {
        $newDate = new \DateTime('2 hours');
        $this->_data['expires'] = $newDate->format('Y-m-d H:i:s');

        return $this->store->update($this);
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->_data['token'];
    }

    /**
     * @return string
     */
    public function getExpiry()
    {
        return $this->_data['expires'];
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user)
    {
        // store both to use User characteristics in code, but only use ID when saving the token
        $this->user = $user;
        $this->_data['user_id'] = $user->id;
        return $this; // allow for method chaining
    }

    /**
     * @return bool
     */
    public function saved()
    {
        if(!isset($this->_data['id']) || $this->_data['id'] === null) {
            return false;
        }

        $token = $this->store->read('users', [], ['token' => $this->_data['token']])[0];

        return $token == $this->_data;
    }

    /**
     * @return bool
     */
    public function save()
    {
        // if no ID is set, then it doesn't exist in the database
        if(!isset($this->_data['id']) || $this->_data['id'] === null) {
            return $this->store->create($this);
        }
        if(!$this->saved()) {
            return $this->store->update($this);
        }

        // if it is saved, then we can just return true
        return true;
    }

    /**
     * @return bool
     */
    public function delete()
    {
        return $this->store->delete($this);
    }

    /**
     * @param array $attr
     * @return $this
     */
    public function set(array $attr)
    {
        $this->_data = $this->_data + $attr;
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {

        return [
            'type'      => "1",   // Bearer
            'user_id'   => $this->_data['user_id'],
            'token'     => $this->_data['token'],
            'scope'     => $this->_data['scope'],
            'expires'   => $this->_data['expires']
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    /**
     * @param $key
     * @param $value
     * @return mixed
     */
    public function __set($key, $value)
    {
        return $this->_data[$key] = $value;
    }

} 