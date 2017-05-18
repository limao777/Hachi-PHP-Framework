<?php
/**
 * 
 * @filesource 
 * @author limao (limao777@126.com)
 * @date 2017
 */
class Lib_ControllerBase
{

    public $ctx;

    public $sw;

    protected $_smarty;

    protected $_data;

    protected $_curl_back_error = '';

    public function init($ctx, $sw)
    {
        $this->ctx = $ctx;
        $this->sw = $sw;
        $ctx->setHeader('Server', 'CMCC');
        $ctx->setHeader('X-Powered-By', 'CMCC IOTeam/Hachi');
        $ctx->setHeader('Content-type', 'text/html;charset=utf-8');
    }

    protected function _initSmarty()
    {
        require_once __DIR__ . '/../Smarty/Smarty.class.php';
        $this->_smarty = new Smarty();
        $smarty_path = realpath(ROOTSERVER . '/../storage/smarty');
        $this->_smarty->setTemplateDir($smarty_path . '/templates/');
        $this->_smarty->setConfigDir($smarty_path . '/configs/');
        $this->_smarty->setCompileDir($smarty_path . '/templates_c/');
        $this->_smarty->setCacheDir($smarty_path . '/cache/');
        $this->_smarty->left_delimiter = '{%';
        $this->_smarty->right_delimiter = '%}';
    }

    public function smartyRender($path)
    {
        if (isset($_GET['_d'])) {
            $this->_smarty->debugging = true;
        }
        
        if (! empty($this->_data)) {
            foreach ($this->_data as $k => $v) {
                $this->_smarty->assign($k, $v);
            }
            $this->_smarty->default_modifiers = array(
                '$' => 'escape:"html"'
            );
        }
        
        return $this->_smarty->display($path);
    }

    protected function _ajax_failed($msg, $data = array(), $filter = true)
    {
        return $this->_ajax_return(500, $msg, $data, $filter);
    }

    protected function _ajax_succ($msg = '', $data = array(), $filter = true)
    {
        return $this->_ajax_return(0, $msg, $data, $filter);
    }
    
    // ajax 返回的统一方法 0 成功 其它失败
    protected function _ajax_return($code = 0, $msg = '', $data = array(), $filter = true)
    {
        $ret = array(
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        );
        
        $default_failed_ret = array(
            'code' => 500
        );
        
        $ret = @json_encode($ret);
        if (empty($ret)) {
            $ret = json_encode($default_failed_ret);
        }
        if ($filter) {
            $ret = htmlspecialchars($ret);
            $ret = str_replace("&quot;", '"', $ret);
        }
        $this->_end($ret);
    }

    protected function _curlBack($url_conf_base, $url, $params, $method)
    {
        $exec_time = microtime(TRUE);
        
        $url_conf_base = 'back.' . $url_conf_base;
        $url = ltrim($url, '/');
        $url = Config::get($url_conf_base) . '/' . $url;
        if (strtoupper($method) == 'GET' && ! empty($params)) {
            $params = http_build_query($params);
            $url = $url . '?' . $params;
        }
        $ret = F::$f->Lib_Http($this->ctx)->curl($url, $params, $method);
        $ret = json_decode($ret, TRUE);
        if ($ret['errno'] == 0) {
            return $ret['data'];
        } else {
            if (! empty($ret['error'])) {
                $this->_curl_back_error = $ret['error'];
                return FALSE;
            } else {
                $this->_curl_back_error = gettext('后端请求错误');
                return FALSE;
            }
        }
    }

    protected function _getCurlBackError()
    {
        return $this->_curl_back_error;
    }

    protected function _end($msg)
    {
        $this->ctx->end($msg);
    }
}
