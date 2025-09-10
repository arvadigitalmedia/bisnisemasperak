<?php
$weburl = "https://domainanda.com/";
$dbhost     = "localhost";
$dbname     = "";
$dbuser     = "";
$dbpassword = ""; # Jangan gunakan karakter $
define('SECRET', "");
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') { 
	header("Location:".$weburl);
}
?>