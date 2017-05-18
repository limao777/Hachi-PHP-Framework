<?php
/**
 * Memcached类的二次封装，方便从配置中直接拿到
 * @filesource 
 * @author limao (limao777@126.com)
 * @date 2017
 */
class Cache_Memcached
{
    protected static $instance;
    protected $_memcache;
    
    public function __construct($config_name){
        $this->_memcache = new Memcached;
        $servers = Config::get($config_name);
        $this->_memcache->addServers($servers);
    }
    
    public static function getInstance()
    {
    
        if (! self::$instance) {
            self::$instance = new self('memcached');
        }
        return self::$instance;
    }


    public function get($key)
    {
        if (empty(self::$instance)) {
            if ( ! self::getInstance()) {
                return FALSE;
            }
        }
        return $this->_memcache->get($key);
    }
    
    public function set($key, $value, $expire)
    {
            if (empty(self::$instance)) {
            if ( ! self::getInstance()) {
                return FALSE;
            }
        }
        return $this->_memcache->set($key, $value, $expire);
    }
    
    public function delete($key)
    {
        if (empty(self::$instance)) {
            if ( ! self::getInstance()) {
                return FALSE;
            }
        }
        return $this->_memcache->delete($key);
    }
    
   
    
}