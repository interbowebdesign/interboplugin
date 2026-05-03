# Testplan v0.3

## Doel

Dit document beschrijft de handmatige kwaliteitscheck voor Interbo Site Defaults v0.3.0. Deze versie bevat de pluginbasis, adminpagina, centrale updatecontrole via GitHub Releases en een read-only site info widget.

## Testomgeving

- WordPress: LocalWP testsite
- Pluginversie: 0.3.0
- Debug: WP_DEBUG true, WP_DEBUG_LOG true, WP_DEBUG_DISPLAY false
- Debuglog: wp-content/debug.log

## Testcases

1. Activeer de plugin via Plugins > Geinstalleerde plugins.
2. Deactiveer de plugin en activeer hem opnieuw.
3. Controleer als beheerder of Interbo > Dashboard zichtbaar is.
4. Controleer of de dashboardpagina opent zonder foutmelding.
5. Controleer of de pagina pluginnaam, versie, release status, releasekanaal en beschrijving toont.
6. Controleer of Interbo > Dashboard de site info widget toont.
7. Controleer of de site info widget site URL, WordPress-versie, PHP-versie, database, actief thema, actieve plugins, tijdzone en omgeving toont.
8. Controleer of de WordPress specs tabel de WordPress.org requirements naast de daadwerkelijke sitespecificaties toont.
9. Controleer of onder de WordPress specs tabel de bron en laatste ophaaltijd zichtbaar zijn.
10. Controleer of de WordPress specs tabel statuswaarden toont voor PHP, database, HTTPS en webserver.
11. Controleer of Interbo > Dashboard de updatebron, GitHub API-endpoint, tokenstatus, autoupdatestatus en pakketbron toont.
12. Controleer als beheerder via Dashboard > Updates of WordPress zonder fatal errors op updates kan controleren.
13. Controleer dat er geen update wordt aangeboden wanneer de nieuwste GitHub-release gelijk is aan of lager is dan 0.3.0.
14. Maak voor een volledige updater-test een nieuwere GitHub-release aan, bijvoorbeeld v0.3.1, met bij voorkeur een asset interbo-site-defaults.zip.
15. Forceer een nieuwe updatecheck en controleer dat WordPress de nieuwe release als pluginupdate aanbiedt.
16. Controleer dat de plugin na update in de map interbo-site-defaults blijft staan.
17. Log in met een niet-admin gebruiker en controleer dat Interbo niet zichtbaar is.
18. Controleer wp-content/debug.log op PHP notices, warnings of fatal errors die door de plugin worden veroorzaakt.
19. Controleer dat v0.3 geen opties, custom tabellen of gebruikersmeta opslaat.
20. Controleer dat releasegegevens alleen tijdelijk worden opgeslagen in de site transient interbo_site_defaults_latest_release.
21. Controleer dat WordPress requirements alleen tijdelijk worden opgeslagen in de site transient interbo_site_defaults_wp_requirements.

## Private GitHub repository

Als de centrale repository private is, voeg dan voor de test een read-only GitHub token toe aan wp-config.php:

```php
define( 'INTERBO_SITE_DEFAULTS_GITHUB_TOKEN', 'github_pat_...' );
```

Gebruik een token met alleen leesrechten op repository contents.

## Automatische updates

Automatische achtergrondupdates staan standaard aan. Zet dit in wp-config.php om dit voor een testsite uit te schakelen:

```php
define( 'INTERBO_SITE_DEFAULTS_AUTOUPDATE', false );
```

## Releasepakket

Aanbevolen releasepakket:

```text
interbo-site-defaults.zip
```

De zip hoort de pluginmap interbo-site-defaults als rootmap te bevatten. Als deze asset ontbreekt, gebruikt de updater de GitHub zipball en probeert hij de uitgepakte map tijdens de upgrade te normaliseren naar interbo-site-defaults.

## Verwachte uitkomst

- De plugin activeert en deactiveert zonder foutmelding.
- Alleen beheerders zien het Interbo-menu.
- De dashboardpagina gebruikt WordPress admin UI en toont v0.3-informatie.
- De site info widget toont alleen read-only informatie.
- WordPress.org requirements staan naast de daadwerkelijke sitespecificaties.
- WordPress.org requirements worden periodiek opgehaald en vallen terug op pluginwaarden als wordpress.org niet bereikbaar is.
- WordPress kan updategegevens ophalen via GitHub Releases.
- Een nieuwere release wordt alleen aangeboden als de tagversie hoger is dan de lokaal geinstalleerde pluginversie.
- Automatische achtergrondupdates staan standaard aan, tenzij INTERBO_SITE_DEFAULTS_AUTOUPDATE op false staat.
- Er verschijnen geen nieuwe plugin-gerelateerde errors in debug.log.

## PHPCS

Gebruik de meegeleverde .phpcs.xml.dist om de PHP-code te controleren met WordPress Coding Standards:

```bash
phpcs
```

Als PHPCS of WordPress Coding Standards nog niet lokaal beschikbaar zijn, installeer die eerst in je ontwikkelomgeving.
