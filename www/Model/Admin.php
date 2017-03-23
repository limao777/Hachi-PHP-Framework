<?php
class Model_Admin extends Lib_Model_Pgdb
{
    
    private static $instance;
    
    protected $data_type = array(
        'data' => array(
            'name' => 'string',
        ),
    );

    public function __construct()
    {
        parent::__construct('admin_group', 'local', $this->data_type);
    }


    public static function getInstance()
    {
 
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}