<?php
/**
 * Redis类的二次封装，方便从配置中直接拿到
 * @filesource 
 * @author limao (limao777@126.com)
 * @date 2017
 */
class Cache_Redis
{
    protected static $instance;
    protected $_redis;
    
    public function __construct($config_name){
        $this->_redis = new Redis();
        $servers = Config::get($config_name);
        $this->_redis->connect($servers[0],$servers[1],$servers[2],NULL,$servers[3]);
    }
    
    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new self('redis');
        }
        return self::$instance;
    }
       
    public function ping()
    {
        if (empty(self::$instance)) {
            if ( ! self::getInstance()) {
                return FALSE;
            }
        }
        return $this->_redis->ping();
    }
    
    public function get($key)
    {
        if (empty(self::$instance)) {
            if ( ! self::getInstance()) {
                return FALSE;
            }
        }
        return $this->_redis->get($key);
    }
    
    public function setex($key, $expire, $value)
    {
        if (empty(self::$instance)) {
            if ( ! self::getInstance()) {
                return FALSE;
            }
        }
        return $this->_redis->setex($key, $expire, $value);
    }
    
    //不建议使用没有expire的
    public function set($key, $value)
    {
        if (empty(self::$instance)) {
            if ( ! self::getInstance()) {
                return FALSE;
            }
        }
        return $this->_redis->setex($key, $value);
    }
    
    public function delete($keys)
    {
        if (empty(self::$instance)) {
            if ( ! self::getInstance()) {
                return FALSE;
            }
        }
        return $this->_redis->delete($keys);
    }
    
    public function lPush($key, $value)
    {
        if (empty(self::$instance)) {
            if ( ! self::getInstance()) {
                return FALSE;
            }
        }
        return $this->_redis->lPush($key, $value);
    }
       
    public function lPop($key)
    {
        if (empty(self::$instance)) {
            if ( ! self::getInstance()) {
                return FALSE;
            }
        }
        return $this->_redis->lPop($key);
    }
    
    public function rPop($key)
    {
        if (empty(self::$instance)) {
            if ( ! self::getInstance()) {
                return FALSE;
            }
        }
        return $this->_redis->rPop($key);
    }
    
}
