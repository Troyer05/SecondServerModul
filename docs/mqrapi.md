# Klasse MqrApi

## Zweck

`MqrApi` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/plugins/museumqr.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| private | `static base()` | Verarbeitet die Funktion base. |
| private | `static fetch()` | Verarbeitet die Funktion fetch. |
| public | `static getFeedback()` | Verarbeitet die Funktion get feedback. |
| public | `static getObjects()` | Verarbeitet die Funktion get objects. |
| public | `static getObject()` | Verarbeitet die Funktion get object. |
| public | `static getSettings()` | Verarbeitet die Funktion get settings. |
| public | `static getLangs()` | Verarbeitet die Funktion get langs. |
| public | `static getTours()` | Verarbeitet die Funktion get tours. |
| public | `static newApiKey()` | Verarbeitet die Funktion new api key. |
| public | `static saveSettings()` | Verarbeitet die Funktion save settings. |
| public | `static newLang()` | Verarbeitet die Funktion new lang. |
| public | `static newTour()` | Verarbeitet die Funktion new tour. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
