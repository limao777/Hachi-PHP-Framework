<?php
/**
 * 表单验证类
 * 通过配置，方便进行一系列的验证。
 * '验证组' => {
 *      '表单参数|表单别名' => 'required|validate[1,3,4]'
 * }
 * 默认方法
 *
 * 错误返回：
 * 支持全部检查和部分检查
 * '表单参数' => '错误信息'
 */

class Lib_FormValidation
{
    /**
     * 默认的验证器,标准方法都在这里边
     */
    protected $_default_validator = NULL;

    /*
     * 保存用户自定义的一些验证方法，在调用的时候优先处理
     * 方法名 callable
     */
    protected $_user_methods = array();


    /*
     * 要检查的from group
     * group_name => array(
     *      'param_name|别名' => array(
     *          'methodname' => array(
     *              默认参数
     *          )
     *      )
     * )
     */
    protected $_form_groups = array();

    protected $query = array();

    protected $_errors = array();

    protected $_params = array();

    /**
     * 构造函数，一次初始化一个组
     */
    public function __construct($conf = array(), $query)
    {
        $this->_default_validator = new Lib_FormValidation_Validator();
        if (empty($conf)) {
            return;
        }

        $this->query = $query;
        
        foreach ($conf as $group_name => $group_conf) {
            $this->addFormGroup($group_name, $group_conf);
        }
    }

    /**
     * 验证请求并返回受验证的值
     * 成功返回组中设定的参数值，否则返回FALSE
     */
    public function validate($name, $data = NULL, $check_all = TRUE)
    {
        $this->_errors = array();
        $this->_params = array();
        if (empty($this->_form_groups[$name])) {
            return array();
        }

        $form_group = $this->_form_groups[$name];

        if (is_null($data)) {
            $data = $this->_getDefaultData();
        }

        $vals = array();
        $have_false = FALSE;
        foreach ($form_group as $param_name => $methods) {
            $val = $this->_callMethods($param_name, $methods, $data);
            if ($val === FALSE) {
                $have_false = TRUE;
                if (!$check_all) {
                    break;
                } else {
                    continue;
                }
            }

            list($param_name, $param_val) = $val;
            $vals[$param_name] = $param_val;
        }

        if ($have_false) {
            return FALSE;
        } else {
            return $vals;
        }
    }

    /**
     * 返回分解后的key, val 数组
     * array(key, val)
     */
    protected function _callMethods($param_name, $methods, $data)
    {
        $param_name_arr = explode('|', $param_name);
        $param_name = $param_name_arr[0];
        $alias_name = $param_name;
        if (count($param_name_arr) > 1) {
            $alias_name = $param_name_arr[1];
        }
        $val = @$data[$param_name];

        //后面会取数据值
        $this->_params[$param_name] = $val;

        if (empty($methods)) {
            return array($param_name, $val);
        }

        $have_error = FALSE;
        foreach ($methods as $method => $params) {
            //找可以执行的方法

            $method_callable = array($this->_default_validator, $method);
            if (isset($this->_user_methods[$method])) {
                $method_callable = $this->_user_methods[$method];
            }

            if (!is_callable($method_callable)) {
                $have_error = TRUE;
                $this->_errors[$param_name] = "{$method} 验证方法不存在";
                break;
            }

            $params = array_merge(array($alias_name, $val), $params);
            $ret = call_user_func_array($method_callable, $params);
            if (is_bool($ret) && $ret) {
                continue;
            }

            //检查发生了错误
            $have_error = TRUE;
            $this->_errors[$param_name] = $ret;
            break;
        }

        if ($have_error) {
            return FALSE;
        } else {
            return array($param_name, $val);
        }
    }

    protected function _getDefaultData()
    {
        return $this->query;
    }

    /**
     * 返回最后一次验证的错误信息
     * param_name => error
     */
    public function error()
    {
        if(is_array($this->_errors)){
            return array_pop($this->_errors);
        }else{
            return $this->_errors;
        }
    }
    
    public function errors()
    {
        return $this->_errors;
    }

    /**
     * 返回检查过的变量值
     */
    public function params()
    {
        return $this->_params;
    }

    public function addFormGroup($name, $conf)
    {
        if (empty($conf)) {
            return;
        }
        $group_conf = array();

        foreach ($conf as $param_name => $c) {
            if (!isset($group_conf[$param_name])) {
                $group_conf[$param_name] = array();
            }
            if (empty($c)) {
                continue;
            }
            $methods = explode('|' , $c);
            foreach($methods as $m) {
                $m = trim($m);
                $default_param_pos = strpos($m, '[');
                if ($default_param_pos === FALSE) {
                    //没有默认参数
                    $group_conf[$param_name][$m] = array();
                } else {
                    $params = array();
                    if ($m[$default_param_pos + 1] != ']') {
                        $params_str = substr($m, $default_param_pos + 1, -1);
                        $params = explode(',', $params_str);
                    }
                    $m = substr($m, 0, $default_param_pos);
                    $group_conf[$param_name][$m] = $params;
                }
            }
        }

        if (isset($this->_form_groups[$name])) {
            $this->_form_groups[$name] = array_merge($this->_form_groups[$name], $group_conf);
        } else {
            $this->_form_groups[$name] = $group_conf;
        }

        return;
    }

    /**
     * callable的参数为:
     * $name:字段名称
     * $data:具体的数据，如果没有传入值为NULL 
     * ...后面为具体定义参数
     */
    public function addValidateMethod($name, $callable)
    {
        if (!is_callable($callable)) {
            return;
        }

        $this->_user_methods[$name] = $callable;
    }
}

class Lib_FormValidation_Validator
{
    public function required($name, $data)
    {
        if (strlen($data)<=0) {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 不能为空";
            }else{
                return "'{$_name}' cannot empty";
            }
        } else {
            return TRUE;
        }
    }

    //最小值
    public function min($name, $data, $min)
    {
        $min = (int) $min;
        if (!is_null($data) && $data >= $min) {
            return TRUE;
        } else {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 最小值为{$min}";
            }else{
                return "{$_name} can not less than {$min}";
            }
        }
    }

    public function max($name, $data, $max)
    {
        $max = (int) $max;
        if (!is_null($data) && $data <= $max) {
            return TRUE;
        } else {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 最大值为{$max}";
            }else{
                return "{$_name} can not bigger than {$max}";
            }
        }
    }

    public function integer($name, $data)
    {
        if (empty($data)) {
            return TRUE;
        }

        $data = (int) $data;
        if (!empty($data)) {
            return TRUE;
        } else {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 必须为整数";
            }else{
                return "{$_name} must be integer";
            }
        }
    }

    public function numeric($name, $data)
    {
        if (empty($data)) {
            return TRUE;
        }

        if (is_numeric($data)) {
            return TRUE;
        } else {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 必须为数字";
            }else{
                return "{$_name} must be numeric";
            }
        }
    }

    //用户名的检查,只能是字母数字和下划线
    public function username($name, $data)
    {
        if (empty($data)) {
            return TRUE;
        }

        $pattern = '/^[_\w\d]+$/i';
        if (!preg_match($pattern, $data))
        {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 只能是字母数字和下划线";
            }else{
                return "{$_name} is not a valid word";
            }
        }

        return TRUE;
    }

    public function valid_url($name, $str)
    {
        if (empty($str)) {
            return TRUE;
        }

        $pattern = "/^(http|https|ftp):\/\/([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?\/?/i";
        if (!preg_match($pattern, $str))
        {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 不是有效的URL地址";
            }else{
                return "{$_name} is not a valid URL";
            }
        }

        return TRUE;
    }

    /**
     * 密码校验，不能是全数字或全字母
     */
    public function valid_password($name, $str)
    {
        if (empty($str)) {
            return TRUE;
        }

       $pattern = "/(?![0-9]+$)(?![a-zA-Z]+$)(?![~!@#$%^&*.]+$)(?![0-9~!@#$%^&*.]+$)(?![a-zA-Z~!@#$%^&*.]+$)[0-9a-zA-Z~!@#$%^&*.]{8,}/";
        if (!preg_match($pattern, $str))
        {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 不符合密码规则";
            }else{
                return "{$_name} is not a valid password";
            }
        }

        return TRUE;
    }
    
    /**
     * 匹配格式：
     * 11位手机号码
     * 3-4位区号，7-8位直播号码，1－4位分机号
     * 如：12345678901、1234-12345678-1234
     * 2015-03-23新增美国电话格式:xxx-xxx-xxxx、+xx-xxx-xxx-xxxx或没有连接字符串
     */
    public function valid_phone($name, $str)
    {
        if (empty($str)) {
            return TRUE;
        }

//         $pattern = "((\d{11})|^((\d{7,8})|(\d{4}|\d{3})-(\d{7,8})|(\d{4}|\d{3})-(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1})|(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1}))$)";

        $pattern = "(^\+?((\d{1}|\d{2})-?\d{3}-?\d{3}-?\d{4})$|(\d{11})|^((\d{7,8})|(\d{4}|\d{3})-(\d{7,8})|(\d{4}|\d{3})-(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1})|(\d{7,8})-(\d{4}|\d{3}|\d{2}|\d{1}))$)";
       
        if (!preg_match($pattern, $str))
        {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 不是有效的电话号码";
            }else{
                return "{$_name} is not a valid phone-number";
            }
        }

        return TRUE;
    }

    public function valid_datetime($name, $str)
    {
        if (empty($str)) {
            return TRUE;
        }

        if (Helper_Date::isDateTime($str) && !Helper_Date::isEmpty($str)) {
            return TRUE;
        } else {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 不是有效的日期";
            }else{
                return "{$_name} is not a valid datetime";
            }
        }

    }
    
    public function valid_date($name, $str)
    {
    	if (empty($str)) {
    		return TRUE;
    	}
    
    	if (Helper_Date::isDate($str) && !Helper_Date::isEmpty($str)) {
    		return TRUE;
    	} else {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 不是有效的日期";
            }else{
                return "{$_name} is not a valid date";
            }
    	}
    
    	}

    public function valid_email($name, $str)
    {
        if (empty($str)) {
            return TRUE;
        }

        $pattern = '/^(\w)+([\.\w\-]+)*@([\w\-])+((\.\w+){1,5})$/i';
        if(!preg_match($pattern, $str)) {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}' 不是有效的电子邮箱";
            }else{
                return "{$_name} is not a valid email";
            }
        }

        return TRUE;
    }

    //字符串最小长度
    public function min_length($name, $str, $len)
    {
        if (empty($str)) {
            return TRUE;
        }
        
        if (!empty($str) && strlen($str) >= $len)
        {
            return TRUE;
        } else {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}'至少输入{$len}个字符";
            }else{
                return "{$_name} requires at least {$len} characters";
            }
        }
    }

    //字符串最大长度
    public function max_length($name, $str, $len)
    {
        if (empty($str) || strlen($str) <= $len)
        {
            return TRUE;
        } else {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}'最多输入{$len}个字符";
            }else{
                return "{$_name} can not more than {$len} characters";
            }
        }
    }

    //字符串最小长度
    public function utf8min_length($name, $str, $len)
    {
        if (!empty($str) && mb_strlen($str, 'UTF-8') >= $len)
        {
            return TRUE;
        } else {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}'至少输入{$len}个字符";
            }else{
                return "{$_name} requires at least {$len} characters";
            }
        }
    }

    //字符串最大长度
    public function utf8max_length($name, $str, $len)
    {
        if (empty($str) || mb_strlen($str, 'UTF-8') <= $len)
        {
            return TRUE;
        } else {
            $_name = FVLangMap::NameMap($name);
            if(FVLangMap::isCN()) {
                return "'{$_name}'最多输入{$len}个字符";
            }else{
                return "{$_name} can not more than {$len} characters";
            }
        }
    }

    //检查是否是一个合理的价格
    public function price($name, $data)
    {
        if (empty($data)) {
            return TRUE;
        }

        $_name = FVLangMap::NameMap($name);

        if (!is_numeric($data)) {
            if(FVLangMap::isCN()) {
                return "'{$_name}' 不是有效的数字";
            }else{
                return "{$_name} is not a valid number";
            }
        }

        $nums = explode('.', $data);
        if (count($nums) < 2) {
            //证明是整数
            return TRUE;
        }
        if (strlen($nums[1]) > 2) {
            if(FVLangMap::isCN()) {
                return "'{$_name}' 只能精确到小数点2位";
            }else{
                return "{$_name} can only accurate to 0.01";
            }
        }

        return TRUE;
    }
}

class FVLangMap{

    private $_lang = 'zh_CN';   //默认语言为中文

    private static $_instance = null;
    private static $_map = null;

    public function __construct(){
//         $this->_lang = isset($_COOKIE['LANG']) ? $_COOKIE['LANG'] : 'zh_CN';
        //2016-11兼容新的多语言格式
//         $request = Yaf_Dispatcher::getInstance()->getRequest();
//         // 解析语言包
//         $query = $request->getQuery();
//         $query_keys = array_keys($query);
//         $query_uri = $query_keys[0];
//         $arr = explode('/', $query_uri);
//         $this->_lang = $arr[0];
        
//         if ($this->_lang == 'en') {
//             $this->_lang = 'en_US';
//             putenv("LANG=en_US");
//             setlocale(LC_ALL, "en_US");
//             //bindtextdomain("en_US", dirname(dirname(__FILE__)) . '/locale');
//             //textdomain("en_US");
//             //bind_textdomain_codeset("en_US", 'UTF-8');
//         }
//TODO 2017-03-15新框架按需加载
        if(empty(self::$_map)){
            self::$_map = $this->_loadMap();
        }
    }

    /**
     * 获取当前语言环境
     */
    public function getLang(){
        return $this->_lang;
    }

    /**
     * 从项目配置目录的lang.php文件中加载$FieldMap
     */
    private function _loadMap(){
        return NULL;
    }

    public static function isCN(){
        if(!self::$_instance){
            self::$_instance = new FVLangMap();
        }

        return self::$_instance->getLang();
    }

    public static function NameMap($name){
        if(!self::$_instance){
            self::$_instance = new FVLangMap();
        }

        if(self::$_instance->isCN()){
            //中文环境，直接返回$name（假设_map都是以中文作为key）
            return $name;
        }

        return isset(self::$_map[$name]) ? self::$_map[$name] : $name;
    }

}