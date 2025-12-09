<?php include 'assets/php/inc/.config/_config.inc.php'; ?>

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
    $databases = array_filter(scandir($root), function($item) use ($root) {
        return is_dir($root . $item) && !in_array($item, ['.', '..']);
    });

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
                $tableFiles = array_filter(
                    scandir($root . $database),
                    fn($item) => is_file($root . $database . "/" . $item) && pathinfo($item, PATHINFO_EXTENSION) === 'json'
                );

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
