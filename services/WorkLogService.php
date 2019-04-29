<?php

namespace app\services;

use Yii;
use likingfit\Workflow\Util\LogService;

class WorkLogService extends LogService
{
    const INFO = 0;
    const WARN = 1;
    const ERR = 2;
    const FATAL = 3;
    private $level;
    private $logDate;
    private $logFile;
    private $logFileName;
    private $ip;
    private static $log;
    private $records = array();
    private $maxRecordCount = 1;
    private $curRecordCount = 0;
    private $processID = '0';

    function __construct()
    {
        if (!empty (self::$log)) {
            return;
        }
        $logname = "workflow_egine";
        $this->logFileName = $logname . '.log';
        $this->level = self::INFO;
        $this->ip = isset(Yii::$app->request->userIP) ? Yii::$app->request->userIP : 'cli';
        $this->processID = str_pad((function_exists('posix_getpid') ? posix_getpid() : 0), 5);

        self::$log = $this;
    }

    function __destruct()
    {
        if ($this->curRecordCount > 0) {
            if (empty ($this->logFile) || $this->logDate != date('Ymd')) {
                if (!empty ($this->logFile)) {
                    fclose($this->logFile);
                }
                $this->_setHandle();
            }

            $str = implode("\n", $this->records);
            fwrite($this->logFile, $str . "\n");
            $this->records = array();
            $this->curRecordCount = 0;
        }

        if (!empty ($this->logFile)) {
            fclose($this->logFile);
        }
    }

    private function _setHandle()
    {
        $this->logDate = date('Ymd');
        $logDir = dirname(__FILE__) . '/../logs/workflow/' . $this->logDate . '/';

        if (!file_exists($logDir)) {
            @umask(0);
            @mkdir($logDir, 0777, true);
        }

        $this->logFile = fopen($logDir . $this->logFileName, 'a');
    }

    private function _transFilename($filename)
    {
        if (!strlen($filename)) {
            return $filename;
        }

        $filename = str_replace('\\', '#', $filename);
        $filename = str_replace('/', '#', $filename);
        $filename = str_replace(':', ';', $filename);
        $filename = str_replace('"', '$', $filename);
        $filename = str_replace('*', '@', $filename);
        $filename = str_replace('?', '!', $filename);
        $filename = str_replace('>', ')', $filename);
        $filename = str_replace('<', '(', $filename);
        $filename = str_replace('|', ']', $filename);

        return $filename;
    }

    private static function _write($s)
    {
        if (!strlen($s)) {
            return false;
        }

        self::$log->records [] = $s;
        self::$log->curRecordCount++;

        if (self::$log->curRecordCount >= self::$log->maxRecordCount) {
            if (empty (self::$log->logFile) || self::$log->logDate != date('Ymd')) {
                if (!empty (self::$log->logFile)) {
                    fclose(self::$log->logFile);
                }
                self::$log->_setHandle();
            }
            $str = implode("\n", self::$log->records);
            fwrite(self::$log->logFile, $str . "\n");
            self::$log->curRecordCount = 0;
            self::$log->records = array();
        }

        return true;
    }

    /**
     * 实现log类
     * @param unknown $str
     * @return boolean
     */
    public static function log($str)
    {
        if (!strlen($str)) {
            return false;
        }

        if (empty (self::$log)) {
            self::$log = new self();
        }

        $trc = debug_backtrace();
        $s = date('Y-m-d H:i:s');
        $s .= "\tINFO\tPID:" . self::$log->processID;
        $s .= "\t" . $trc [0] ['file'];
        $s .= "\tline " . $trc [0] ['line'];
        $s .= "\tip:" . self::$log->ip . "\t";
        $s .= "\t" . $str;
        self::_write($s);

        return true;
    }
}