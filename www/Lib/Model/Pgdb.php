<?php

/**
 * 数据库模型基类
 */
class Model_Pgdb
{
    protected static $_forceReadOnMaster = FALSE;

    protected $_readOnMaster = FALSE;
    protected $_table = NULL;
    protected $_db_zone_name = NULL;//数据库配置名称
    protected $_db_master_name = 'main';
    protected $_db_read_name = 'query';

    protected $_dberrcode = 0;
    protected $_dberrinfo = array();

    private $_db_instance = array();//数据库操作类

    protected $_sql_maker = NULL;

    protected $_data_type = array();

  
    public function __construct($table = NULL, $db_zone_name = NULL, $data_type = NULL, $debug = FALSE)
    {
//         parent::__construct();
        $this->_table = $table;
        $this->_debug = $debug;
        $this->_db_zone_name = $db_zone_name;
        $this->_sql_maker = Model_PDOPgsqlMaker::getInstance();
        $this->_data_type = $data_type;
    }

    public static function setForceReadOnMaster($bool = TRUE)
    {
        Model_Db::$_forceReadOnMaster = $bool;
    }

    //返回当前PDO数据库链接
    public function getConnection($is_read = TRUE)
    {
        return $this->_getDbInstance($is_read);
    }

    public function table($table = NULL)
    {
        if (!empty($table)) {
            $this->_table = $table;
        }

        return $this->_table;
    }

    // 根据mdoel定义，校验传入的参数类型
    private function _checkDataType($attrs)
    {
        if ($attrs && $this->_data_type) {
            foreach ($this->_data_type as $key => $val) {
                if ( is_array($val) ) {
                    foreach ($val as $k => $v) {
                        if (!empty($attrs[$key][$k]) && gettype($attrs[$key][$k]) != $v) {
                            return 'ERROR DATA TYPE:' . $key . '[' . $k . '] must be ' . $v . '!';
                        }
                    }
                } else {
                    return 'ERROR DATA TYPE:' . $key . ' must be ' . $val . '!';
                }
            }
        }
    }

    // 根据model定义，校验传入的查询参数类型
    private function _checkConType($where) {
        if ($where) {
            $keys = array_keys($where);
            foreach($keys as $k => $v) {
                if (strpos($v, '.')) {
                    list($k1, $k2) = explode('.', $v);
                    if (!is_array($where[$v])) {
                        if (!empty($this->_data_type[$k1][$k2]) && gettype($where[$v]) != $this->_data_type[$k1][$k2]) {
                            return 'ERROR CONDITION TYPE:' . $k1 . '[' . $k2 . '] must be ' . $this->_data_type[$k1][$k2] . '!';
                        }
                    }
                }
            }
        }
    }

    public function getLastId()
    {
        $db = $this->_getDbInstance(FALSE);//write

        if (!$db) {
            return FALSE;
        }

        return $db->lastInsertId("{$this->_table}_id_seq");
    }

    public function execute($sql, $bind_attrs = array())
    {
        $db = $this->_getDbInstance(FALSE);//write
        if (!$db) {
            return FALSE;
        }
        $log_sql = 'Sql:' . $sql . '***' . json_encode($bind_attrs);
        Logging::logSql($log_sql, Logging::LEVEL_DEBUG);

        $sth = $db->prepare($sql);
        $ret = $sth->execute($bind_attrs);
        $this->_dberrcode = $sth->errorCode();
        $this->_dberrinfo = $sth->errorInfo();
        if ($ret === FALSE) {
            $err = $this->_dberrinfo;
            Logging::logSql('DB_ERROR code:' . $this->_dberrcode . ' error:' . $err[2], Logging::LEVEL_ERROR);
            Logging::logSql($log_sql, Logging::LEVEL_ERROR);
            return FALSE;
        }

        return TRUE;
    }

    /**
     * 插入或者修改 on duplicate 的情况会进行修改操作
     * auto_increment_field：数据表中自增长字段名称
     */
    public function insertUpdate($ins, $upt = NULL, $auto_increment_field = NULL)
    {
        // 校验传入参数的数据类型
        $check_data = $this->_checkDataType($ins);
        if ( !empty($check_data)) {
            return $check_data;
        }
        // 校验查询条件的数据类型
        if (!empty($upt)) {
            $check_condition = $this->_checkConType($upt);
            if ( !empty($check_condition)) {
                return $check_condition;
            }            
        }

        list($insert_sql, $bind_attrs) = $this->_sql_maker->replace($ins, $upt, $auto_increment_field);
        $insert_sql = "INSERT {$this->_table} " . $insert_sql;

        $res = $this->execute($insert_sql, $bind_attrs);
        if (!$res) {
            return FALSE;
        }

        $this->_afterInsertUpdate($ins, $upt, $auto_increment_field);
        
        if (!empty($auto_increment_field)) {
            //返回last insert id
            $db = $this->_getDbInstance(FALSE);
            $last_id = $db->lastInsertId("{$this->_table}_id_seq");
            return $last_id;
        } else {
            return TRUE;
        }
    }

    public function insert($ins_arr, $return_last_id = TRUE)
    {
        if (empty($this->_table)) {
            return FALSE;
        }

        $db = $this->_getDbInstance(FALSE);//write
        if (!$db) {
            return FALSE;
        }

        // 校验传入的参数
        $check_result = $this->_checkDataType($ins_arr);
        if ( !empty($check_result)) {
            return $check_result;
        }

        list($insert_sql, $bind_attrs) = $this->_sql_maker->insert($ins_arr);
        $sql = "INSERT INTO {$this->_table} " . $insert_sql . " RETURNING id";

        if($this->_debug){
            echo $sql;
        }
        
        $log_sql = 'Sql:' . $sql . '***' . json_encode($bind_attrs);
        Logging::logSql($log_sql, Logging::LEVEL_DEBUG);

        $sth = $db->prepare($sql);
        $ret = $sth->execute($bind_attrs);
        $this->_dberrcode = $sth->errorCode();
        $this->_dberrinfo = $sth->errorInfo();

        if ($ret === FALSE) {
            $err = $this->_dberrinfo;
            Logging::logSql('DB_ERROR code:' . $this->_dberrcode . ' error:' . $err[2], Logging::LEVEL_ERROR);
            Logging::logSql($log_sql, Logging::LEVEL_ERROR);
            return FALSE;
        }

        $this->_afterInsert($ins_arr, $return_last_id);

        if ($return_last_id) {
            if($this->getLastId()){
                return $this->getLastId();
            }
            else{
               return $ret;
            }
        } else {
            return $ret;
        }
    }

    public function update($where, $upt_arr)
    {
        if (empty($this->_table)) {
            return FALSE;
        }

        $db = $this->_getDbInstance(FALSE);//write
        if (!$db) {
            return FALSE;
        }

        // 校验查询条件的数据类型
        $check_condition = $this->_checkConType($where);
        if ( !empty($check_condition)) {
            return $check_condition;
        }
        // 校验传入参数的数据类型
        $check_data = $this->_checkDataType($upt_arr);
        if ( !empty($check_data)) {
            return $check_data;
        }

        list($update_sql, $update_bind_attrs) = $this->_sql_maker->update($upt_arr);
        list($where_sql, $where_bind_attrs) = $this->_sql_maker->where($where, $this->_data_type);
        $sql = 'UPDATE ' . $this->_table . $update_sql . $where_sql;
        if($this->_debug){
            echo $sql;
        }
        $log_sql = 'Sql:' . $sql . '***' . json_encode($update_bind_attrs) . '***' . json_encode($where_bind_attrs);
        Logging::logSql($log_sql, Logging::LEVEL_DEBUG);
        
        $sth = $db->prepare($sql);
        $ret = $sth->execute(array_merge($update_bind_attrs, $where_bind_attrs));

        $this->_dberrcode = $sth->errorCode();
        $this->_dberrinfo = $sth->errorInfo();

        if ($ret) {
            $this->_afterUpdate($where);
        } else {
            $err = $this->_dberrinfo;
            Logging::logSql('DB_ERROR code:' . $this->_dberrcode . ' error:' . $err[2], Logging::LEVEL_ERROR);
            Logging::logSql($log_sql, Logging::LEVEL_ERROR);
        }

        return $ret;
    }

    public function delete ($where)
    {
        if (empty($this->_table)) {
            return FALSE;
        }
        
        $db = $this->_getDbInstance(FALSE);
        if (! $db) {
            return FALSE;
        }

        // 校验查询条件的数据类型
        $check_condition = $this->_checkConType($where);
        if ( !empty($check_condition)) {
            return $check_condition;
        }
        
        list($where_sql, $where_bind_attrs) = $this->_sql_maker->where($where, $this->_data_type);
        $sql = 'DELETE FROM ' . $this->_table . $where_sql;
        if($this->_debug){
            echo $sql;
        }
        $log_sql = 'Sql:' . $sql . '***' . json_encode($where_bind_attrs);
        
        Logging::logSql($log_sql, Logging::LEVEL_DEBUG);
        $sth = $db->prepare($sql);
        $ret = $sth->execute($where_bind_attrs);

        $this->_dberrcode = $sth->errorCode();
        $this->_dberrinfo = $sth->errorInfo();

        if ($ret) {
            $this->_afterDelete($where);
        } else {
            $err = $this->_dberrinfo;
            Logging::logSql('DB_ERROR code:' . $this->_dberrcode . ' error:' . $err[2], Logging::LEVEL_ERROR);
            Logging::logSql($log_sql, Logging::LEVEL_ERROR);
        }

        return $ret;
    }

    //根据一个字段和给定的数组值查询
    public function selectByField($field, $arr, $attrs = array())
    {
        $where = array(
            $field => $arr
        );

        $res = $this->select($where, $attrs);
        return $res;
    }

    public function select ($where = array(), $attrs = array())
    {
        if (empty($this->_table)) {
            return FALSE;
        }
        
        $db = $this->_getDbInstance(TRUE);//READ
        if (! $db) {
            return FALSE;
        }

        // 校验查询条件的数据类型
        $check_condition = $this->_checkConType($where);
        if ( !empty($check_condition)) {
            return $check_condition;
        }
        
        $select_fields = isset($attrs['select']) ? $attrs['select'] : '*';
        
        list($where_sql, $where_bind_attrs) = $this->_sql_maker->where($where, $this->_data_type, $attrs);
        $sql = "SELECT {$select_fields} FROM " . $this->_table . $where_sql;
        if($this->_debug){
            echo $sql;
        }  
        $log_sql = 'Sql:' . $sql . '***' . json_encode($where_bind_attrs);
        Logging::logSql($log_sql, Logging::LEVEL_DEBUG);
      
        $sth = $db->prepare($sql);
        $ret = $sth->execute($where_bind_attrs);
        $this->_dberrcode = $sth->errorCode();
        $this->_dberrinfo = $sth->errorInfo();
        if ($ret === FALSE) {
            $err = $this->_dberrinfo;
            Logging::logSql('DB_ERROR code:' . $this->_dberrcode . ' error:' . $err[2], Logging::LEVEL_ERROR);
            Logging::logSql($log_sql, Logging::LEVEL_ERROR);
            return FALSE;
        } else {
            $res = $sth->fetchAll(PDO::FETCH_ASSOC);
            if(is_array($res)){
                foreach ($res as $p=>$v){
                    if(!empty($res[$p]['data'])){
                        $res[$p]['data'] = json_decode($res[$p]['data'],TRUE);
                    }
                }
            }
            return $res;
        }
    }

    public function selectOne ($where = array(), $attrs = array())
    {
        $attrs['limit'] = 1;
        $attrs['offset'] = 0;
        
        $res = $this->select($where, $attrs);
        if ($res === FALSE) {
            return FALSE;
        }

        if (empty($res)) {
            return NULL;
        }

        return array_pop($res);
    }

    public function selectCount ($where = array(), $attrs = array())
    {
        if (! isset($attrs['select'])) {
            $attrs['select'] = 'COUNT(0)';
        }
        $attrs['select'] .= ' AS total';
        
        $res = $this->selectOne($where, $attrs);
        if ($res === FALSE) {
            return FALSE;
        }
        return intval($res['total']);
    }

    protected function _statementBindParams($sth, $bind_attrs)
    {
        if (empty($bind_attrs)) {
            return;
        }
        foreach ($bind_attrs as $name => $val) {
            $sth->bindValue($name, $val);
        }
    }

    protected function _getDbInstance($is_read = TRUE)
    {
        $db_choose_name = $this->_db_master_name;

        /*
        if (Model_Db::$_forceReadOnMaster || $this->_readOnMaster) {
            $is_read = FAlSE;
        }
        */

        if ($is_read) {
            $db_choose_name = $this->_db_read_name;
        }

        if (isset($this->_db_instance[$db_choose_name]) && is_object($this->_db_instance[$db_choose_name])) {
            return $this->_db_instance[$db_choose_name];
        }

        //获得一个db
        if (empty($this->_db_zone_name)) {
            return NULL;
        }

        $db_config = Config::get('DB_CONFIG');
        if (!isset($db_config[$this->_db_zone_name][$db_choose_name])) {
            return NULL;
        }
        $db_config = $db_config[$this->_db_zone_name][$db_choose_name];
        if ($db_choose_name == $this->_db_read_name) {
            $query_count = count($db_config);
            if ($query_count > 1) {
                $i = mt_rand(0, count($db_config) - 1);
                $db_config = $db_config[$i];
            } else {
                $db_config = $db_config[0];
            }
        }
        
        $dsn = "pgsql:host={$db_config['host']};dbname={$db_config['database']};port={$db_config['port']};";
        $options = array();
        $db = FALSE;
        try {
            $db = new PDO($dsn, $db_config['user'], $db_config['password'], $options);
        } catch (Exception $e) {
            $db = FALSE; 
        }

        if (!empty($db)) {
            $this->_db_instance[$db_choose_name] = $db;
            return $db;
        } else {
            Logging::logSql('DB_ERROR new PDO error! dsn:' . $dsn, Logging::LEVEL_FATAL);
            return FALSE;
        }
    }

    //返回数据库错误
    public function dbError()
    {
        if (empty($this->_dberrcode) || empty($this->_dberrinfo)) {
            return array(
                'code' => 0,
                'driver_code' => 0,
                'msg' => '',
            );
        }

        $ret = array(
            'code' => $this->_dberrcode,
            'driver_code' => @$this->_dberrinfo[1],
            'msg' => implode(',', $this->_dberrinfo),
        );
        return $ret;
    }

    protected function _afterInsertUpdate($ins, $upt, $auto_increment_field)
    {

    }

    protected function _afterInsert($ins, $return_last_id = TRUE)
    {
    }

    protected function _afterUpdate($where)
    {
    }

    protected function _afterDelete($where)
    {
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
