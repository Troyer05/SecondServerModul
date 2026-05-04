# mRoot – Lizenz- und Update-Plugin

## Zweck

`plugins/mroot.php` enthält die mRoot-Klassen für Lizenzprüfung und Updates. Für Details siehe:

- [`mrootlicense.md`](mrootlicense.md)
- [`mrootupdate.md`](mrootupdate.md)

## Intention

mRoot bündelt produktbezogene Update- und Lizenzlogik, ohne diese direkt in GBDB, Auth oder Projektseiten zu vermischen. Dadurch können verschiedene Produkte denselben Update-/Lizenzmechanismus nutzen.

## Typischer Einsatz

```php
$license = mRootLicense::check();
$update = mRootUpdate::update();
```

## Sicherheit

Lizenz- und Update-Keys gehören in `Vars` bzw. in sichere Configbereiche. Release-ZIPs dürfen keine produktiven Secrets enthalten.
