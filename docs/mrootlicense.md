# Klasse mRootLicense

## Zweck

`mRootLicense` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/plugins/mroot.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| private | `static fetch()` | Verarbeitet die Funktion fetch. |
| private | `static storePath()` | Verarbeitet die Funktion store path. |
| private | `static ensureStore()` | Verarbeitet die Funktion ensure store. |
| private | `static licenseData()` | Verarbeitet die Funktion license data. |
| private | `static saveLicense()` | Verarbeitet die Funktion save license. |
| private | `static currentUrl()` | Verarbeitet die Funktion current url. |
| private | `static validApiResponse()` | Verarbeitet die Funktion valid api response. |
| public | `static testLicense()` | Verarbeitet die Funktion test license. |
| public | `static setLicense()` | Verarbeitet die Funktion set license. |
| public | `static checkLicense()` | Verarbeitet die Funktion check license. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
