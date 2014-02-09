<?php
/**
 * ModelInterface.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/10
 */

namespace Hugo\Data\Model;

use Hugo\Data\Storage\DataSource;

/**
 * Interface ModelInterface
 * @package Hugo\Data\Model
 */
interface ModelInterface
{
    /**
     * Constructor for model. Sets a DataSource to save to, and optional ID to retrieve old.
     *
     * @param DataSource $store
     * @param null $id
     */
    public function __construct(DataSource $store, $id = null);

    /**
     * Checks if the current model isn't saved (i.e. hasn't been created before or has been updated)
     *
     * @return bool
     */
    public function saved();

    /**
     * Creates/updates the model
     *
     * @return bool
     */
    public function save();

    /**
     * Deletes the model
     *
     * @return mixed
     */
    public function delete();

    /**
     * Setting attributes through an array
     *
     * @param array $attr
     * @return mixed
     */
    public function set(array $attr);

    /**
     * Used to convert the model into an associative array
     *
     * @return array
     */
    public function toArray();

    /**
     * Used to store the model, especially saving to file
     *
     * @return string
     */
    public function __toString();

    /**
     * Magic method to get undefined parameter on the fly
     *
     * @param $key
     * @return mixed
     */
    public function __get($key);

    /**
     * Magic method to set undefined parameter on the fly
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    public function __set($key, $value);

}