<div class="dashboard-header">
    <nav class="navbar navbar-expand-lg bg-white fixed-top"
        style="background-color: <?php echo SETTINGS[FARBE][P]; ?> !important;color:white !important;">
        <a class="navbar-brand" href="main.php" style="color: white; text-transform: none !important;"><?php echo SETTINGS[NAME][P]; ?></a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse " id="navbarSupportedContent">
            <ul class="navbar-nav ml-auto navbar-right-top">
                <li class="nav-item dropdown nav-user">
                    <a class="nav-link nav-user-img" href="#" id="navbarDropdownMenuLink2" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false" style="color:  white;">
                        <img src="assets/images/avatar-1.jpg" alt="" class="user-avatar-md rounded-circle"></a>
                    <div class="dropdown-menu dropdown-menu-right nav-user-dropdown"
                        aria-labelledby="navbarDropdownMenuLink2">
                        <div class="nav-user-info" style="background-color: #0b9514 !important;">
                            <h5 class="mb-0 text-white nav-user-name"><?php echo USER["name"]; ?></h5>
                        </div>
                        <a class="dropdown-item" href="account.php"><i class="fas fa-user mr-2"></i>Account</a>     
                        
                        <?php if (RECHTE["recht"] == "admin") { ?>
                            <a class="dropdown-item" href="users.php"><i class="fas fa-users mr-2"></i>Benutzerverwaltung</a>
                            <a class="dropdown-item" href="rechte.php"><i class="fas fa-users mr-2"></i>Rechte</a>
                            <a class="dropdown-item" href="settings.php"><i class="fas fa-cog mr-2"></i>Einstellungen</a>
                            <a class="dropdown-item" href="nav.php"><i class="fas fa-list mr-2"></i>Navigation</a>

                            <?php if (SETTINGS[REGISTRIERUNG][P]) { ?>
                                <a class="dropdown-item" href="register_liste.php"><b class="mr-2 circle-badge" style="color: <?php if ($regs == 0) { echo '#0b9514;'; } else { echo '#ff0000;'; } ?>"><?php echo $regs; ?></b>Registrierungen</a>
                            <?php } ?>
                        <?php } ?>

                        <a class="dropdown-item" href="list.php"><i class="fas fa-list mr-2"></i>Inhalte</a> 
                        <a class="dropdown-item" href="chats.php"><i class="fab fa-rocketchat mr-2"></i>Chats</a> 
                            
                        <a class="dropdown-item" href="logout.php"><i class="fas fa-power-off mr-2"></i>Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</div>
