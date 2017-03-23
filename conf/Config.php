<?php
class Config{
    
    private static $config_arr = [];
    public static $instance;
    
    public function __construct(){
        //确定环境
        //默认是生产环境
        $default_env = 'production';
        $current_env = $default_env;
        $allowed_env = array('dev', 'test', 'pre', 'production');
        
        $dev_hostnames = array(
            'dev_name',
        );
        $test_hostnames = array(
            'test_name',
        );
        $pre_hostnames = array(
            'pre_name',
        );
        
        $current_host_name = php_uname('n');
        
        if (in_array($current_host_name, $dev_hostnames)) {
            $current_env = 'dev';
        } elseif (in_array($current_host_name, $test_hostnames)) {
            $current_env = 'test';
        }elseif (in_array($current_host_name, $pre_hostnames)) {
            $current_env = 'pre';
        }
        
        if (!in_array($current_env, $allowed_env)) {
            $current_env = $default_env;
        }
        
        //加载默认的配置信息
        require_once(ROOTCONFIG . '/alldefault.php');
        
        //再按需加载
        require_once(ROOTCONFIG . '/' . $current_env . '.php');
    }
    
    public static function add($conf = []){
        self::$config_arr = array_merge(self::$config_arr, $conf);
    }
    
    public static function get($key, $default = NULL){
        $config = self::$config_arr;
        
        $path = explode('.', $key);
        foreach ($path as $key) {
            $key = trim($key);
            if (empty($config) || !isset($config[$key])) {
                return $default;
            }
            $config = $config[$key];
        }
        
        return $config;
    }
    
    public static function getInstance(){
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

