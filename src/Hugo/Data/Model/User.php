<?php
/**
 * User.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/12
 */

namespace Hugo\Data\Model;

use Hugo\Data\Storage\DataSource,
    Hugo\Data\Exception\InvalidRequestException,
    Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class User
 * @package Hugo\Data\Model
 */
class User implements ModelInterface {

    /**
     * @var \Hugo\Data\Storage\DataSource
     */
    protected $store;

    /**
     * @var array
     */
    protected $_data = [];

    /**
     * @param DataSource $store
     * @param null $id
     */
    public function __construct(DataSource $store, $id = null)
    {
        $this->store = $store;
        if(null !== $id) {
            $this->_data = $this->store->read('users', [], ['id' => $id]);
        }
    }

    /**
     * @param DataSource $store
     * @return array
     */
    static public function listArray(DataSource $store)
    {
        $users = $store->read('users', ['id', 'user_name', 'user_logon', 'user_role', 'active']);

        if(!(bool)$users) {
            return ['error' => 'No users to list'];
        }

        foreach($users as &$user) {
            $user_role = $store->read('user_roles', ['id', 'user_role'], ['id' => $user['user_role']])[0];
            $user['user_role'] = $user_role['user_role'];
            $user['active'] = (bool)$user['active'];
        }

        return $users;
    }

    /**
     * Sets user details from a base64 encoded username/password
     *
     * @param $encodedUser
     * @return mixed
     */
    public function verifyUser($encodedUser)
    {
        $decoded = base64_decode($encodedUser);
        list($user, $pass) = explode(':',$decoded);

        return $this->login($user, $pass);
    }

    /**
     * @param $user
     * @param $pass
     * @return bool
     */
    public function login($user, $pass)
    {
        // search for the username/password combination
        $match = $this->store->read('users', [], ['user_logon' => $user, 'active' => true]);

        if((bool)$match) {
            // if a match is found, then assign the user details
            $this->_data = $match;
            return password_verify($pass, $match['user_secret']);
        }

        return false;
    }

    /**
     * @param ParameterBag $parameterBag
     * @return bool
     * @throws \Hugo\Data\Exception\InvalidRequestException
     */
    public function processParameters(ParameterBag $parameterBag)
    {
        $requiredFields = ['user_name', 'user_logon', 'user_secret', 'active', 'user_role'];
        foreach($requiredFields as $field) {
            if(!$this->parameterExists($field, $parameterBag)) {
                throw new InvalidRequestException("Required parameter {$field} wasn't found.", 400);
            }
            $this->{$field} = $parameterBag->get($field);
        }

        return true;
    }

    /**
     * @param $field
     * @param ParameterBag $parameterBag
     * @return bool
     */
    private function parameterExists($field, ParameterBag $parameterBag)
    {
        return !is_null($parameterBag->get($field)) || isset($this->_data[$field]);
    }

    /**
     * @return bool
     */
    public function saved()
    {
        if(!isset($this->_data['id']) || $this->_data['id'] === null) {
            return false;
        }

        $user = $this->store->read('users', [], ['user_logon' => $this->_data['user_logon']]);

        return $user == $this->_data;
    }

    /**
     * @return bool
     */
    public function save()
    {
        if(!$this->saved()) {
            return $this->store->create($this);
        }

        return $this->store->update($this);
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        return $this->store->delete($this);
    }

    /**
     * @param array $attr
     * @return mixed
     */
    public function set(array $attr)
    {
        return $this->_data + $attr;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->_data;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }

    /**
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->_data[$key];
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