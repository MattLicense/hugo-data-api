<?php
/**
 * Logger.php
 * data-api
 * @author: Matthew License, B023339
 * @date:   2013/10
 */

namespace Hugo\Data\Application;

use Psr\Log\LogLevel,
    Psr\Log\LoggerInterface,
    Hugo\Data\Model\LogItem,
    Hugo\Data\Storage\DataSource,
    Hugo\Data\Storage\FileSystem,
    Psr\Log\InvalidArgumentException;

class Logger implements LoggerInterface
{

    /**
     * @var \Hugo\Data\Storage\DataSource
     */
    protected $store;

    /**
     * @var array
     */
    protected $logs = [];

    public function __construct(DataSource $store = null)
    {
        if(null === $store) {
            // if no DataSource is declared, we'll use a FileSystem
            $store = new FileSystem('/media/vagrant/www/api.hugowolferton.co.uk/logs/api.log');
        }
        $this->store = $store;
    }

    public function log($level, $message, array $context = [])
    {
        switch($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
            case LogLevel::ALERT:
            case LogLevel::WARNING:
            case LogLevel::INFO:
            case LogLevel::NOTICE:
            case LogLevel::DEBUG:
                return $this->_log($level, $message, $context);
                break;
            default:
                throw new InvalidArgumentException("Logging level {$level} doesn't exist");
        }
    }

    public function getLogs()
    {
        return $this->logs;
    }

    public function resetLogs()
    {
        $this->logs = [];
    }

    private function _log($level, $message, array $context = [])
    {
        $log = new LogItem($this->store);
        $log->set(['level' => $level, 'message' => $message, 'context' => $context]);
        $this->logs[] = trim((string)($log));
        if($log->save()) {
            return $log;
        } else {
            return false;
        }
    }

    public function emergency($message, array $context = [])
    {
        $this->_log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->_log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->_log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->_log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->_log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->_log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->_log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->_log(LogLevel::DEBUG, $message, $context);
    }

}