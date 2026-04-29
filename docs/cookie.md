# Klasse Cookie

## Zweck

`Cookie` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/core/cookies.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| protected | `static validateName()` | Verarbeitet die Funktion validate name. |
| protected | `static options()` | Verarbeitet die Funktion options. |
| protected | `static send()` | Verarbeitet die Funktion send. |
| public | `static set()` | Speichert einen Wert in der angegebenen Quelle. |
| public | `static setSecure()` | Verarbeitet die Funktion set secure. |
| public | `static add()` | Verarbeitet die Funktion add. |
| public | `static get()` | Liest Daten aus der angegebenen Quelle. |
| public | `static delete()` | Löscht Daten aus der angegebenen Quelle. |
| public | `static edit()` | Bearbeitet bestehende Daten. |
| public | `static compare()` | Verarbeitet die Funktion compare. |
| public | `static refresh()` | Verarbeitet die Funktion refresh. |
| public | `static init()` | Initialisiert die Klasse und legt benötigte Strukturen an. |
| public | `static exists()` | Verarbeitet die Funktion exists. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
