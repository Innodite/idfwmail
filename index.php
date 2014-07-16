<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Descripción de Base
 *
 * @author merma158 <jurbano@innodite.com en Innodite, C.A.> | javierurbano11@gmail.com
 */

ini_set('default_socket_timeout',300);

include_once './Main.php';
include_once './Manejador.php';

$manager = new Manejador();
print_r($manager->out());
?>