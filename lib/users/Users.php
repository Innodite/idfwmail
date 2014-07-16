<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Users
 *
 * @author merma158 <jurbano@innodite.com en Innodite, C.A.>
 */
class Users extends Main{
    //put your code here
    public function __construct() {
        parent::__construct();
    }
    
    public function __destruct() {
        parent::__destruct();
    }
    
    public function logIn($p=NULL){
        if ($import = $this->importarClase("extras.PHPMailer.phpmailer")){
            if (is_array($import) || $import){
                $mail = new PHPMailer(true);
                $mail->Host = $this->smpt_host;
                $mail->Port = $this->smtp_port;
                $mail->Timeout = 120;
                
                $this->user_mail = $p['username'];
                $this->user_pass = $p['password'];
                
                $mail->Username = $this->user_mail;
                $mail->Password = $this->user_pass;
                
                $smtp = null;
                if (is_null($mail->getSMTP())){
                    $smtp = $mail->getSMTPInstance();
                    if ($smtp->connected()){
                        $smtp->close();
                    }
                    $smtp->setTimeout($mail->Timeout);
                    $smtp->setDebugLevel($mail->SMTPDebug);
                    $smtp->setDebugOutput($mail->Debugoutput);
                    $smtp->setVerp($mail->do_verp);
                    
                    if($smtp->connect($mail->Host, $mail->Port, $mail->Timeout)){
                        try {
                            if ($mail->Helo) {
                                $hello = $mail->Helo;
                            }else{
                                $hello = $mail->getServerHostName();
                            }
                            $smtp->hello();
                            if($smtp->authenticate($mail->Username, $mail->Password)){
                                $_SESSION['idwebmail']['logged'] = "S";
                                $_SESSION['idwebmail']['user_mail'] = $this->user_mail;
                                $_SESSION['idwebmail']['user_pass'] = $this->user_pass;
                                
                                $contactos = array();
                                $user = explode("@", $this->user_mail);
                                $fileDir = "./lib/mail/contactos/contact-".$user[0].".bin";
                                
                                /* Leer archivo de contactos */
                                if (file_exists($fileDir)){
                                    $fp = fopen($fileDir,"rb");
                                    while (!feof($fp)) {
                                        if (($line = fgets($fp)) !== false){
                                            $partes = explode("/*/", $this->getUnHex($line));
                                            if (count($partes) == 2){
                                                array_push($contactos, array("nombre"=>$partes[0],
                                                                             "correo"=>str_replace("\u0000","",
                                                                                       str_replace("\0"    ,"", 
                                                                                       str_replace(PHP_EOL, "", $partes[1])))));
                                            }
                                        }
                                    }
                                    fclose($fp);
                                }else{ /* Crear archivo de contactos */
                                    $fp = fopen($fileDir, "wb");
                                    // Contacto de prueba
                                    fwrite($fp, $this->setHex('Javier Urbano/*/javierurbano11@gmail.com').PHP_EOL);
                                    fwrite($fp, $this->setHex('Alexander Tovar/*/guason@hotmail.com').    PHP_EOL);
                                    fclose($fp);
                                }
                                
                                $_SESSION['idwebmail']['contactos'] = $contactos;
                                
                                return array("data"=>"exito");
                            }else{
                                return array("data"=>false);
                            }
                        }catch (Exception $e){
                            return array("data"=>false);
                        }
                    }
                }
            }
        }
        return array("data"=>false);
    }
    
    public function logOut($p=NULL){
        unset($_SESSION['idwebmail']['logged']);
        session_destroy();
        return array("data"=>"exito");
    }
    
    public function isLogged($p=NULL){
        return array("data"=>"exito","log"=>isset($_SESSION['idwebmail']['logged']) ? $_SESSION['idwebmail']['logged'] : "N");
    }
    
    public function getUserMail($p=NULL){
        $user = explode("@", $this->user_mail);
        return array("data"=>"exito","correo"=>$this->user_mail,"user"=>$user[0],"version"=>$this->version,"modo"=>$this->modo);
    }
    
    public function getProgramData($p=NULL){
        return array("data"=>"exito","version"=>$this->version,"modo"=>$this->modo);
    }
}
