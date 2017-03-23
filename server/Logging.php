<?php
class Logging{
    
    const LEVEL_DEBUG = 'debug';
    
    const LEVEL_INFO = 'info';
    
    const LEVEL_WARNING = 'warning';
    
    const LEVEL_ERROR = 'error';
    
    const LEVEL_FATAL = 'fatal';
    
    public static function logSql($msg, $level){
        $date_time = date('Y-m-d H:i:s');
        $content = $date_time . '#' . $level . '#' . $msg . PHP_EOL;
        file_put_contents(SERVERLOGFILE, $content, FILE_APPEND);
    }

    
}