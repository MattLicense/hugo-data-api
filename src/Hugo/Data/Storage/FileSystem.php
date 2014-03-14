<?php
/**
 * FileSystem.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/10
 */

namespace Hugo\Data\Storage;

use Hugo\Data\Application\Logger;
use Hugo\Data\Model\ModelInterface,
    Hugo\Data\Exception\IOException;

class FileSystem implements DataSource
{

    /**
     * @var string
     */
    private $file;


    private $log;

    /**
     * @param $file
     * @throws \Hugo\Data\Exception\IOException
     */
    public function __construct($file)
    {
        $this->file = $file;

        $dir = dirname($this->file);
        if(!is_dir($dir)) {
            mkdir($dir, 0777, true); // create the specified directory and any intermediate.
        }
        if(!file_exists($this->file)) {
            touch($this->file);
        }
        if(!is_writable($this->file)) {
            throw new IOException("File: {$this->file} is not writable");
        }

    }

    /**
     * Opens blank file for writing
     *
     * @param ModelInterface $model
     * @return bool
     */
    public function create(ModelInterface $model)
    {
        $file = new \SplFileObject($this->file, 'w');
        return (null === $file->fwrite((string)$model)) ? false : true;
    }

    /**
     * Reads a line ($id) from a file, if $id is null, reads the last line from the file
     *
     * @param $id
     * @param array $opts
     * @return string
     */
    public function read($id = null, array $opts = [])
    {
        $file = new \SplFileObject($this->file, 'r'); // open the file for reading

        if(null === $id) {
            $file->seek($file->getSize()); // seek to the end of the file to return the last line
        } else {
            $file->seek($id); // seek to the specified line number
        }

        return $file->current();
    }

    /**
     * Reads an entire file.
     * NOTE: NOT RECOMMENDED FOR LARGE FILES
     *
     * @param array $opts
     * @return string
     */
    public function readAll(array $opts = [])
    {
        $contents = '';
        $file = new \SplFileObject($this->file, 'r');

        while (!$file->eof()) {
            $contents .= $file->fgets().PHP_EOL;
        }

        return $contents;
    }

    /**
     * Opens file for writing (leaving previous content)
     *
     * @param ModelInterface $model
     * @return bool
     */
    public function update(ModelInterface $model)
    {
        $file = new \SplFileObject($this->file, 'a');
        return (null === $file->fwrite(PHP_EOL.(string)$model)) ? false : true;
    }

    /**
     * Deletes a file
     *
     * @param ModelInterface $model
     * @return bool
     * @throws \Hugo\Data\Exception\IOException
     */
    public function delete(ModelInterface $model = null)
    {
        if(null !== $model) {
            throw new IOException('FileSystem::delete() should only be called to delete a file', 500);
        }

        return unlink($this->file);
    }

}