<?php

require_once __DIR__ . '/gbdb_system/greenql/v2/greenqlv2_base.trait.php';
require_once __DIR__ . '/gbdb_system/greenql/v2/greenqlv2_parser.trait.php';
require_once __DIR__ . '/gbdb_system/greenql/v2/greenqlv2_io.trait.php';
require_once __DIR__ . '/gbdb_system/greenql/v2/greenqlv2_rows.trait.php';
require_once __DIR__ . '/gbdb_system/greenql/v2/greenqlv2_runtime.trait.php';
require_once __DIR__ . '/gbdb_system/greenql/v2/greenqlv2_execution.trait.php';

class GreenQLv2 {
    private static string $driver = "GBDB";
    private static string $instance = "";
    private static string $defaultLogFile = "";

    use GreenQLv2_BaseTrait;
    use GreenQLv2_ParserTrait;
    use GreenQLv2_IoTrait;
    use GreenQLv2_RowsTrait;
    use GreenQLv2_RuntimeTrait;
    use GreenQLv2_ExecutionTrait;
}
