<?php

/**
 * GreenQL ENV-Konfiguration.
 *
 * Diese Datei wird von GreenQL über ENV("key") als PHP-Datei geladen.
 * Dadurch werden sensible Werte nicht als normale .env-Textdatei im Browser ausgeliefert,
 * sondern serverseitig durch PHP verarbeitet.
 *
 * Beispiel in GreenQL:
 * DECLARE _auth = ENV("api_auth");
 */
 
$GREENQL_ENV = [
    "api_auth" => "",
];

return $GREENQL_ENV;
