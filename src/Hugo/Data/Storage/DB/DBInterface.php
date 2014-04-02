<?php
/**
 * DBInterface.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2014/03
 */

namespace Hugo\Data\Storage\DB;

use Hugo\Data\Storage\DataSource;

/**
 * Interface DBInterface
 * @package Hugo\Data\Storage\DB
 */
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