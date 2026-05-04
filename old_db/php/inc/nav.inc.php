<div class="nav-left-sidebar sidebar-dark" style="background-color: #222529 !important;">
    <div class="menu-list">
        <nav class="navbar navbar-expand-lg navbar-light" style="color: white !important;">
            <a class="d-xl-none d-lg-none" href="#"></a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav flex-column">
                    <li class="nav-divider">
                        Navigation
                    </li>

                    <?php foreach (NAV_GROUPS as $i => $r) { ?>
                        <?php if (str_contains($r["rechte"], RECHTE["recht"]) || $r["rechte"] == "" || RECHTE["recht"] == "admin") { ?>
                            <li class="nav-item ">
                                <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false"
                                    data-target="#submenu-<?php echo $i; ?>" aria-controls="submenu-<?php echo $i; ?>">
                                    <?php echo $r["titel"]; ?> <span
                                        class="badge badge-success"></span></a>
                                <div id="submenu-<?php echo $i; ?>" class="collapse submenu">
                                    <ul class="nav flex-column">
                                        <?php foreach (NAV_INDEX as $j => $t) { ?>
                                            <?php if ($t["nid"] == $r["nid"]) { ?>
                                                <?php
                                                $ok = false;

                                                if ($t["rechte"] != "") {
                                                    $uRechte = explode(",", RECHTE["recht"]);

                                                    for ($n = 0; $n < count($uRechte); $n++) {
                                                        if (str_contains($t["rechte"], $uRechte[$n])) {
                                                            $ok = true;
                                                            break;
                                                        }
                                                    }

                                                    if (RECHTE["recht"] == "admin") {
                                                        $ok = true;
                                                    }

                                                    if (RECHTE["recht"] == "") {
                                                        $ok = false;
                                                    }
                                                } else {
                                                    $ok = true;
                                                }

                                                if ($ok) {
                                                ?>
                                                <li class="nav-item">
                                                    <a class="nav-link" href="<?php echo $t["dest"]; ?>"><?php echo $t["titel"]; ?></a>
                                                </li>
                                            <?php }} ?>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </li>
                        <?php } ?>
                    <?php } ?>

                    <?php if (SETTINGS[PROJEKT_ZEIT][P]) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false"
                                data-target="#submenu-p" aria-controls="submenu-p"><i
                                    class="fas fa-chart-bar"></i>Projektzeit Tracking</a>
                            <div id="submenu-p" class="collapse submenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link" href="track.php">Tracken</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="p_show.php">Ansicht</a>
                                    </li>
                                    
                                    <?php if (RECHTE["recht"] == "admin") { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="projekte.php">Projekte</a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (SETTINGS[ZEITERFASSUNG][P]) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false"
                                data-target="#submenu-z" aria-controls="submenu-z"><i
                                    class="fas fa-clock"></i>Zeiterfassung</a>
                            <div id="submenu-z" class="collapse submenu">
                                <ul class="nav flex-column">
                                    <?php if (RECHTE["recht"] == "admin") { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="zeiten.php">Zeiten</a>
                                        </li>

                                        <li class="nav-item">
                                            <a class="nav-link" href="z_settings.php">Einstellungen</a>
                                        </li>
                                    <?php } ?>

                                    <?php if (SETTINGS[ZEIT_DIGITAL][P]) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="zf.php">Zeit erfassen</a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (SETTINGS[TICKET_SYSTEM][P]) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false"
                                data-target="#submenu-tick" aria-controls="submenu-tick"><i
                                    class="fas fa-tags"></i>Ticketsystem</a>
                            <div id="submenu-tick" class="collapse submenu">
                                <ul class="nav flex-column">
                                    <?php if (RECHTE["recht"] == "admin") { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="tickets.php">Tickets</a>
                                        </li>
                                    <?php } ?>

                                    <li class="nav-item">
                                        <a class="nav-link" href="newTicket.php">Ticket stellen</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="mTickets.php">Meine Tickets</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (SETTINGS[PLUGINS][P]) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false"
                                data-target="#submenu-pl" aria-controls="submenu-pl"><i
                                    class="fas fa-cloud"></i>Web-Plugins</a>
                            <div id="submenu-pl" class="collapse submenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link" href="plugin.php">Plugins</a>
                                    </li>

                                    <?php if (RECHTE["recht"] == "admin") { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="p_settings.php">Einstellungen</a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (SETTINGS[NEWS_BLOG][P]) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false"
                                data-target="#submenu-zblo" aria-controls="submenu-zblo"><i
                                    class="fas fa-bell"></i>News Blogs</a>
                            <div id="submenu-zblo" class="collapse submenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link" href="new_blog.php">Blog schreiben</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="main.php">Blogs ansehen</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (SETTINGS[ANTRAG_STELLUNG][P]) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false"
                                data-target="#submenu-ant" aria-controls="submenu-ant"><i
                                    class="fas fa-file"></i>Antr&auml;ge</a>
                            <div id="submenu-ant" class="collapse submenu">
                                <ul class="nav flex-column">
                                    <li class="nav-item">
                                        <a class="nav-link" href="new_antrag.php">Antrag Stellen</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="antrag_liste.php">Meine Antr&auml;ge</a>
                                    </li>

                                    <?php if (RECHTE["recht"] == "admin") { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="antrage.php">Antr&auml;ge ansehen</a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (SETTINGS[AUTOS][P]) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false"
                                data-target="#submenu-autos" aria-controls="submenu-autos"><i
                                    class="fas fa-car"></i>Auto Pool</a>
                            <div id="submenu-autos" class="collapse submenu">
                                <ul class="nav flex-column">
                                    <?php if (RECHTE["recht"] == "admin") { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="autos.php">Autos</a>
                                        </li>
                                    <?php } ?>

                                    <li class="nav-item">
                                        <a class="nav-link" href="rAuto.php">Reservieren</a>
                                    </li>

                                    <li class="nav-item">
                                        <a class="nav-link" href="hAuto.php">Historie</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>

                    <?php if (SETTINGS[JOBSY][P]) { ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" data-toggle="collapse" aria-expanded="false"
                                data-target="#submenu-jobsy" aria-controls="submenu-jobsy"><i
                                    class="fas fa-building"></i>Jobsy</a>
                            <div id="submenu-jobsy" class="collapse submenu">
                                <ul class="nav flex-column">
                                    <?php if (RECHTE["recht"] == "admin") { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" href="j_settings.php">Einstellungen</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="j_mails.php">Mail Texte</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="j_stellen.php">Stellen</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="j_fragen.php">Score Fragen</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" href="j_logs.php">Logs</a>
                                        </li>
                                    <?php } ?>

                                    <li class="nav-item">
                                        <a class="nav-link" href="j_new.php">Bewerbungen</a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </nav>
    </div>
</div>
