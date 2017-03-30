<?php

class Controllers_Welcome extends Controllers_BaseController{

    public function __construct($ctx, $sw = NULL){
        parent::init($ctx, $sw);
    }   

    public function index(){
		$this->ctx->redirect("test");        
 }
    
}