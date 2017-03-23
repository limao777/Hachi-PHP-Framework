<?php
class Controllers_BaseController extends Lib_ControllerBase{
    
    public function __construct($ctx){
    
        parent::init($ctx);

    }
    
    public function init($ctx){
        
        parent::init($ctx);
        
//        parent::_initSmarty();
        
    }
    
}