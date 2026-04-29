# Klasse Auth

## Zweck

`Auth` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/auth.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| private | `static db()` | Verarbeitet die Funktion db. |
| private | `static jwtCookie()` | Verarbeitet die Funktion jwt cookie. |
| private | `static insert()` | Fügt neue Daten ein. |
| private | `static edit()` | Bearbeitet bestehende Daten. |
| private | `static redirect()` | Verarbeitet die Funktion redirect. |
| private | `static expired()` | Verarbeitet die Funktion expired. |
| private | `static expires()` | Verarbeitet die Funktion expires. |
| private | `static boolValue()` | Verarbeitet die Funktion bool value. |
| private | `static readEmailHtmlFile()` | Verarbeitet die Funktion read email html file. |
| private | `static getUserFull()` | Verarbeitet die Funktion get user full. |
| private | `static replaceMailVars()` | Verarbeitet die Funktion replace mail vars. |
| private | `static mail()` | Verarbeitet die Funktion mail. |
| private | `static sendVerifyMail()` | Verarbeitet die Funktion send verify mail. |
| private | `static send2FaMail()` | Verarbeitet die Funktion send2 fa mail. |
| private | `static new2FaCode()` | Verarbeitet die Funktion new2 fa code. |
| private | `static newVerifyToken()` | Verarbeitet die Funktion new verify token. |
| private | `static newJWT()` | Verarbeitet die Funktion new j w t. |
| private | `static newUID()` | Verarbeitet die Funktion new u i d. |
| private | `static isNoLoginFile()` | Verarbeitet die Funktion is no login file. |
| private | `static auth()` | Verarbeitet Auth-Aktionen. |
| private | `static doubleUser()` | Verarbeitet die Funktion double user. |
| private | `static userObj()` | Verarbeitet die Funktion user obj. |
| private | `static metaObj()` | Verarbeitet die Funktion meta obj. |
| private | `static initTables()` | Verarbeitet die Funktion init tables. |
| public | `static init()` | Initialisiert die Klasse und legt benötigte Strukturen an. |
| public | `static initRemote()` | Verarbeitet die Funktion init remote. |
| public | `static hashPass()` | Erzeugt den Framework-Passwort-Hash. |
| public | `static get()` | Liest Daten aus der angegebenen Quelle. |
| public | `static delete()` | Löscht Daten aus der angegebenen Quelle. |
| public | `static logout()` | Beendet die aktuelle Anmeldung. |
| public | `static login()` | Prüft Login-Daten und startet die Anmeldung. |
| public | `static loginRemote()` | Verarbeitet die Funktion login remote. |
| public | `static authByToken()` | Verarbeitet die Funktion auth by token. |
| public | `static user()` | Verarbeitet die Funktion user. |
| public | `static newUser()` | Verarbeitet die Funktion new user. |
| public | `static editUser()` | Verarbeitet die Funktion edit user. |
| public | `static verifyEmail()` | Verarbeitet die Funktion verify email. |
| public | `static verify2FaCode()` | Verarbeitet die Funktion verify2 fa code. |

## Hinweise

Diese Klasse verwaltet Benutzer, JWT-Sessions, Mail-Verifizierung und 2FA-Daten. Die Konfiguration kommt aus `Vars::AUTH()`.
