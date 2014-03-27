<?php
/**
 * Report.php
 * data-api
 * @author: Matt
 * @date:   2013/11
 */

namespace Hugo\Data\Model;


use Hugo\Data\Storage\DB\Query,
    Hugo\Data\Storage\DB\MySQL,
    Hugo\Data\Storage\DataSource,
    Hugo\Data\Application\Logger,
    Hugo\Data\Exception\InvalidQueryException,
    Hugo\Data\Exception\InvalidRequestException;

/**
 * Class Report
 * @package Hugo\Data\Model
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
     * @return array
     */
    static public function listArray(DataSource $store)
    {
        $reports = $store->read(
            'report_metadata',
            ['id', 'client_id', 'report_about'],
            ['published' => true]
        );
        return !(bool)$reports ? ['error' => 'No reports found'] : $reports;
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

            if(!(bool)$reportFromStore) {
                $this->log->error("No report with id {id} found in store", ['id' => $id]);
                throw new InvalidQueryException("No report exists with id {$id}");
            }
            $this->_data = $reportFromStore;
        }
    }

    /**
     * Processes a CSV file for a report
     *
     * @param \SplFileObject $file
     * @param array $data
     * @return $this
     */
    public function processFile(\SplFileObject $file, array $data = [])
    {
        $this->_data = $data;

        // copy the data from the file into $this->_data array
        $this->_data['columns'] = $file->fgetcsv();
        while(!$file->eof()) {
            $this->_data['data'][] = $file->fgetcsv();
        }
        $this->_data['report_order'] = null; // set initially to avoid throwing NOTICE

        // seek to the beginning of the
        $file->seek(0);
        $strippedFile = new \SplFileObject($file->getRealPath() . '.tmp', 'w'); // create a new temporary file with the data

        // copy all but the first line to the temporary file.
        foreach(new \LimitIterator($file, 1) as $line) {
            $strippedFile->fwrite($line);
        }

        $this->createReportTable($file);

        $this->table = $this->_data['id'];
        $this->log->debug("New report in table `hugo_reports`.`{report}`", ['report' => $this->table]);
        $query = new Query(new Mysql(['db' => 'hugo_reports', 'table' => 'report_metadata']));
        $query->createTable($this->table);

        // easiest method is just to assign all columns to VARCHAR
        foreach($this->_data['columns'] as $column) {
            $query->addColumn($column, 'VARCHAR(45)');
        }
        $query->exec();

        $query = new Query(new MySQL(['db' => 'hugo_reports', 'table' => 'report_metadata']));
        $query->loadDataInFile($this->table, $this->_data['columns'], $strippedFile);

        // if the query executed correctly, then we can delete the CSV files from the server
        if($query->exec()) {
            unlink($strippedFile->getRealPath());
            unlink($file->getRealPath());
        }

        return $this;
    }

    private function createReportTable(\SplFileObject $file)
    {
        $query = new Query(new MySQL(['db' => 'hugo_reports', 'table' => 'report_metadata']));

        $this->table = basename($file->getFilename(), ".".$file->getExtension());
        $this->log->debug("New report in table `hugo_reports`.`{report}`", ['report' => $this->tableName]);
        $query->createTable($this->table);

        // easiest method is just to assign all columns to VARCHAR
        foreach($this->_data['columns'] as $column) {
            $query->addColumn($column, 'VARCHAR(45)');
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

    public function checkValidOrder($order)
    {
        $jsonDecoded = json_decode($order, true);

        // json_decode will return null if it fails to transform the data
        if(null === $jsonDecoded) {
            return false;
        }

        // use a soft comparison to compare the columns in the report (excluding year/local_authority) to those submitted
        $dataColumns = $this->_data['columns'];
        unset($dataColumns['year']);
        unset($dataColumns['local_authority']);
        if($jsonDecoded != array_keys($dataColumns)) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function saved()
    {
        if(!isset($this->_data['id']) || $this->_data['id'] === null) {
            return false;
        }

        $reportFromStore = $this->store->read('report_metadata', [], ['id' => $this->_data['id']]);
        return $this->toArray() == $reportFromStore;
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
            'report_about'  => $this->_data['report_about'],
            'client_id'     => $this->_data['client_id'],
            'report_order'  => $this->_data['report_order']
        ];
    }

    /**
     * @return array
     */
    public function getReportDataArray()
    {
        return array_merge([$this->_data['columns']], $this->_data['data']);
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