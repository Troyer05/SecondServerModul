<?php
include 'assets/php/inc/gbdb_framework/gbdb.php';
include 'assets/php/inc/Srv.php';
include 'functions.php';

define("STATIC_AUTH", hash('sha256', hash('sha256', "dein-statischer-schlüssel")));
define("DB_ARCH", "GBDB"); // Or SQL

?>
