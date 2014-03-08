<?php
/**
 * DBInterface.php
 * data-api
 * @author: Matt
 * @date:   2014/03
 */

namespace Hugo\Data\Storage\DB;

use Hugo\Data\Storage\DataSource;

interface DBInterface extends DataSource {

    /**
     * @param $dsn
     * @return $this
     */
    public function connect($dsn);

    /**
     * @param Query $query
     * @return mixed
     */
    public function execute(Query $query);

    /**
     * @return $this
     */
    public function close();

} 