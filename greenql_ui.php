<?php include 'assets/php/inc/.config/_config.inc.php'; ?>

<?php if (!Vars::__DEV__()) { echo "Seite gesperrt!"; exit; } ?>

<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GBDB UI</title>
    <link rel="stylesheet" href="assets/css/greenql_ui.css">
</head>

<body>

<div class="sidebar">
    <a href="?null">Startseite</a>
    <h2>Datenbanken</h2>

    <?php
    $root = "assets/DB/GBDB/";

    // Datenbanken finden
    $databases = GBDB::listDBs();

    foreach ($databases as $database):
    ?>
        <div class="nav-db">
            <a href="#"><?php echo htmlspecialchars($database); ?></a>

            <!-- DB löschen -->
            <a href="?dd=<?php echo urlencode($database); ?>"
               style="background-color:#e74c3c; color:#fff; padding:4px 8px; border-radius:4px; text-decoration:none;">
               Löschen
            </a>

            <div class="subnav">
                <?php
                $tableFiles = GBDB::listTables($database);

                foreach ($tableFiles as $tableFile):
                    $tableName = pathinfo($tableFile, PATHINFO_FILENAME);
                ?>
                    <a href="?show=table&db=<?php echo urlencode($database); ?>&t=<?php echo urlencode($tableName); ?>"
                       class="subnav-link">
                       <?php echo htmlspecialchars($tableName); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

</div>

<?php
// DB löschen
if (isset($_GET["dd"])) {
    $delDB = $_GET["dd"];
    GBDB::deleteAll($delDB);
    Ref::to("?null");
}

if (!isset($_GET["show"])) {
    include 'assets/php/inc/gbdb_framework/ui/index.php';
} else {
    include 'assets/php/inc/gbdb_framework/ui/table.php';
}
?>

</body>
</html>
