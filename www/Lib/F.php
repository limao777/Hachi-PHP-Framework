<?php

/**
 * 工厂单例类
 * 主要用来对一些功能复制类单例化使用
 * F::$f->ClassName->method
 */
class F
{

    protected static $_instances = array();
    public static $f = NULL;

    public static function _init()
    {
        if (is_null(self::$f)) {
            self::$f = new self;
        }
    }

    public static function getInstance($name, $args = array())
    {
        $key = $name;
//         if (!empty($args)) {
//             $key .= implode('|', $args);
//         }

        if (isset(self::$_instances[$key])) {
            return self::$_instances[$key];
        }

        $instance = NULL;
        switch (count($args)) {
            case 1:
                $instance = new $name($args[0]);
                break;
            case 2:
                $instance = new $name($args[0], $args[1]);
                break;
            case 3:
                $instance = new $name($args[0], $args[1], $args[2]);
                break;
            case 4:
                $instance = new $name($args[0], $args[1], $args[2], $args[3]);
                break;
            default:
                $instance = new $name();
                break;
        }

        self::$_instances[$key] = $instance;
        return $instance;
    }

    public function __call ($name, $args)
    {
        if (empty($args)) {
            $args = array();
        }
        
        $args = array($name, $args);
        return call_user_func_array(array(
            'F' , 
            'getInstance'
        ), $args);
    }

    public function __get($name)
    {
        return call_user_func_array(array('F', 'getInstance'), array($name));
    }
}

F::_init();
