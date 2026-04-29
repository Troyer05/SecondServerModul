# Klasse ShareSuiteAPI

## Zweck

`ShareSuiteAPI` ist Bestandteil des SecondServerModul/GBDB Frameworks. Die Klasse kapselt zusammengehörige Funktionen, damit Projektcode kurz bleibt und zentrale Logik wiederverwendbar ist.

## Datei

`assets/php/inc/gbdb_framework/plugins/sharesuite.php`

## Verwendung

Die Klasse wird über die zentrale Framework-Konfiguration beziehungsweise die normalen Includes geladen. Öffentliche Methoden können statisch oder als Instanzmethode entsprechend ihrer Definition genutzt werden. Private und protected Methoden sind interne Helfer und sollten nur innerhalb der Klasse beziehungsweise Vererbung verwendet werden.

## Methoden

| Sichtbarkeit | Methode | Beschreibung |
|---|---|---|
| private | `static base()` | Verarbeitet die Funktion base. |
| private | `static fetch()` | Verarbeitet die Funktion fetch. |
| public | `static getTable()` | Verarbeitet die Funktion get table. |
| public | `static getTableSettings()` | Verarbeitet die Funktion get table settings. |
| public | `static getTableIndex()` | Verarbeitet die Funktion get table index. |
| public | `static getCalendar()` | Verarbeitet die Funktion get calendar. |
| public | `static getBib()` | Verarbeitet die Funktion get bib. |
| public | `static getBlogs()` | Verarbeitet die Funktion get blogs. |
| public | `static getTickets()` | Verarbeitet die Funktion get tickets. |
| public | `static newTableEntry()` | Verarbeitet die Funktion new table entry. |
| public | `static newCalendarEntry()` | Verarbeitet die Funktion new calendar entry. |
| public | `static newBlog()` | Verarbeitet die Funktion new blog. |
| public | `static editTableEntry()` | Verarbeitet die Funktion edit table entry. |
| public | `static editCalendarEntry()` | Verarbeitet die Funktion edit calendar entry. |
| public | `static editBlog()` | Verarbeitet die Funktion edit blog. |
| public | `static editBib()` | Verarbeitet die Funktion edit bib. |
| public | `static editTicket()` | Verarbeitet die Funktion edit ticket. |
| public | `static deleteTableEntry()` | Verarbeitet die Funktion delete table entry. |
| public | `static deleteCalendarEntry()` | Verarbeitet die Funktion delete calendar entry. |
| public | `static deleteBlog()` | Verarbeitet die Funktion delete blog. |
| public | `static deleteBib()` | Verarbeitet die Funktion delete bib. |

## Hinweise

Änderungen an öffentlichen Methoden können bestehende Projektseiten beeinflussen. Vor Anpassungen sollte geprüft werden, wo die Klasse bereits genutzt wird.
