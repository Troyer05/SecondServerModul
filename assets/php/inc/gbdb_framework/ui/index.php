<?php
if (isset($_GET["add"])) {
  if ($_GET["add"] == "db") {
    GBDB::createDatabase($_POST["name"]);
    Ref::this_file();
  }

  if ($_GET["add"] == "table") {
    $sdb = GetForm::getDropdown($_POST["db"]);
    $arr = json_decode($_POST["array"], true);

    GBDB::createTable($sdb, $_POST["name"], $arr);

    Ref::this_file();
  }
}

$dbs = scandir("assets/DB/GBDB/");
?>

<div class="main">
    <div class="topbar">
        <h1>Datenbank-Verwaltung</h1>
    </div>

    <div class="content">
        <!-- Formular -->
        <div class="form-container">
            <h2>Neue Datenbank</h2>
            <form id="add-entry-form" method="post" action="?add=db">
                <input type="text" name="name" placeholder="Name" required>
                <button type="submit" class="btn">Datenbank erstellen</button>
            </form>
        </div>

        <br><br>
        <h2>Neue Tabelle</h2>

        <form id="add-entry-form" method="post" action="?add=table">
            <input type="text" name="name" placeholder="Name" required>

            <select name="db[]">
                <?php for ($i = 0; $i < count($dbs); $i++) { ?>
                <?php if ($dbs[$i] != "." && $dbs[$i] != "..") { ?>
                <option value="<?php echo $dbs[$i]; ?>"><?php echo $dbs[$i]; ?></option>
                <?php } ?>
                <?php } ?>
            </select>

            <input type="text" name="array" placeholder="Array" required>
            <button type="submit" class="btn">Tabelle erstellen</button>
        </form>
    </div>
</div>
