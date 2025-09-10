<?php
$weburl = "https://bisnisemasperak.com/";
$dbhost     = "localhost";
$dbname     = "bustanu1_dbbisnisepi";
$dbuser     = "bustanu1_dbbisnisepi";
$dbpassword = "OMEq+VG9&j#&L]wv"; # Jangan gunakan karakter $
define('SECRET', "HkdlareHEkjdlkajfaJFccHS");
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'off') { 
	header("Location:".$weburl);
}
?>