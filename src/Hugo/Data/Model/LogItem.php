<?php
/**
 * LogItem.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/10
 */

namespace Hugo\Data\Model;


use Hugo\Data\Storage\DataSource,
    Hugo\Data\Storage\FileSystem,
    Psr\Log\InvalidArgumentException;

class LogItem implements ModelInterface {

    /**
     * @var \Hugo\Data\Storage\DataSource
     */
    private $source;

    /**
     * @var array
     */
    protected $_data = [];


    public function __construct(DataSource $source = null, $id = null)
    {
        if(null === $source) {
            // if no DataSource is declared, we'll use a FileSystem
            $source = new FileSystem('/media/vagrant/www/api.hugowolferton.co.uk/logs/api.log');
        }
        $this->source = $source;
    }

    /**
     * @return bool
     */
    public function saved()
    {
        //
    }

    /**
     * @return bool
     */
    public function save()
    {
        return $this->source->update($this);
    }

    /**
     * @return bool
     */
    public function delete()
    {
        return $this->source->delete($this);
    }

    /**
     * @param array $attr
     * @return array
     * @throws \Psr\Log\InvalidArgumentException
     */
    public function set(array $attr)
    {
        if(!(array_key_exists('level', $attr) && array_key_exists('message', $attr) && array_key_exists('context', $attr))) {
            throw new InvalidArgumentException("LogItem must define 'level', 'message', 'context'");
        }
        if(!is_array($attr['context'])) {
            throw new InvalidArgumentException("Context must be an array");
        }

        return $this->_data = $attr;
    }

    /**
     * @return string
     */
    public function getLogLevel()
    {
        return $this->_data['level'];
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->interpolate($this->_data['message'], $this->_data['context']);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $date = new \DateTime('now', new \DateTimeZone("Europe/London"));
        return  [
            'date'     => $date->format('Y-m-d H:i:s')
        ,   'level'    => strtoupper($this->_data['level'])
        ,   'message'  => $this->interpolate($this->_data['message'], $this->_data['context'])
        ];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $date = new \DateTime('now', new \DateTimeZone("Europe/London"));
        return sprintf("%s - %s - %s"
                , $date->format('Y-m-d H:i:s')
                , strtoupper($this->_data['level'])
                , $this->interpolate($this->_data['message'], $this->_data['context'])
        );
    }

    /**
     * @param string $message
     * @param array $context
     * @return string
     */
    private function interpolate($message, array $context = [])
    {
        $replace = [];
        foreach ($context as $key => $value) {
            $replace['{'.$key.'}'] = $value;
        }
        
        if($message instanceof \DateTime) {
            $message = $message->format('Y-m-d H:i:s');
        }

        return strtr((string)($message), $replace);
    }

    public function __get($key)
    {
        return $this->_data[$key];
    }

    public function __set($key, $value)
    {
        return $this->_data[$key] = $value;
    }

} 
