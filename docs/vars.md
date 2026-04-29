# Klasse Vars

## Zweck

`Vars` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/ENV.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static AUTH()` | Verarbeitet die Funktion a u t h. |
| public | `static __DEV__()` | Verarbeitet die Funktion __ d e v__. |
| public | `static app_version()` | Verarbeitet die Funktion app_version. |
| public | `static mRoot_url()` | Verarbeitet die Funktion m root_url. |
| public | `static mRoot_license_form()` | Verarbeitet die Funktion m root_license_form. |
| public | `static mRoot_pid()` | Verarbeitet die Funktion m root_pid. |
| public | `static mRoot_auth()` | Verarbeitet die Funktion m root_auth. |
| public | `static update_auth()` | Verarbeitet die Funktion update_auth. |
| public | `static srvp_ip()` | Verarbeitet die Funktion srvp_ip. |
| public | `static srvp_ssl()` | Verarbeitet die Funktion srvp_ssl. |
| public | `static srvp_static_key()` | Verarbeitet die Funktion srvp_static_key. |
| public | `static srvp_api_log()` | Verarbeitet die Funktion srvp_api_log. |
| public | `static srvp_log_path()` | Verarbeitet die Funktion srvp_log_path. |
| public | `static sharesuite_api_url()` | Verarbeitet die Funktion sharesuite_api_url. |
| public | `static sharesuite_api_key()` | Verarbeitet die Funktion sharesuite_api_key. |
| public | `static sharesuite_api_auth()` | Verarbeitet die Funktion sharesuite_api_auth. |
| public | `static sharesuite_sid()` | Verarbeitet die Funktion sharesuite_sid. |
| public | `static mqr_api_url()` | Verarbeitet die Funktion mqr_api_url. |
| public | `static mqr_api_key()` | Verarbeitet die Funktion mqr_api_key. |
| public | `static enable_https_redirect()` | Verarbeitet die Funktion enable_https_redirect. |
| public | `static json_path()` | Verarbeitet die Funktion json_path. |
| public | `static json_pretty()` | Verarbeitet die Funktion json_pretty. |
| public | `static sql_server()` | Verarbeitet die Funktion sql_server. |
| public | `static sql_database()` | Verarbeitet die Funktion sql_database. |
| public | `static sql_user()` | Verarbeitet die Funktion sql_user. |
| public | `static sql_password()` | Verarbeitet die Funktion sql_password. |
| public | `static sql_dev_server()` | Verarbeitet die Funktion sql_dev_server. |
| public | `static sql_dev_database()` | Verarbeitet die Funktion sql_dev_database. |
| public | `static sql_dev_user()` | Verarbeitet die Funktion sql_dev_user. |
| public | `static sql_dev_password()` | Verarbeitet die Funktion sql_dev_password. |
| public | `static reCaptcha_website_key()` | Verarbeitet die Funktion re captcha_website_key. |
| public | `static reCaptcha_secret_key()` | Verarbeitet die Funktion re captcha_secret_key. |
| public | `static crypt_data()` | Verarbeitet die Funktion crypt_data. |
| public | `static cryptKey()` | Verarbeitet die Funktion crypt key. |
| public | `static data_extension()` | Verarbeitet die Funktion data_extension. |
| public | `static init_cookies()` | Verarbeitet die Funktion init_cookies. |
| public | `static init_session()` | Verarbeitet die Funktion init_session. |
| protected | `static serverVar()` | Verarbeitet die Funktion server var. |
| public | `static this_file()` | Verarbeitet die Funktion this_file. |
| public | `static this_path()` | Verarbeitet die Funktion this_path. |
| public | `static this_uri()` | Verarbeitet die Funktion this_uri. |
| public | `static client_ip()` | Verarbeitet die Funktion client_ip. |
| public | `static DB_PATH()` | Verarbeitet die Funktion d b_ p a t h. |
| public | `static jpretty()` | Verarbeitet die Funktion jpretty. |

## Hinweise

Diese Klasse ist die zentrale ENV- und Projektkonfiguration. Änderungen wirken sich auf viele Framework-Bereiche aus.
