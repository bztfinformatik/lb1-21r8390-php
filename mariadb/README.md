# Maria DB

MariaDB ist ein von der Gemeinschaft entwickelter Fork des relationalen Datenbankmanagementsystems **MySQL**, der unter der GNU GPL frei bleiben soll. MariaDB wird von Webanwendungen als Datenbankserver verwendet und wird auch von vielen großen Websites, darunter Facebook, Google und Wikipedia, als Datenbankserver genutzt. Der Vorteil von MariaDB ist, dass es eine vollständige Abwärtskompatibilität zu MySQL bietet, aber auch einige neue Funktionen und Verbesserungen enthält. Zudem ist es **Open Source** und unter der GNU GPL lizenziert, was bedeutet, dass es **kostenlos** und frei verfügbar ist.

## Adminer

Adminer ist eine **Datenbankverwaltung**, die in einer einzigen Datei geschrieben ist. Sie ist in PHP geschrieben und kann auf einem beliebigen Webserver betrieben werden. Adminer ist ein Open-Source-Tool, das unter der GNU GPL veröffentlicht wurde. Adminer ist eine Alternative zu phpMyAdmin. Der Ordner (`/adminer`) wird für das Theme verwendet.

## Initscripts

Die Init-Skripte werden verwendet, um SQL beim **aufsetzen** der Datenbank auszuführen. Die Skripte werden in der `docker-entrypoint-initdb.d`-Ordner ausgeführt. Die Skripte werden in der Reihenfolge ausgeführt, in der sie im Ordner aufgelistet sind. Mithilfe der Benennung können Sie die Reihenfolge der Ausführung der Skripte festlegen. Wenn Sie die Datenbank **nicht neu** erstellen, werden die Skripte beim Starten des Containers nicht ausgeführt.

### Scripte

| Nummer | Definition                         |
| :----: | ---------------------------------- |
|  100   | Konfigurationen & Administratives  |
|  200   | Erstellt die Tabellen              |
|  300   | Fügt die Daten in die Tabellen ein |

## Sysdata

Damit die Daten auch beim beenden des Containers **nicht verloren** gehen, werden die Daten in den Ordner `sysdata` gespeichert. Dieser Ordner wird beim Starten des Containers wieder eingelesen. Der Ordner beinhaltet das ganze Verzeichnis, welches von MariaDB verwendet wird und ist somit ständig **synchronisiert**.
