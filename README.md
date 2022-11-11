# PHP - Doku

---

> Autor: Manuel Schumacher <br>
> Modul: M133

[Doku 📚](https://bztfinformatik.github.io/lernportfolio-21r8390-php/)

[Dokumentation 📂](https://github.com/bztfinformatik/lernportfolio-21r8390-php)

## How To:

Damit der [ELK-Stack](https://www.elastic.co/what-is/elk-stack) gestartet werden kann muss Docker mit mehr Speicher versehen werden. Die Dokumentation kann hier gefunden werden: [Elastic Requirements](https://www.elastic.co/guide/en/elasticsearch/reference/current/docker.html#docker-prod-prerequisites)

> Auf Linux Speicher erhöhen: `sysctl -w vm.max_map_count=262144` <br>
> Programm starten: `docker compose up`

Die **Ports** können im `.env` eingesehen und angepasst werden.

### Fehler

Falls die Konfiguration mit dem ELK-Stack nicht funktioniert, kann auch das Logging zu Logstash deaktiviert werden. Dazu muss im `.env` die Variable `IS_LOGGING` auf `false` gesetzt werden und dementsprechend auch die Container entfernt werden. Zu beachten ist, dass die Container auf einander angewiesen sind, weswegen die `depends_on` auch entfernt werden müssen. Die Logs werden dann **nur** in der Konsole ausgegeben.

## Commits

| Icon | Meaning       |
| :--: | ------------- |
|  📚  | Content       |
|  💬  | Documentation |
|  🦄  | Refactoring   |
|  🤡  | Fix / Issue   |
|  🥞  | Mixed / Merge |
|  👷  | Automation    |
|  📝  | Setting       |
|  💥  | Hotfix        |

## TODO:

-   Datenbank
-   Emails
