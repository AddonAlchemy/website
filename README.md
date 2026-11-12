# Addon Alchemy Marketplace

Marktplatz für Plugins, Erweiterungen und Software-Lizenzen: Katalog mit Suche und Filtern, Vendor-Portal mit
Release-Verwaltung, Lizenz- und Update-API, Checkout mit Abrechnung pro Studio sowie ein Admin-Panel mit
Moderations-Queue.

## Stack

- PHP 8.3, Laravel 12, Sanctum
- SQLite (Standard), MySQL oder PostgreSQL
- Vite, Tailwind CSS 4, Alpine.js, Chart.js
- PHPUnit, Laravel Pint (PSR-12)

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Für die Entwicklung startet `composer dev` Server, Queue, Logs und Vite gemeinsam.

### Demo-Zugänge

Der Seeder legt Kategorien, Plattformen, fünf Studios, 24 Plugins inklusive Releases, Bestellungen,
Abrechnungen, Bewertungen und Tickets an. Das Passwort aller Demo-Konten lautet `password`.

| Rolle  | E-Mail                       |
| ------ | ---------------------------- |
| Admin  | `admin@addonalchemy.test`    |
| Vendor | `vendor@addonalchemy.test`   |
| Kunde  | `customer@addonalchemy.test` |

In Produktivumgebungen wird nur `PlatformSeeder` und `CategorySeeder` ausgeführt; der erste Administrator
entsteht über `php artisan marketplace:admin admin@example.com`.

## Aufbau

```
app/
  Actions/        Anwendungsfälle (Checkout, Licensing, Plugins, Reviews, Support, Vendors)
  Data/           Unveränderliche Datenobjekte (PriceQuote, CatalogFilters, SettlementBreakdown, ...)
  Enums/          Status- und Typ-Enums mit Labels
  Http/           Controller, FormRequests, Middleware, API-Resources
  Models/         Eloquent-Models
  Policies/       Autorisierung
  Queries/        Katalog-Abfrage
  Services/       Preise/Sales, Warenkorb, Abrechnung, Release-Prüfung, Einstellungen, Statistiken
```

Controller validieren über FormRequests, autorisieren über Policies und delegieren an Actions. Geldbeträge
werden durchgehend als Integer in Cent gespeichert.

### Bereiche

| Bereich    | Pfad         | Zugriff                                |
| ---------- | ------------ | -------------------------------------- |
| Storefront | `/`          | öffentlich                             |
| Konto      | `/account/*` | angemeldete Benutzer                   |
| Vendor     | `/vendor/*`  | Benutzer mit Studio (teils freigeschaltet) |
| Admin      | `/admin/*`   | Administratoren                        |
| API        | `/api/v1/*`  | öffentlich bzw. Bearer-Token           |

## Releases und Downloads

Release-Archive (`.zip`, `.jar`) werden vor der Speicherung geprüft (Struktur, Pfadangaben, symbolische Links,
Verschlüsselung, Kompressionsrate) und auf einer privaten Disk abgelegt. Downloads laufen ausschließlich über
kurzlebige, signierte URLs; jede Auslieferung wird protokolliert. Neue Plugins und Versionen erscheinen erst
nach Freigabe durch die Moderation.

## Lizenz-API

| Methode    | Endpoint                         | Zweck                                             |
| ---------- | -------------------------------- | ------------------------------------------------- |
| POST       | `/api/v1/activate`               | Instanz aktivieren (IP-Limit je Lizenz)           |
| POST       | `/api/v1/deactivate`             | Instanz entfernen                                 |
| GET / POST | `/api/v1/verify`                 | Schlüssel prüfen, ohne Zustand zu ändern          |
| GET        | `/api/v1/updates/check`          | Neueste Version, Changelog, signierter Download   |
| GET        | `/api/v1/plugins`                | Öffentlicher Katalog                              |
| GET        | `/api/v1/licenses`               | Eigene Lizenzen (Token-Ability `licenses:read`)   |
| GET        | `/api/v1/licenses/{key}/download` | Download-Link (Token-Ability `downloads:read`)   |

Die interaktive Dokumentation inklusive Live-Check liegt unter `/verify`.

## Migration der Alt-Installation

```bash
php artisan marketplace:import-legacy /pfad/zur/alten/installation --category=administration
```

Der Import liest `data/db.json` und übernimmt Einstellungen, Konten (Passwort-Hashes bleiben gültig), Studios,
Produkte samt Versionen und Dateien, Lizenzen mit Aktivierungen und Subuser-Keys, Lizenz-Codes, Bestellungen,
Abrechnungen, Sale-Aktionen und Tickets. Bestehende Produkt-IDs und Lizenzschlüssel bleiben erhalten, sodass
bereits ausgelieferte Integrationen weiter funktionieren. Der Befehl ist idempotent.

## Konfiguration

Marktplatz-spezifische Werte stehen in `config/marketplace.php`. Die wichtigsten Umgebungsvariablen:

| Variable                     | Bedeutung                                                       |
| ---------------------------- | --------------------------------------------------------------- |
| `MARKETPLACE_RELEASE_DISK`   | Disk für Release-Archive (privat)                               |
| `MARKETPLACE_MEDIA_DISK`     | Disk für Icons und Screenshots                                  |
| `MARKETPLACE_AUTO_CAPTURE`   | `true`: Bestellungen sofort freischalten, sonst nach Bestätigung |
| `MARKETPLACE_DOWNLOAD_TTL`   | Gültigkeit signierter Download-Links in Sekunden                |
| `MARKETPLACE_API_RATE_LIMIT` | Anfragen pro Minute und IP auf die Lizenz-API                   |

Branding, Gebühren, Lizenzregeln, Zahlungsarten und Rechtstexte pflegen Administratoren unter
`/admin/settings`.

## Qualitätssicherung

```bash
composer test     # PHPUnit
composer lint     # Pint im Prüfmodus
composer format   # Pint
```
