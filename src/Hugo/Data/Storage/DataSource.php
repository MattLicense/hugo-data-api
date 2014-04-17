<?php
/**
 * DataSource.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/10
 */

namespace Hugo\Data\Storage;

use Hugo\Data\Model\ModelInterface;

interface DataSource
{

    /**
     * Stores a new model
     *
     * @param ModelInterface $model
     * @return bool
     */
    public function create(ModelInterface &$model);

    /**
     * Gets a model
     *
     * @param null $id
     * @param array $opts
     * @return mixed
     */
    public function read($id = null, array $opts = []);

    /**
     * Gets all models
     *
     * @param array $opts
     * @return mixed
     */
    public function readAll(array $opts = []);

    /**
     * Updates a model
     *
     * @param ModelInterface $model
     * @return bool
     */
    public function update(ModelInterface $model);

    /**
     * Deletes a model
     *
     * @param ModelInterface $model
     * @return bool
     */
    public function delete(ModelInterface $model = null);

}