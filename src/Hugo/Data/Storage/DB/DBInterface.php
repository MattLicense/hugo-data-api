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

    public function connect($dsn);

    public function close();

} 