<?php
/**
 * Report.php
 * data-api
 * @author: Matt
 * @date:   2013/11
 */

namespace Hugo\Data\Model;


use Hugo\Data\Exception\BadReportException;
use Hugo\Data\Storage\DB\DBInterface;
use Hugo\Data\Storage\DB\Query,
    Hugo\Data\Storage\DB\MySQL,
    Hugo\Data\Storage\DataSource,
    Hugo\Data\Application\Logger,
    Hugo\Data\Exception\InvalidQueryException,
    Hugo\Data\Exception\InvalidRequestException;

/**
 * Class Report
 * @package Hugo\Data\Model
 * @todo Find solution to change column types
 */
class Report implements ModelInterface {

    /**
     * @var \Hugo\Data\Storage\DataSource
     */
    protected $store;

    /**
     * @var \Hugo\Data\Application\Logger
     */
    protected $log;

    /**
     * @var array
     */
    protected $_data = [];

    /**
     * @var string
     */
    protected $table;

    /**
     * @param DataSource $store
     * @param bool $showAll
     * @return array
     */
    static public function listArray(DataSource $store, $showAll)
    {
        $showAll ? $published = [] : $published = ['published' => true];
        $reports = $store->read(
            'report_metadata',
            ['id', 'client_id', 'report_about'],
            $published
        );
        return !(bool)$reports ? [] : $reports;
    }

    /**
     * @param DataSource $store
     * @param null $id
     * @throws \Hugo\Data\Exception\InvalidQueryException
     */
    public function __construct(DataSource $store, $id = null)
    {
        $this->store = $store;
        $this->log = new Logger();

        if(null !== $id && !isset($this->_data['id'])) {
            $reportFromStore = $this->store->read('report_metadata', [], ['id' => $id]);
            $this->store->close();

            if(!(bool)$reportFromStore) {
                $this->log->error("No report with id {id} found in store", ['id' => $id]);
                throw new InvalidQueryException("No report exists with id {$id}", 404);
            }

            $this->_data = $reportFromStore[0];
            //$this->checkDataSet();
        }
    }

    /**
     * Processes a CSV file for a report
     *
     * @param \SplFileObject $file
     * @param array $data
     * @return $this
     * @throws \Hugo\Data\Exception\BadReportException
     */
    public function processFile(\SplFileObject $file, array $data = [])
    {
        $this->_data = $data;
        $this->_data['report_order'] = null; // set initially to avoid throwing NOTICE
        $this->_data['published'] = false; // set initially to avoid throwing NOTICE
        $this->table = $this->_data['id'];
        $this->save();

        // copy the data from the file into $this->_data array
        $this->_data['columns'] = $file->fgetcsv();
        if(!in_array('year', $this->_data['columns']) || !in_array('local_authority', $this->_data['columns'])) {
            $this->log->error("File upload attempted with no year or local_authority field");
            throw new BadReportException("All reports need to have 'year' and 'local_authority' fields.");
        }

        // first line of the file is the columns, rest is data
        while(!$file->eof()) {
            $this->_data['data'][] = $file->fgetcsv();
        }

        // seek to the beginning of the
        $file->seek(0);
        $strippedFile = new \SplFileObject($file->getRealPath() . '.tmp', 'w'); // create a new temporary file with the data

        // copy all but the first line to the temporary file.
        foreach(new \LimitIterator($file, 1) as $line) {
            $strippedFile->fwrite($line);
        }

        $this->createReportTable($file);
        $this->log->debug("New report in table `hugo_reports`.`{report}`", ['report' => $this->table]);

        $query = new Query(new MySQL(['db' => 'hugo_reports', 'table' => 'report_metadata']));
        $query->loadDataInFile($this->table, $this->_data['columns'], $strippedFile);

        // @todo: when the query executes, the files are still busy so can't be deleted.
        // if the query executed correctly, then we can delete the CSV files from the server
        if($query->exec()) {
            //unlink($strippedFile->getRealPath());
            //unlink($file->getRealPath());
        }

        return $this;
    }

    /**
     * @param \SplFileObject $file
     * @return bool
     */
    private function createReportTable(\SplFileObject $file)
    {
        $query = new Query(new MySQL(['db' => 'hugo_reports', 'table' => 'report_metadata']));

        $this->log->debug("New report in table `hugo_reports`.`{report}`", ['report' => $this->table]);
        $query->createTable($this->table);

        // easiest method is just to assign all columns to VARCHAR
        foreach($this->_data['columns'] as $column) {
            if($column == 'year' || $column == 'local_authority') {
                $type = 'VARCHAR(45)';
            } else {
                // assume all other columns are numeric
                $type = 'DOUBLE';
            }
            $query->addColumn($column, $type);
        }

        return $query->exec();
    }

    /**
     * @param \SplFileObject $file
     * @return bool
     * @throws \Hugo\Data\Exception\InvalidRequestException
     */
    public function updateData(\SplFileObject $file)
    {
        $query = new Query(new MySQL(['db' => 'hugo_reports', 'table' => $this->_data['id']]));

        $line = $file->fgetcsv();
        if(count($line) !== count($this->_data['columns'])) {
            $this->log->error(
                "Attempted to update report {id} with {file-count} columns, {store-count} columns in report",
                ['id' => $this->_data['id'], 'file-count' => count($line), 'store-count' => count($this->_data['columns'])]
            );
            throw new InvalidRequestException("Number of columns in the file does not match what is already in the report", 500);
        }
        if($line == $this->_data['columns']) {
            $this->log->error("File uploaded to PUT /report/{id} should not contain column headers", ['id' => $this->_data['id']]);
            throw new InvalidRequestException("File uploaded to PUT /report/{$this->_data['id']} shouldn't contain column headers", 500);
        }
        // reset the file line to zero
        $file->seek(0);

        return $query->loadDataInFile($this->_data['id'], $this->_data['columns'], $file)->exec();
    }

    /**
     * @param $order
     * @return bool
     * @todo Find method to check columns against table in DB ($this->_data['columns'] is null on when accessing via PUT)
     */
    public function checkValidOrder(&$order)
    {
        if(is_array($order)) {
            $order = json_encode($order);
            return true;
        } else if(json_decode($order) == null) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function saved()
    {
        $reportFromStore = $this->store->read('report_metadata', [], ['id' => $this->_data['id']]);
        return $this->toArray() == $reportFromStore;
    }

    /**
     * @return bool
     */
    public function save()
    {
        $reportFromStore = $this->store->read('report_metadata', [], ['id' => $this->_data['id']]);
        if($reportFromStore == []) {
            return $this->store->create($this);
        }

        // if it is saved, then we can just return true
        return $this->store->update($this);
    }

    /**
     * @return bool
     */
    public function delete()
    {
        $query = new Query(new MySQL(['db' => 'hugo_reports', 'table' => $this->_data['id']]));
        $query->drop($this->_data['id'])->exec();

        return $this->store->delete($this);
    }

    /**
     * @param array $attr
     * @return $this
     */
    public function set(array $attr)
    {
        $this->_data = array_merge($this->_data, $attr);
        return $this;
    }

    /**
     * Returns the report metadata
     * NB: Only provides metadata in line with connection to DataSource (specifically MySQL which updates based on this array)
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'id'            => $this->_data['id'],
            'published'     => $this->_data['published'],
            'report_about'  => $this->_data['report_about'],
            'client_id'     => $this->_data['client_id'],
            'report_order'  => $this->_data['report_order']
        ];
    }

    /**
     * @return array
     * @todo tidier way to get columns from report table
     */
    public function getReportDataArray()
    {
        $this->checkDataSet();

        return ['columns' => $this->_data['columns'], 'data' => $this->_data['data']];
    }

    /**
     *
     */
    private function checkDataSet()
    {
        if(!isset($this->_data['columns'])) {
            $pdo = new \PDO("mysql:host=localhost;dbname=hugo_reports", "hugo", "D0ubl3th1nk!");
            $query = $pdo->query("DESCRIBE " . $this->_data['id']);
            $this->_data['columns'] = $query->fetchAll(\PDO::FETCH_COLUMN);
        }

        if(!isset($this->_data['data'])) {
            $pdo = new \PDO("mysql:host=localhost;dbname=hugo_reports", "hugo", "D0ubl3th1nk!");
            $query = $pdo->query("SELECT * FROM `" . $this->_data['id'] . "`");
            $this->_data['data'] = $query->fetchAll(\PDO::FETCH_NUM);
        }
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

    /**
     *
     */
    public function __destruct()
    {
        $this->store->close();
    }

} 