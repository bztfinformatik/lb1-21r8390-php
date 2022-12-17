# PHP - LB

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

Falls die Konfiguration mit dem **ELK-Stack** nicht funktioniert, kann auch das Logging zu Logstash deaktiviert werden. Dazu muss im `.env` die Variable `IS_LOGGING` auf `false` gesetzt werden und dementsprechend auch die Container entfernt werden. Zu beachten ist, dass die Container auf einander angewiesen sind, weswegen die `depends_on` auch entfernt werden müssen. Die Logs werden dann **nur** in der Konsole ausgegeben.

## Anmelden

| Benutzername            | Passwort       |
| :---------------------- | :------------- |
| admin@example.com       | Test123Test123 |
| verified@example.com    | Test123Test123 |
| notverified@example.com | Test123Test123 |

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

## 2. Abgabe 📦

Es wurde das ganze **UI** mit entwürfen für das verwalten von **Sessions** umgesetzt. Zudem wurde der **ELK-Stack** komplett realisiert und funktionstüchtig gestaltet. Das Programm kann fast wie das richtige verwendet werden. Somit wurden alle [funktionalen Anforderungen](https://bztfinformatik.github.io/lernportfolio-21r8390-php/LB1/Anforderung/Funktionale/) erfüllt.

Es fehlt nur noch die **Datenbank**, **Downloads**, **Email** und der Datei Viewer. Die Datenbank wurde bewusst noch nicht gemacht, da `Mocking` verlangt wurde. Da der Download nahe zusammen mit der Datenbank liegt, wurde dieser auch noch nicht gemacht. Die Emails habe gleich wie die Datenbank als _Backendlogik_ klassifiziert und werde sie somit in er nächsten Abgabe umsetzen. Die Problematik mit dem **Datei Viewer** wird in der Dokumentation beschrieben. Die Lösung ist noch nicht gefunden.

-   [x] UI
-   [x] Sessions
-   [x] ELK-Stack
-   [x] Mocks

## 3. Abgabe 📦

Alle funktionalen [Anforderungen](https://bztfinformatik.github.io/lernportfolio-21r8390-php/LB1/Anforderung/Funktionale/), welche das letzte Mal nicht umgesetzt wurden, sind in dieser Abgabe realisiert. Die [Datenbank](mariadb/README.md) wurde mit **MariaDB** umgesetzt und die **Downloads** funktionieren. Über eine Email wird der Benutzer über eine **Statusänderung** informiert. Dazu wurde [SendGrid](https://sendgrid.com/) verwendet. Der Datei Viewer wurde mit jsTree umgesetzt. Die Dokumentation wurde um die **Datenbank** und die **Downloads** erweitert.

Trotzdem ist das Projekt noch nicht `100%` fertig. Es fehlt noch, dass das UI automatisiert **End to End** getestet wird. Dies wurde bewusst mit noch nicht gemacht, da die **Testfälle** noch nicht definiert sind. Diese werden in der nächsten Abgabe umgesetzt.

-   [x] Datenbank
    -   [x] Testdaten
-   [x] Emails
-   [x] Datei Viewer
-   [x] Download

## 4. Abgabe 📦

-   [ ] End to End Tests
-   [x] Sessions
    -   [x] Redis
-   [x] Redirects

## TODO:

-   VPS Hosting

## Nice to have:

-   Zip in Mail
