# Klasse Tools

## Zweck

`Tools` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/tools.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static generatePassword()` | Verarbeitet die Funktion generate password. |
| public | `static testPasswordStrength()` | Verarbeitet die Funktion test password strength. |
| public | `static getDomainInfo()` | Verarbeitet die Funktion get domain info. |
| public | `static generateId()` | Verarbeitet die Funktion generate id. |
| public | `static generateToken()` | Verarbeitet die Funktion generate token. |
| public | `static generateTokenExt()` | Verarbeitet die Funktion generate token ext. |
| public | `static getIpCountry()` | Verarbeitet die Funktion get ip country. |
| public | `static ping4()` | Verarbeitet die Funktion ping4. |
| public | `static ping6()` | Verarbeitet die Funktion ping6. |
| public | `static qr()` | Verarbeitet die Funktion qr. |
| public | `static bar()` | Verarbeitet die Funktion bar. |
| private | `static generateTokenInternal()` | Verarbeitet die Funktion generate token internal. |
| private | `static buildToken()` | Verarbeitet die Funktion build token. |
| private | `static getFrameworkTempFile()` | Verarbeitet die Funktion get framework temp file. |
| private | `static ensureDir()` | Verarbeitet die Funktion ensure dir. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
