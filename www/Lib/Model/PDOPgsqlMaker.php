<?php
/**
 * 区别于SqlMake,专门方便PDO进行bindalue的
 */
class Lib_Model_PDOPgsqlMaker
{
    const RAW_STR_PREFIX = '&/';
    
    const LOGIC = '__logic';
    
    
    private $rawStrPrefixLength;
    
    private function __construct()
    {
        $this->rawStrPrefixLength = strlen(self::RAW_STR_PREFIX);
    }
    
    public static function getInstance()
    {
        static $instance = NULL;
        if ($instance === NULL) {
            $instance = new self();
        }
        return $instance;
    }
    
    /**
     * SQL 原始字符包装，如 CURRENT_TIMESTAMP, field + 1
     */
    public static function rawValue($val, $escapeIt = TRUE)
    {
        return
        ($escapeIt ? self::RAW_STR_PREFIX : self::RAW_STR_NO_ESCAPE_PREFIX) . $val;    
    }

    //去除字段名和值中的特殊前缀
    public static function trimData($data)
    {
        $trimData = array();
        foreach ($data as $key => $value) {
            if (strpos($key, self::RAW_STR_NO_ESCAPE_PREFIX) === 0) {
                $key = substr($key, $this->rawStrNoEscapePrefixLength);
            } else if (strpos($key, self::RAW_STR_PREFIX) === 0) {
                $key = substr($key, $this->rawStrPrefixLength);
            }

            if (strpos($value, self::RAW_STR_NO_ESCAPE_PREFIX) === 0) {
                $value = substr($value, $this->rawStrNoEscapePrefixLength);
            } if (strpos($value, self::RAW_STR_PREFIX) === 0) {
                $value = substr($value, $this->rawStrPrefixLength);
            }

            $trimData[$key] = $value;
        }

        return $trimData;
    }

    /**
     * @example
     * 单行数据
     * insert(array(
     *   'key1' => 'val1',
     *   'key2' => '&/CURRENT_TIMESTAMP',
     * ));
     *
     * output : (`key1`,`key2`) VALUES ('val1',CURRENT_TIMESTAMP)
     *
     * 多行数据
     * insert(array(
     *    'key1', 'key2',
     * ), array(
     *      array('val11', 'val12'),
     *      array('val21', 'val22')
     * ));
     * output: (`key1`,`key2`) VALUES ('val11','val12'),('val21','val22')
     *
     * @param array
     */
    public function insert($row, $rowsData = NULL)
    {
        $bind_attrs = array();
        $keys = array();
        if ($rowsData) {
            $keys = $row;
        } else {
            $keys = array_keys($row);
            $rowsData = array(array_values($row));
        }
        
        $keySql = '(' . implode(',', array_map(array($this, '_escapeName'), $keys)) . ')';
        $valSqls = array();
        foreach ($rowsData as $data) {
            $key_i = 0;
            foreach ($data as &$val) {
                // 对数组进行处理
                if (is_array($val)) {
                    $val = json_encode($val);
                }
                $key = $keys[$key_i];
                if (strpos($val, self::RAW_STR_PREFIX) === 0) {
                    $val = substr($val, $this->rawStrPrefixLength);
                    if ($val != 'CURRENT_TIMESTAMP') {
                        continue;
                    }
                } else {
                    $param_name = $this->_createBindParamName('i' . $key, $bind_attrs);
                    $bind_attrs[$param_name] = $val;
                    $val = $param_name;
                }
                ++$key_i;
            }
            $valSqls[] = '(' . implode(',', $data) . ')';
        }
        $valSql = implode(',', $valSqls);
        
        return array(" $keySql VALUES $valSql", $bind_attrs);
    }
    
    /**
     * @example
     * update(array(
     *   'key1' => 'value1',
     *   'key2' => '&/CURRENT_TIMESTAMP',
     * ));
     * output: " (`key1`,`key2`) VALUES ('value1',CURRENT_TIMESTAMP)"
     * 
     * @param array $data
     * @return string
     */
    public function update($data)
    {
        $bind_attrs = array();
        $sql = '';
        foreach ($data as $name => $val) {
            // 对数组进行处理
            if (is_array($val)) {
                $val = json_encode($val);
            }
            if (strpos($val, self::RAW_STR_PREFIX) === 0) {
                $val = substr($val, $this->rawStrPrefixLength);
                if ($val != 'CURRENT_TIMESTAMP') {
                    continue;
                } else {
                    $name = $this->_escapeName($name);
                    $sql .= "{$name}={$val},";
                }
            } else {
                $param_name = $this->_createBindParamName('u' . $name, $bind_attrs);
                $name = $this->_escapeName($name);
                $bind_attrs[$param_name] = $val;
                $sql .= "{$name}={$param_name},";
            }
        }
        return array(' SET ' . trim($sql, ','), $bind_attrs);
    }
    
    /**
     * @example
     * replace(array(
     *    'key1' => 'value1',
     *    'key2' => '&/CURRENT_TIMESTAMP',
     * ), array(
     *    'key1' => '1'
     * ));
     *
     * output: " (`key1`,`key2`) VALUES ('value1',CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE `key1`=1"
     * 
     * @param array $insData  same as method insert parameter
     * @param array $resData  replace data
     * @param string $auto_increment_field 如果为字符串，将返回last insert id
     * @return string
     */
    public function replace($insData, $resData = NULL, $auto_increment_field = NULL)
    {
        if ($resData === NULL) {
            $resData = $insData;
        }
        
        list($sql, $insert_bind_attrs) = $this->insert($insData);
        list($update_sql, $update_bind_attrs) = $this->update($resData);

        $sql .= ' ON DUPLICATE KEY UPDATE ';
        $sql .= preg_replace('@^\s*SET\s*@', '', $update_sql);
        if (is_string($auto_increment_field) && !empty($auto_increment_field)) {
            $sql .= ",{$auto_increment_field}=LAST_INSERT_ID({$auto_increment_field})";
        }
        return array($sql, array_merge($insert_bind_attrs, $update_bind_attrs));
    }
    
    /**
     * @example
     *
     * example 1.
     * where(array(
     *   'key1' => 'value1',
     *   'key2' => NULL,
     *   'key3' => array('!=' => 'value3'),
     *   'key4' => array('value4_1', 'value4_2')
     * ));
     *
     * output : WHERE `key1`='value1' AND `key2` is NULL AND `key3` != 'value3' AND (`key4` = 'value4_1' OR `key4` = 'value4_2')
     *
     * example 2.
     * where(array(
     *    array('key1' => array('like' => '%value1%')),
     *    array(
     *          'key2' => 3,
     *          'key3' => 4,
     *    )
     * ), array(
     *   'order_by' => 'id DESC',
     *   'offset' => 10,
     *   'limit' => 20,
     * ));
     * 
     * output: WHERE (`key1` like '%value1%') OR (`key2`='3' AND `key3`='4') ORDER BY id DESC LIMIT 10, 20
     *
     * @param array $where  条件数组,默认是AND关系,数字索引数组(非关系数组)表示OR关系
     * @param array $attrs 可设置的值:order_by,group_by,limit,offset
     * @return string
     */
    public function where($where, $condition_type = array(), $attrs = array())
    {
        $sql = '';
        $bind_attrs = array();
        if (!empty($where)) {
            $whereSql = $this->_where($where, $bind_attrs, $condition_type);
            if ( $whereSql) {
                $sql .= ' WHERE ' . $whereSql;    
            }
        }
        if ($attrs) {
            if (isset($attrs['group_by'])) {
                $sql .= ' GROUP BY ' . $attrs['group_by'];
            }
            
            if (isset($attrs['having'])) {
                $sql .= ' HAVING ' . $attrs['having'];
            }
            
            if (isset($attrs['order_by'])) {
                $sql .= ' ORDER BY ' . $attrs['order_by'];    
            }
            
            if (!empty($attrs['offset']) || !empty($attrs['limit'])) {
                $sql .= ' LIMIT ';
                if (isset($attrs['limit']) && $attrs['limit'] > 0) {
                    $sql .= $attrs['limit'];
                } else {
                    //默认一页10条记录
                    $sql .= 10;
                }

                $sql .= ' OFFSET ';
                if (isset($attrs['offset']) && $attrs['offset'] > 0) {
                    $sql .= $attrs['offset'];
                } else {
                    //默认从0开始
                    $sql .= 0;
                }
            }
        }
        
        return array($sql, $bind_attrs);
    }
    
    private function _where($where, &$bind_attrs, $condition_type)
    {
        if (empty($where) || ! is_array($where)) {
            return '';
        }
        
        $logic = '';
        
        if (isset($where[self::LOGIC])) {
            $logic = $where[self::LOGIC];
            unset($where[self::LOGIC]);
        }
        
        $isArray = self::_isArray($where);
        if ($isArray) {
            $conds = array_map(array($this, '_where'), $where);
            $conds = array_map(array($this, '_wrapWithBrackets'), array_filter($conds));
            if ( ! $logic) {
                $logic = 'OR';
            }
            $sql = implode(" $logic ", $conds);
            return $sql;
        }
        
        $conds = array();
        foreach ($where as $key => $val) {
            $conds[] = $this->_cond($key, $val, $bind_attrs, $condition_type);
        }
        if ( ! $logic) {
            $logic = 'AND';
        }
        $sql = implode(" $logic ", array_filter($conds));
        return $sql;
    }
    
    private function _cond($name, $val, &$bind_attrs, $condition_type)
    {
        // 对name进行处理
        if (strpos($name, '.')) {
            list($k1, $k2) = explode('.', $name);
            // 获取查询字段的数据类型
            $data_type = $condition_type[$k1][$k2];
            switch ($data_type) {
                case 'integer':
                    $name = "({$k1}->>'{$k2}')::int";
                    break;
                
                default:
                    $name = "{$k1}->>'{$k2}'";
                    break;
            }
        }

        $escape_name = $this->_escapeName($name);
        if ( ! is_array($val)) {
            if ($val === 'NULL') {
                return "{$escape_name} is NULL";
            }
            $param_name = $this->_createBindParamName($name, $bind_attrs);
            $bind_attrs[$param_name] = $val;//为bind设置值
            return "{$escape_name}={$param_name}";
        }
        
        $logic = 'OR';
        if (isset($val[self::LOGIC])) {
            $logic = $val[self::LOGIC];
            unset($val[self::LOGIC]);
        }
        
        if (self::_isHash($val)) {
            if (count($val) == 1) {
                $val_keys = array_keys($val);
                $operation = array_pop($val_keys);
                $val = $val[$operation];
                $param_name = $this->_createBindParamName($name, $bind_attrs);
                $bind_attrs[$param_name] = $val;
                return "{$name} {$operation} {$param_name}";    
            } else {
                $newVal = array();
                foreach ($val as $iKey => $iVal) {
                    $newVal[] = array($iKey => $iVal);
                }
                $val = $newVal;
            }
        }
        
        $conds = array();
        foreach ($val as $condVal) {
            if (self::_isArray($condVal)) {
                //array('val1', 'val2', ...)
                $conds[] = $this->_cond($name, $condVal, $bind_attrs);
                continue;
            } else if (self::_isHash($condVal)) {
                //array('!=' => 'val')
                $operation = array_pop(array_keys($condVal));
                $condVal = $condVal[$operation];
            } else {
                $operation = '=';
            }
            $param_name = $this->_createBindParamName($name, $bind_attrs);
            $bind_attrs[$param_name] = $condVal;
            $conds[] = "{$name} {$operation} {$param_name}";
        }
        
        if (empty($conds)) {
            return "$name = ''";
        }
        
        return '(' . implode(" $logic ", $conds) . ')';
    }
    
    private function _createBindParamName($name, $bind_attrs, $num = 0)
    {
        if(strpos($name, '->>')) {
            list($m1, $n1) = explode('->>', $name);
            if(strpos($n1, '::')) {
                list($m2, $n2) = explode('::', $n1);
                $param_name = str_replace("'", '', $m2);
                $param_name = ":".str_replace(')', '', $param_name);
            } else {
                $param_name = ":".str_replace("'", '', $n1);
            }
        } elseif (strpos($name, '|')) {
            list($m, $n) = explode('|', $name);
            $param_name = ":{$n}";
        } else {
            $param_name = ":{$name}";
        }
        if ($num > 0) {
            $param_name .= '_' . $num;
        }

        if (isset($bind_attrs[$param_name])) {
            return $this->_createBindParamName($name, $bind_attrs, $num + 1);
        } else {
            return $param_name;
        }
    }

    private function _wrapWithBrackets($str)
    {
        return '('.$str.')';    
    }
    
    //是不是纯数字索引
    private static function _isArray($val)
    {
        if (!is_array($val)) {
            return FALSE;
        }
        $keys = array_keys($val);
        foreach ($keys as $key) {
            if ( ! is_numeric($key)) {
                return FALSE;
            }
        }
        return TRUE;
    }
    
    private static function _isHash($val)
    {
        return is_array($val) && !self::_isArray($val);
    }
    
    private function _escapeName($str)
    {
        #兼容以前代码，但不做转义了
        if (strpos($str, self::RAW_STR_PREFIX) === 0) {
            return substr($str, $this->rawStrPrefixLength);
        }
        // 兼容PGSQL的特殊函数写法
        if (strpos($str, '|')) {
            list($name, $bind_name) = explode('|', $str);
            return $name;
        }
        return $str;
    }
    
    /**
     * 日志
     * @param unknown $content
     * @return boolean
     */
    private function _writelog($content)
    {
        if (!$content) return FALSE;
        
        $log_dir = "/tmp/";
        if (!file_exists($log_dir)) {
            @mkdir($log_dir, 0766);
        }
        $log_name = 'pgsql-'.date('Ymd').'.log';
        if (is_array($content)) {
            $content = json_encode($content, JSON_UNESCAPED_UNICODE);
        }
        file_put_contents($log_dir . $log_name, $content."\n", FILE_APPEND);
    }
}

