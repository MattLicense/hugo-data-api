<?php
/**
 * MySQL.php
 * data-api
 * @author: Matt
 * @date:   2013/11
 */

namespace Hugo\Data\Storage\DB;

use Hugo\Data\Storage\FileSystem,
    Hugo\Data\Application\Logger,
    Hugo\Data\Model\ModelInterface,
    Hugo\Data\Exception\InvalidQueryException,
    Hugo\Data\Exception\InvalidDataSourceException;

/**
 * Class MySQL
 * @package Hugo\Data\Storage\DB
 * @todo Integrate Query object into ModelInterface-based functions
 * @todo Add JOIN functionality
 */
class MySQL implements DBInterface
{

    /**
     * @var array
     */
    private $config;

    /**
     * @var \PDO
     */
    private $pdo;

    /**
     * @var \Hugo\Data\Application\Logger
     */
    private $log;

    /**
     * @var string
     */
    private $dsn;

    const MYSQL_AND = 0;
    const MYSQL_OR  = 1;
    const MYSQL_VAL = 2;

    const QUERY_SELECT = 10;
    const QUERY_INSERT = 11;
    const QUERY_UPDATE = 12;
    const QUERY_DELETE = 13;

    /**
     * @param array $config
     * @throws \Hugo\Data\Exception\InvalidDataSourceException
     * @todo look into moving logger instantiation
     */
    public function __construct(array $config)
    {
        $this->log = new Logger(new FileSystem('/media/vagrant/www/api.hugowolferton.co.uk/logs/mysql.log'));

        if(!array_key_exists('db', $config)) {
            $this->log->error("No database schema was specified trying to connect to the database");
            throw new InvalidDataSourceException("A database schema was not specified in the MySQL config", 500);
        }

        if(!array_key_exists('table', $config)) {
            $this->log->error("No table was specified trying to connect to the database");
            throw new InvalidDataSourceException("No table has been specified for MySQL queries", 500);
        }

        $this->config = [
            'host'  => 'localhost',
            'user'  => 'hugo',
            'pass'  => 'D0ubl3th1nk!'
        ] + $config;

        $this->dsn = "mysql:host={$this->config['host']};dbname={$this->config['db']}";
        $this->connect($this->dsn);
    }

    /**
     * @param $dsn
     * @return $this
     */
    public function connect($dsn)
    {
        $this->log->info('Connecting to MySQL DB with DSN: {dsn}', ['dsn' => $dsn]);
        $this->pdo = new \PDO($dsn, $this->config['user'], $this->config['pass']);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $this;
    }

    /**
     * @return $this
     */
    public function close()
    {
        $this->pdo = null;
        return $this;
    }

    /**
     * @param $schema
     * @return $this
     */
    public function setSchema($schema)
    {
        $this->log->info('Changing schema from {new} to {old}', ['old' => $this->config['db'], 'new' => $schema]);

        $this->config['db'] = $schema;
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['db']}";
        $this->connect($dsn);

        return $this;   // allow for method chaining
    }

    /**
     * @param ModelInterface $model
     * @return bool
     */
    public function create(ModelInterface &$model)
    {
        $query = $this->generateInsertQuery($this->config['table'], $model->toArray());

        if(is_null($this->pdo)) {
            $this->connect($this->dsn);
        }

        $statement = $this->pdo->prepare($query);
        $this->log->debug($statement->queryString);
        $exec = $statement->execute($model->toArray());

        if(!$exec) {
            $this->log->error("INSERT query failed: {error}", ['error' => json_encode($statement->errorInfo())]);
        }

        $model->id = $this->getLatestId();

        return $exec;
    }

    /**
     * @param string $table     name of table to use
     * @param array $cols       array of columns to retrieve, if default/empty array, retrieve all columns
     * @param array $params     associative array of conditions
     * @param int $join         integer determining how to join parameters; @default AND
     * @return mixed
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function read($table = null, array $cols = [], array $params = [], $join = self::MYSQL_AND)
    {
        if(null === $table) {
            $this->log->warning('Tried to query DB without specifying table, assigning {table}'.
                                ['table' => $this->config['table']]);
        }

        if(is_null($this->pdo)) {
            $this->connect($this->dsn);
        }

        $query = $this->generateSelectQuery($table, $cols, $params, $join);

        $statement = $this->pdo->prepare($query);
        $this->log->debug($statement->queryString);
        $statement->execute($params);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param array $opts
     * @return mixed
     */
    public function readAll(array $opts = [])
    {
        return;
    }

    /**
     * @param ModelInterface $model
     * @return bool
     */
    public function update(ModelInterface $model)
    {
        $modelArray = $model->toArray();
        $query = $this->generateUpdateQuery($this->config['table'], $modelArray, ['id' => $model->id]);

        if(is_null($this->pdo)) {
            $this->connect($this->dsn);
        }

        $statement = $this->pdo->prepare($query);
        $this->log->debug($statement->queryString);
        $exec = $statement->execute($modelArray + ['id' => $model->id]);

        if(!$exec) {
            $this->log->error("UPDATE query failed: {error}", ['error' => json_encode($statement->errorInfo())]);
        }

        return $exec;
    }

    /**
     * @param ModelInterface $model
     * @return bool
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function delete(ModelInterface $model = null)
    {
        if(null === $model) {
            $this->log->error('No model provided to delete');
            throw new InvalidQueryException("No model provided to delete");
        }

        if(is_null($this->pdo)) {
            $this->connect($this->dsn);
        }

        $query = $this->generateDeleteQuery($this->config['table'], ['id' => $model->id]);

        $statement = $this->pdo->prepare($query);
        $this->log->debug($statement->queryString);
        $exec = $statement->execute(['id' => $model->id]);

        if(!$exec) {
            $this->log->error("DELETE query failed: {error}", ['error' => json_encode($statement->errorInfo())]);
        }

        return $exec;
    }

    /**
     * @param $type
     * @param null $table
     * @param array $cols
     * @param array $params
     * @param int $join
     * @return string
     * @throws \Hugo\Data\Exception\InvalidQueryException
     * @todo Refactor for INSERT/UPDATE/DELETE queries - currently leaves second argument empty
     */
    public function generateQuery($type, $table = null, array $cols = [], array $params = [], $join = self::MYSQL_AND)
    {
        switch($type) {
            case self::QUERY_SELECT:
                $query = $this->generateSelectQuery($table, $cols, $params, $join);
                break;
            case self::QUERY_INSERT:
                $query = $this->generateInsertQuery($table, $params);
                break;
            case self::QUERY_UPDATE:
                $query = $this->generateUpdateQuery($table, $cols, $params, $join);
                break;
            case self::QUERY_DELETE:
                $query = $this->generateDeleteQuery($table, $params, $join);
                break;
            default:
                throw new InvalidQueryException("Query type must be one of SELECT, INSERT, UPDATE, DELETE", 500);
        }

        return $query;
    }

    /**
     * @param $table
     * @param $cols
     * @param $params
     * @param $and
     * @return string
     */
    public function generateSelectQuery($table, $cols = [], $params = [], $and = self::MYSQL_AND)
    {
        $columns = (count($cols) > 0) ? implode(", ", $cols) : '*';
        $query = 'SELECT '. $columns .' FROM `' . $table . '`';

        if(count($params) > 0) {
            $query .= ' WHERE ' . $this->prepareConditions($params, $and);
        }

        $this->log->debug('SELECT query generated: {query}', ['query' => $query]);
        return $query;
    }

    /**
     * @param $table
     * @param array $values
     * @return string
     */
    public function generateInsertQuery($table, array $values = [])
    {
        $query  = "INSERT INTO `" . $table . "` (`" . implode('`,`',array_keys($values)) . "`) ";
        $query .= "VALUES (:" . implode(",:", array_keys($values)) . ")";
        $this->log->debug('INSERT query generated: {query}', ['query' => $query]);
        return $query;
    }

    /**
     * @param $table
     * @param array $values
     * @param array $conditions
     * @param int $join
     * @return string
     */
    public function generateUpdateQuery($table, array $values = [], array $conditions = [], $join = self::MYSQL_AND)
    {
        $query = 'UPDATE `' . $table . '` SET ' . $this->prepareConditions($values, self::MYSQL_VAL) . ' WHERE '
                .$this->prepareConditions($conditions, $join);
        $this->log->debug('UPDATE query generated: {query}', ['query' => $query]);
        return $query;
    }

    /**
     * @param $table
     * @param array $conditions
     * @param int $join
     * @return string
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function generateDeleteQuery($table, array $conditions = [], $join = self::MYSQL_AND)
    {
        if(empty($conditions)) {
            $this->log->error('Attempted to delete everything from {table}', ['table' => $table]);
            throw new InvalidQueryException("Deleting all entries from {$table} not permitted", 500);
        }

        $query = 'DELETE FROM `' . $table . '` WHERE ' . $this->prepareConditions($conditions, $join);
        $this->log->debug('DELETE query generated: {query}', ['query' => $query]);
        return $query;
    }

    /**
     * Formats the parameters array for use in a PDO prepared statement.
     *
     * @param array $params     e.g. ['user_logon' => 'a.edwards', 'active' => true]
     * @param int $join         integer to determine how to join the conditions; @default AND
     * @return string           e.g. user_logon = :user_logon AND active = :active
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    private function prepareConditions(array $params = [], $join = self::MYSQL_AND)
    {
        $conditions = [];
        foreach($params as $key => $value) {
            $conditions[] = $key . ' = :'.$key;
        }

        switch($join) {
            case self::MYSQL_AND:
                $verb = ' AND ';
                break;
            case self::MYSQL_OR:
                $verb = ' OR ';
                break;
            case self::MYSQL_VAL:
                $verb = ', ';
                break;
            default:
                $this->log->error('Tried to join conditions using {join} instead of defined constants', ['join' => $join]);
                throw new InvalidQueryException("Invalid attempt to join parameters, use defined constants", 500);
        }

        return implode($verb, $conditions);
    }

    /**
     * @param Query $query
     * @return mixed
     */
    public function execute(Query $query)
    {
        $statement = $this->pdo->exec($query->prepareQuery());

        if(!$statement) {
            $this->log->error("Query {query} failed: " . PHP_EOL . "{error}",
                [
                    'query' => $query->prepareQuery(),
                    'error' => json_encode($this->pdo->errorInfo())
                ]
            );
        }

        return $statement;
    }

    /**
     * @return string
     */
    public function getLatestId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * @return string
     */
    public function getSchema()
    {
        return $this->config['db'];
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->config['host'];
    }

}
