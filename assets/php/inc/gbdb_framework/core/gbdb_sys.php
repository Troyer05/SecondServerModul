<?php

require_once __DIR__ . '/gbdb_system/gbdb/v1/gbdb_schema.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v1/gbdb_transaction.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v1/gbdb_index.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v1/gbdb_storage.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v1/gbdb_crud.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v1/gbdb_maintenance.trait.php';
require_once __DIR__ . '/gbdb_system/gbdb/v1/gbdb_advanced.trait.php';

class GBDB {
    private const SCHEMA_FILE = "assets/php/inc/gbdb_framework/json/schema.json";

    private static bool $txActive = false;
    private static bool $txCommitting = false;
    private static string $txId = "";
    private static array $txOps = [];

    use GBDB_SchemaTrait;
    use GBDB_TransactionTrait;
    use GBDB_IndexTrait;
    use GBDB_StorageTrait;
    use GBDB_CrudTrait;
    use GBDB_MaintenanceTrait;
    use GBDB_AdvancedTrait;
}
