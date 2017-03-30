<?php
class Helper_Http{
    
    private $__data;
    
    public function __construct($data){
        
        $this->__data=$data;

    }
    
    public function curl(){
        $cha = curl_init();
        curl_setopt($cha, CURLOPT_URL, "www.bing.com");
        curl_setopt($cha, CURLOPT_POSTFIELDS, '');
        curl_setopt($cha, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($cha, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($cha, CURLOPT_HTTPHEADER, array('Host: www.bing.com'));
        $curl_get = curl_exec($cha);
        curl_close($cha);
        return $curl_get;
    }
    
}