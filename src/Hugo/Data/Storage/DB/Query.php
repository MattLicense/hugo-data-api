<?php
/**
 * Query.php
 * data-api
 * @author: Matt
 * @date:   2014/02
 */

namespace Hugo\Data\Storage\DB;

use Hugo\Data\Storage\FileSystem,
    Hugo\Data\Application\Logger,
    Hugo\Data\Exception\InvalidQueryException;

/**
 * Class Query
 * @package Hugo\Data\Storage
 * @todo Add JOIN functionality
 */
class Query {

    /**
     * Constants for defining the type of query
     */
    const TYPE_SELECT    = 1;
    const TYPE_INSERT    = 2;
    const TYPE_UPDATE    = 3;
    const TYPE_DELETE    = 4;
    const TYPE_CREATE    = 5;
    const TYPE_DROP      = 6;
    const TYPE_LOAD_FILE = 7;

    /**
     * Constants for defining WHERE x {cond} y
     */
    const WHERE_EQU    = "=";
    const WHERE_LT     = "<";
    const WHERE_LTE    = "<=";
    const WHERE_GT     = ">";
    const WHERE_GTE    = ">=";
    const WHERE_LIKE   = "LIKE";
    const WHERE_NTEQU  = "<>";
    const WHERE_NTLIKE = "NOT LIKE";

    /**
     * Constants for defining table constraints
     */
    const CONSTRAINT_PK  = 10;   // primary key
    const CONSTRAINT_FK  = 11;   // foreign key
    const CONSTRAINT_IND = 12;   // index

    /**
     * @var \Hugo\Data\Storage\DB\DBInterface
     */
    private $store;

    /**
     * @var
     */
    private $table;

    /**
     * @var array
     */
    private $columns = [];

    /**
     * @var array
     */
    private $where = [];

    /**
     * @var array
     */
    private $whereJoin = [];

    /**
     * @var array
     */
    private $values = [];

    /**
     * @var array
     */
    private $constraints = [];

    /**
     * @var int|null
     */
    private $type = null;

    /**
     * @var \SplFileObject
     */
    private $file;

    /**
     * @var \Hugo\Data\Application\Logger
     */
    private $log;

    /**
     * @var string
     */
    private $query;

    /**
     * @param DBInterface $store
     */
    public function __construct(DBInterface $store)
    {
        $this->store = $store;
        $this->log = new Logger(new FileSystem('/media/vagrant/www/api.hugowolferton.co.uk/logs/mysql.log'));
    }

    /**
     * @param $table
     * @param array $columns
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function select($table, array $columns)
    {
        if($this->type !== null) {
            throw new InvalidQueryException("Cannot use define two query types, use a second Query.", 500);
        }

        $this->type = self::TYPE_SELECT;
        $this->table = $table;
        $this->columns = $columns;
        return $this;
    }

    /**
     * @param $table
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function insert($table)
    {
        if($this->type !== null) {
            throw new InvalidQueryException("Cannot use define two query types, use a second Query.", 500);
        }

        $this->type = self::TYPE_INSERT;
        $this->table = $table;
        return $this;
    }

    /**
     * @param $table
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function update($table)
    {
        if($this->type !== null) {
            throw new InvalidQueryException("Cannot use define two query types, use a second Query.", 500);
        }

        $this->type = self::TYPE_UPDATE;
        $this->table = $table;
        return $this;
    }

    /**
     * @param $table
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function delete($table)
    {
        if($this->type !== null) {
            throw new InvalidQueryException("Cannot use define two query types, use a second Query.", 500);
        }

        $this->type = self::TYPE_DELETE;
        $this->table = $table;
        return $this;
    }

    /**
     * @param $table
     * @param array $columns
     * @param \SplFileObject $file
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function loadDataInFile($table, array $columns, \SplFileObject $file)
    {
        if($this->type !== null) {
            throw new InvalidQueryException("Cannot use define two query types, use a second Query.", 500);
        }

        $this->type = self::TYPE_LOAD_FILE;
        $this->table = $table;
        $this->columns = $columns;
        $this->file = $file;

        return $this;
    }

    /**
     * @param $table
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function createTable($table)
    {
        if($this->type !== null) {
            throw new InvalidQueryException("Cannot use define two query types, use a second Query.", 500);
        }

        $this->type = self::TYPE_CREATE;
        $this->table = $table;

        return $this;
    }

    /**
     * @param $field
     * @param $type
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function addColumn($field, $type)
    {
        if($this->type !== self::TYPE_CREATE) {
            throw new InvalidQueryException("Query::addColumn can only be used in conjunction with Query::createTable", 500);
        }

        $this->columns[$field] = $type;

        return $this;
    }

    /**
     * @param array $fields
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function addColumns(array $fields)
    {
        if($this->type !== self::TYPE_CREATE) {
            throw new InvalidQueryException("Query::addColumns can only be used in conjunction with Query::createTable", 500);
        }

        $this->columns = $this->columns + $fields;

        return $this;
    }

    /**
     * @param int $type
     * @param $field
     * @param $references
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function addConstraint($type = self::CONSTRAINT_PK, $field, $references = null)
    {
        if(!array_key_exists($field, $this->columns)) {
            throw new InvalidQueryException("Field \"{$field}\" has not been defined.", 500);
        }
        if($type === self::CONSTRAINT_FK && $references === null) {
            throw new InvalidQueryException("Foreign key constraints require a reference", 500);
        }

        // force a primary key or index to have a null reference
        if($type === self::CONSTRAINT_PK || $type === self::CONSTRAINT_IND) {
            $references = null;
        }

        $this->constraints[$field] = ['type' => $type, 'reference' => $references];

        return $this;
    }

    /**
     * @param $table
     * @return $this
     */
    public function drop($table)
    {
        $this->type = self::TYPE_DROP;
        $this->table = $table;
        return $this;
    }

    /**
     * @param $field
     * @param $value
     * @param $join
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     * @todo investigate method to allow choice between AND/OR to
     */
    public function where($field, $value, $join = self::WHERE_EQU)
    {
        if($this->type === null) {
            throw new InvalidQueryException("Type of query needs to be defined before adding conditions", 500);
        }

        $this->where[$field] = $value;
        $this->whereJoin[$field] = $join;

        return $this;
    }

    /**
     * @param $field
     * @param $value
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function set($field, $value)
    {
        if(!in_array($this->type, [self::TYPE_INSERT, self::TYPE_UPDATE])) {
            throw new InvalidQueryException("Query::set can only be used on INSERT and UPDATE queries", 500);
        }

        $this->values[$field] = $value;
        return $this;
    }

    /**
     * @param array $values
     * @return $this
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function setArray(array $values)
    {
        if(!in_array($this->type, [self::TYPE_INSERT, self::TYPE_UPDATE])) {
            throw new InvalidQueryException("Query::setArray can only be used on INSERT and UPDATE queries", 500);
        }

        $this->values = $this->values + $values;
        return $this;
    }

    /**
     * @return bool
     */
    public function exec()
    {
        $exec = $this->store->execute($this);
        if($exec) {
            $this->resetQuery();
        }
        return $exec;
    }

    /**
     * @return string
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function prepareQuery()
    {
        switch($this->type) {
            case self::TYPE_SELECT:
                $query = $this->prepareSelectQuery();
                break;
            case self::TYPE_INSERT:
                $query = $this->prepareInsertQuery();
                break;
            case self::TYPE_UPDATE:
                $query = $this->prepareUpdateQuery();
                break;
            case self::TYPE_DELETE:
                $query = $this->prepareDeleteQuery();
                break;
            case self::TYPE_LOAD_FILE:
                $query = $this->prepareLoadFileQuery();
                break;
            case self::TYPE_DROP:
                $query = $this->prepareDropQuery();
                break;
            case self::TYPE_CREATE:
                $query = $this->prepareCreateQuery();
                break;
            default:
                throw new InvalidQueryException("Invalid Query type given", 500);
        }

        return $this->query = $query;
    }

    /**
     * @return string
     */
    private function prepareWhereConditions()
    {
        if(empty($this->where)) {
            return "";
        }

        $terms = count($this->where);
        $conditions = " WHERE ";
        foreach($this->where as $field => $value) {
            $terms--;
            $conditions .= "`" . $field . "` " . $this->whereJoin[$field] . " '" . $value . "'";
            if($terms > 0) { $conditions .= " AND "; }
        }

        return $conditions;
    }

    /**
     * @return string
     */
    private function prepareSelectQuery()
    {
        // if $this->columns is an empty array, we select all columns
        empty($this->columns) ? $columns = "*" : $columns = "`" . implode("`, `", $this->columns) .  "`";

        $conditions = $this->prepareWhereConditions();

        return "SELECT " . $columns . " FROM " . $this->table . $conditions;
    }

    /**
     * @return string
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    private function prepareFieldValues()
    {
        if(empty($this->values)) {
            throw new InvalidQueryException("Attempted to to prepare SET string with no parameters", 500);
        }

        $set = "(`" . implode("`,`", array_keys($this->values)) . "`) VALUES ('" . implode("','", array_values($this->values)) . "')";

        return $set;
    }

    /**
     * @return string
     */
    private function prepareInsertQuery()
    {
        return "INSERT INTO " . $this->table . " " . $this->prepareFieldValues();
    }

    /**
     * @return string
     */
    private function prepareUpdateQuery()
    {
        return "UPDATE " . $this->table . " " . $this->prepareFieldValues() . $this->prepareWhereConditions();
    }

    /**
     * @return string
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    private function prepareDeleteQuery()
    {
        $conditions = $this->prepareWhereConditions();
        if($conditions == "") {
            throw new InvalidQueryException("Deleting all records from a table is not allowed", 500);
        }

        return "DELETE FROM " . $this->table . $conditions;
    }

    /**
     * @return string
     */
    private function prepareDropQuery()
    {
        return "DROP TABLE " . $this->table;
    }

    /**
     * @return string
     */
    private function prepareLoadFileQuery()
    {
        return "LOAD DATA INFILE '" . $this->file->getRealPath() . "' INTO TABLE " . $this->table
                . " FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"'"
                . " (`" . implode("`,`", $this->columns) . "`)";
    }

    private function prepareConstraints()
    {
        $constraints = array();

        $prefix = empty($this->constraints) ? "" : " , ";

        foreach($this->constraints as $field => $constraint) {
            switch($constraint['type']) {
                case self::CONSTRAINT_IND:
                    $constraints[] = "INDEX `" . $field . uniqid("_") . "` (`" . $field . "` ASC)";
                    break;
                case self::CONSTRAINT_PK:
                    $constraints[] = "PRIMARY KEY (`" . $field . "`)";
                    break;
                case self::CONSTRAINT_FK:
                    $constraints[] = "CONSTRAINT `" . $field . uniqid("_") . "` FOREIGN KEY (`" . $field . "`) REFERENCES " . $constraint['reference'];
                    break;
                default:
                    $this->log->error("Invalid constraint type given: {type}", ['type' => $constraint['type']]);
                    throw new InvalidQueryException("Invalid constraint type {$constraint['type']} given", 500);
            }
        }

        return $prefix . implode(", ", $constraints);
    }

    /**
     * @return string
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    private function prepareCreateQuery()
    {
        /* $this->columns is used in two different ways:
               (1) as an indexed array for SELECT queries
               (2) as an associative array (column => type) for CREATE queries
           If we find that $this->columns is an indexed array throw an exception */
        if(array_values($this->columns) === $this->columns) {
            throw new InvalidQueryException("No type is given for the columns.", 500);
        }

        $columns = array();
        foreach($this->columns as $column => $type) {
            $columns[] = "`" . $column . "` " . $type;
        }

        return "CREATE TABLE " . $this->table . " (" . implode(', ', $columns) . $this->prepareConstraints() . ") ENGINE = InnoDB";
    }

    /**
     *
     */
    public function resetQuery()
    {
        $this->type = null;
        $this->columns = [];
        $this->values = [];
        $this->table = null;
        $this->constraints = [];
        $this->file = null;
        $this->query = null;
        $this->where = [];
        $this->whereJoin = [];
    }

    /**
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

} 