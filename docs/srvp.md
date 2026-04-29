# Klasse SrvP

## Zweck

`SrvP` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/srvp.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| private | `static endpoint()` | Ermittelt den API-Endpunkt. |
| private | `static request()` | Sendet eine Anfrage und verarbeitet die Antwort. |
| private | `static getToken()` | Verarbeitet die Funktion get token. |
| private | `static payloadWithToken()` | Verarbeitet die Funktion payload with token. |
| public | `static getData()` | Verarbeitet die Funktion get data. |
| public | `static addData()` | Verarbeitet die Funktion add data. |
| public | `static deleteData()` | Verarbeitet die Funktion delete data. |
| public | `static editData()` | Verarbeitet die Funktion edit data. |
| public | `static query()` | Führt eine GreenQL-Abfrage aus. |
| public | `static runScript()` | Führt ein Script aus und gibt das Ergebnis zurück. |
| public | `static auth_init()` | Initialisiert Auth auf dem Zielserver. |
| public | `static auth_login()` | Meldet einen Benutzer über den Zielserver an. |
| public | `static auth_token()` | Prüft einen Auth-Token über den Zielserver. |
| public | `static auth_get()` | Liest Auth-Daten über den Zielserver. |
| public | `static auth_user()` | Liest einen Benutzer über den Zielserver. |
| public | `static auth_newUser()` | Legt einen Benutzer über den Zielserver an. |
| public | `static auth_editUser()` | Bearbeitet einen Benutzer über den Zielserver. |
| public | `static auth_delete()` | Löscht Auth-Daten über den Zielserver. |
| public | `static auth_verifyEmail()` | Verifiziert eine E-Mail über den Zielserver. |
| public | `static auth_verify2FaCode()` | Verifiziert einen 2FA-Code über den Zielserver. |
| public | `static srv_enqueue()` | Verarbeitet die Funktion srv_enqueue. |
| public | `static srv_run_one()` | Verarbeitet die Funktion srv_run_one. |
| public | `static srv_status()` | Verarbeitet die Funktion srv_status. |
| public | `static srv_logs()` | Verarbeitet die Funktion srv_logs. |
| public | `static srv_jobs()` | Verarbeitet die Funktion srv_jobs. |

## Hinweise

Diese Klasse liegt auf Server 1 und spricht den entfernten Backend-Endpunkt `backend.php` auf Server 2 an. Lokale Prüfungen auf Server-2-Dateien sollten hier vermieden werden.
