<?php
/**
 * 
 * @filesource 
 * @author limao (limao777@126.com)
 * @date 2017
 */
class Lib_Http
{

    public $ctx;

    protected $_curl = null;

    public function __construct($ctx)
    {
        $this->ctx = $ctx;
    }

    public function curl($url, $message, $method = 'POST', $header = NULL, &$httpStatus = NULL)
    {
        return $this->_doCurl($url, $message, $method, $header, $httpStatus);
    }

    protected function _doCurl($url, $message, $method = 'POST', $header = NULL, &$httpStatus = NULL)
    {
        $exec_time = microtime(TRUE);
        
        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_URL, $url);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $message);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_curl, CURLOPT_CUSTOMREQUEST, $method);
        if (! is_null($header)) {
            curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->_curl, CURLOPT_TIMEOUT, 10);
        $curl_get = curl_exec($this->_curl);
        
        $status = curl_getinfo($this->_curl, CURLINFO_HTTP_CODE);
        if ($httpStatus) {
            $httpStatus = $status;
        }
        
        $this->_curl = null;
        if (!empty($this->ctx->getCookie('dd')) && $this->ctx->getCookie('dd') == sprintf('DD%s%s', date('md'), date('w'))) {
            $exec_time = microtime(TRUE) - $exec_time;
            echo "<h3>" . __METHOD__ . "</h3>";
            echo "<h4>URL: {$url}</h4>";
            echo "<h4>Method:</h4>";
            var_dump($method);
            echo "<h4>Exec time: {$exec_time}</h4>";
            echo "<h4>Header:</h4>";
            var_dump($header);
            echo "<h4>Data:</h4>";
            var_dump($message);
            echo "<h4>Response:</h4>";
            var_dump($curl_get);
            echo "<hr/>";
        }
        
        return $curl_get;
    }
}
