<?php
/**
 * Client.php
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
class Client implements ModelInterface {

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
     * @throws \Hugo\Data\Exception\InvalidRequestException
     */
    public function __construct(DataSource $store, $id = null)
    {
        $this->store = $store;
        if(null !== $id) {
            $client = $this->store->read('clients', [], ['id' => $id]);
            if(!$client) {
                throw new InvalidRequestException("Client id {$id} not found.", 404);
            }

            $this->_data = $client;
        }
    }

    /**
     * @param DataSource $store
     * @return array
     */
    static public function listArray(DataSource $store)
    {
        $clients = $store->read('clients', [], []);
        return !(bool)$clients ? ['error' => 'No clients'] : $clients;
    }

    /**
     * @param ParameterBag $parameterBag
     * @return bool
     * @throws \Hugo\Data\Exception\InvalidRequestException
     */
    public function processParameters(ParameterBag $parameterBag)
    {
        $requiredFields = ['client_name', 'client_website', 'contact_name', 'contact_phone', 'contact_email'];
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

        $client = $this->store->read('clients', [], ['id' => $this->_data['id']]);

        return $client == $this->_data;
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
        return array_merge($this->_data, $attr);
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