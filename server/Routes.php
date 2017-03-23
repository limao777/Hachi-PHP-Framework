<?php
/**
 * 
 * @filesource 
 * @author limao (limao777@126.com)
 * @date 2017
 */
class Routes{
  
    private static $include_method_array = [
        "/^(.*)(\/test\/detail)(\d+)(.html)$/" => '${1}/${2}/id/${3}',
    ];
    
    private static $route_group = [
        'admin/www' => 'admin/www'
    ];
    
    public static function getGroup(){
        return self::$route_group;
    }
    
    public static function getRegexRoute(){
        return self::$include_method_array;
    }
    

}