<?php

require_once __DIR__ . '/gbdb_system/greenql/v1/greenql_base.trait.php';
require_once __DIR__ . '/gbdb_system/greenql/v1/greenql_parser.trait.php';
require_once __DIR__ . '/gbdb_system/greenql/v1/greenql_io.trait.php';
require_once __DIR__ . '/gbdb_system/greenql/v1/greenql_rows.trait.php';
require_once __DIR__ . '/gbdb_system/greenql/v1/greenql_runtime.trait.php';
require_once __DIR__ . '/gbdb_system/greenql/v1/greenql_execution.trait.php';

class GreenQL {
    private static string $driver = "GBDB";
    private static string $instance = "";
    private static string $defaultLogFile = "";

    use GreenQL_BaseTrait;
    use GreenQL_ParserTrait;
    use GreenQL_IoTrait;
    use GreenQL_RowsTrait;
    use GreenQL_RuntimeTrait;
    use GreenQL_ExecutionTrait;
}
