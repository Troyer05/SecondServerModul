<?php
include 'assets/php/inc/gbdb_framework/gbdb.php';

echo json_encode(SrvP::getData("main", "t"));
