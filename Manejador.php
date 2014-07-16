<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Manejador
 *
 * @author merma158 <jurbano@innodite.com en Innodite, C.A.>
 */
class Manejador extends Main{
    //put your code here
    private $parametros;
    
    public function __construct() {
        parent::__construct();
        $this->parametros = array_merge($_GET,$_POST);
    }
    
    public function __destruct() {}
    
    public function out(){
        $respuesta = array("STDOUT"=>true,"msg"=>"no_found");
        
        if(($objMet = $this->importarClase($this->parametros['import'],true))){
            
            $respuesta = array("STDOUT"=>true,"msg"=>"no_loged");
            session_start();
            if (isset($_SESSION['idwebmail']['logged'])){
                eval("\$obj = new $objMet[0];");
                eval("\$respuesta = \$obj->$objMet[1](\$this->parametros);");
                $respuesta['STDOUT'] = true;
                $respuesta['msg']    = "exito";
            }else{
                if ($objMet[0] == "Users()" && $objMet[1] == "logIn"){
                    eval("\$obj = new $objMet[0];");
                    eval("\$respuesta = \$obj->$objMet[1](\$this->parametros);");
                    $respuesta['STDOUT'] = true;
                    $respuesta['msg']    = "exito";
                }
            }
        }
        return json_encode($respuesta);
    }
}