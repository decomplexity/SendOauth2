<?php
namespace decomplexity\SendOauth2;
session_start(); 
require 'vendor/autoload.php';
//require_once 'decomplexity/sendoauth2/src/SendOauth2D.php';

new SendOauth2D ('1');

$_SESSION = array(); 
?>
