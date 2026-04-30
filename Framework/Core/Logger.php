<?php

namespace Framework\Core;

class Logger {
    private $logDir;
    // 1:DEBUG, 2:INFO, 3:ERROR
    private $threshold = 1;
    
    public function __construct() {
        $this->logDir = Container::get('PROJECT_ROOT') . '/logs';
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }
    
    public function debug($message) {
        if ($this->threshold <= 1) $this->write("DEBUG", $message);
    }
    
    public function info($message) {
        if ($this->threshold <= 2) $this->write("INFO ", $message);
    }
    
    public function error($message) {
        if ($this->threshold <= 3) $this->write("ERROR", $message);
    }
    
    private function write($level, $message) {
        $date = date('Y-m-d H:i:s');
        // ファイル名を日付入りにする変更
        $fileName = 'wwPro_' . date('Y-m-d') . '.log';
        $filePath = $this->logDir . '/' . $fileName;
        
        if (!is_scalar($message)) {
            $message = print_r($message, true);
        }
        
        $formattedMessage = "[{$date}] [{$level}] {$message}" . PHP_EOL;
        file_put_contents($filePath, $formattedMessage, FILE_APPEND | LOCK_EX);
    }
}
