<?php

require_once __DIR__ . '/gbdb_system/gbdb/v2/gbdbv2_transaction.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v2/gbdbv2_instance_schema.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v2/gbdbv2_index.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v2/gbdbv2_storage.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v2/gbdbv2_crud.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v2/gbdbv2_maintenance.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v2/gbdbv2_advanced.trait.php';

class GBDBv2 {
    private const SCHEMA_FILE = "assets/php/inc/gbdb_framework/json/schema_v2.json";

    private static string $instance = "default";
    private static bool $txActive = false;
    private static bool $txCommitting = false;
    private static string $txId = "";
    private static array $txOps = [];

    use GBDBv2_TransactionTrait;
    use GBDBv2_InstanceSchemaTrait;
    use GBDBv2_IndexTrait;
    use GBDBv2_StorageTrait;
    use GBDBv2_CrudTrait;
    use GBDBv2_MaintenanceTrait;
    use GBDBv2_AdvancedTrait;
}
