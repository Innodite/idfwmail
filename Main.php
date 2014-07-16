<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Main
 *
 * @author merma158 <jurbano@innodite.com en Innodite, C.A.>
 */
class Main {
    //put your code here
    private $directorioBase;
    private $htdocs;
    private $clases = "lib";
    // Datos que describen la version y modo de la App
    protected $version = "1.0.0";
    protected $modo = "SIMPLE"; //STANDARD
    protected $contactosFile;
    // Datos del servidor de correo
    protected $smpt_host = "mail.innodite.com";
    protected $smtp_port = 9026;
    protected $user_mail;
    protected $user_pass;
    protected $user_pref;


    public function __construct() {
        $this->directorioBase = str_replace("\\","/",dirname(__FILE__))."/";
        $this->htdocs = substr($this->directorioBase,strlen($_SERVER['DOCUMENT_ROOT']));
        /* Verifica si ya esta conectado */
        if(isset($_SESSION['idwebmail']['logged']) && $_SESSION['idwebmail']['logged'] == "S"){
            $this->user_mail = $_SESSION['idwebmail']['user_mail'];
            $this->user_pass = $_SESSION['idwebmail']['user_pass'];
            $this->contactosFile = $_SESSION['idwebmail']['contactos'];
            $this->user_pref = explode("@", $this->user_mail);
        }
    }
    
    public function __destruct() {}
    
    public function importarClase($clase,$metodo = false){
        $rutaClase = explode('.',"$this->clases.$clase");#parte de la direccion de la clase
        $count = count($rutaClase);#partes de la ruta
        $nombreClase = $rutaClase[$count - ($metodo?2:1)];#nombre de la clase
        $parametros = strstr($nombreClase,'(');
        if($parametros == ''){
          $parametros  = '()';
        }else{
          $nombreClase = substr($nombreClase,0,-strlen($parametros));
        }
        if(class_exists($nombreClase)){#si la clase existe no la importo
          return $metodo?array("$nombreClase$parametros",$rutaClase[$count-1]):true;#retorno por que ya existe
        }
        if($metodo){#si el metodo esta incluido
          $nombreMetodo = array_pop($rutaClase);#borro la parte del metodo
        }
        array_pop($rutaClase);
        $rutaImportar = implode('/',$rutaClase)."/$nombreClase.php";
        if(file_exists($this->directorioBase.'/'.$rutaImportar)){
          include_once($rutaImportar);#incluyo la clase
          if(class_exists($nombreClase) || interface_exists($nombreClase)){#si existe la clase retorno
            return $metodo?array("$nombreClase$parametros",$nombreMetodo):true;#true por que lo importo bien
          }
        }
        return false;#retorno false ya que no importo la clase
    }
    
    public function setHex($str) { 
        return strtoupper(($v = unpack('H*', $str)) ? $v[1] : '');
    }
    
    public function getUnHex($str) { 
        return pack('H*', $str);
    }
}