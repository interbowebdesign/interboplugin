# Testplan v0.1

## Doel

Dit document beschrijft de handmatige kwaliteitscheck voor Interbo Site Defaults v0.1.0. Deze versie bevat alleen de pluginbasis en adminpagina.

## Testomgeving

- WordPress: LocalWP testsite
- Pluginversie: 0.1.0
- Debug: WP_DEBUG true, WP_DEBUG_LOG true, WP_DEBUG_DISPLAY false
- Debuglog: wp-content/debug.log

## Testcases

1. Activeer de plugin via Plugins > Geinstalleerde plugins.
2. Deactiveer de plugin en activeer hem opnieuw.
3. Controleer als beheerder of Interbo > Dashboard zichtbaar is.
4. Controleer of de dashboardpagina opent zonder foutmelding.
5. Controleer of de pagina pluginnaam, versie, release status, releasekanaal en beschrijving toont.
6. Log in met een niet-admin gebruiker en controleer dat Interbo niet zichtbaar is.
7. Controleer wp-content/debug.log op PHP notices, warnings of fatal errors die door de plugin worden veroorzaakt.
8. Controleer dat v0.1 geen opties, transients, custom tabellen of gebruikersmeta opslaat.
9. Controleer dat er geen updaterlogica, externe API-calls of GitHub-koppeling aanwezig is.

## Verwachte uitkomst

- De plugin activeert en deactiveert zonder foutmelding.
- Alleen beheerders zien het Interbo-menu.
- De dashboardpagina gebruikt WordPress admin UI en toont alleen v0.1-informatie.
- Er verschijnen geen nieuwe plugin-gerelateerde errors in debug.log.

## PHPCS

Gebruik de meegeleverde .phpcs.xml.dist om de PHP-code te controleren met WordPress Coding Standards:

```bash
phpcs
```

Als PHPCS of WordPress Coding Standards nog niet lokaal beschikbaar zijn, installeer die eerst in je ontwikkelomgeving.
