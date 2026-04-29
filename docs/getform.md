# Klasse GetForm

## Zweck

`GetForm` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/getForm.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| public | `static getDropdown()` | Verarbeitet die Funktion get dropdown. |
| public | `static upload()` | Verarbeitet die Funktion upload. |
| public | `static check_required_fields()` | Verarbeitet die Funktion check_required_fields. |
| public | `static createInput()` | Verarbeitet die Funktion create input. |
| public | `static checkPost()` | Verarbeitet die Funktion check post. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
