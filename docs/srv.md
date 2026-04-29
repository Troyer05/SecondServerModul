# Klasse Srv

## Zweck

`Srv` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/Srv.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static enqueue()` | Verarbeitet die Funktion enqueue. |
| public | `static getJobs()` | Verarbeitet die Funktion get jobs. |
| public | `static getJob()` | Verarbeitet die Funktion get job. |
| public | `static runScript()` | Führt ein Script aus und gibt das Ergebnis zurück. |
| public | `static auth()` | Verarbeitet Auth-Aktionen. |
| public | `static runOne()` | Verarbeitet die Funktion run one. |
| public | `static loadModule()` | Verarbeitet die Funktion load module. |
| public | `static log()` | Verarbeitet die Funktion log. |
| public | `static logs()` | Verarbeitet die Funktion logs. |
| public | `static moduleLog()` | Verarbeitet die Funktion module log. |

## Hinweise

Diese Klasse liegt auf Server 2, nimmt Anfragen von `SrvP` entgegen und führt die gewünschte Aktion lokal auf der Empfängerseite aus.
