=== Interbo Site Defaults ===
Contributors: interbo
Tags: defaults, admin, interbo
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Beheerde basisplugin voor Interbo Webdesign site defaults.

== Description ==

Interbo Site Defaults is de gecontroleerde basis voor gedeelde Interbo Webdesign site defaults.

Versie 0.3 bevat een veilige admin-dashboardbasis, centrale updatecontrole via GitHub Releases en een read-only site info widget.

== Installation ==

1. Plaats de map interbo-site-defaults in /wp-content/plugins/.
2. Activeer Interbo Site Defaults via het WordPress pluginsoverzicht.
3. Open Interbo > Dashboard in de WordPress admin.

== Updates ==

De plugin controleert nieuwe versies via de GitHub Releases API van https://github.com/interbowebdesign/interboplugin.

Gebruik voor private GitHub repositories een read-only token in wp-config.php:

define( 'INTERBO_SITE_DEFAULTS_GITHUB_TOKEN', 'github_pat_...' );

Automatische achtergrondupdates staan standaard aan voor centrale releases. Dit kan per site worden uitgeschakeld in wp-config.php:

define( 'INTERBO_SITE_DEFAULTS_AUTOUPDATE', false );

Publiceer bij voorkeur een release asset met de naam interbo-site-defaults.zip. Als die ontbreekt, gebruikt de updater de GitHub zipball en normaliseert de uitgepakte map naar interbo-site-defaults.

== Development ==

Gebruik .phpcs.xml.dist voor controle met WordPress Coding Standards.

Het handmatige testplan staat in docs/testing.md.

== Changelog ==

= 0.3.0 =
* Read-only site info widget toegevoegd aan het Interbo Dashboard.
* Actuele sitegegevens toegevoegd: site URL, WordPress-versie, PHP-versie, database, actief thema, actieve plugins, tijdzone en omgeving.
* WordPress.org requirements naast de daadwerkelijke sitespecificaties gezet.
* WordPress requirements worden periodiek opgehaald vanaf wordpress.org en gecachet in een site transient.
* Requirements vergelijking toegevoegd voor PHP, database, HTTPS en webserver.

= 0.2.0 =
* Centrale updatecontrole via GitHub Releases toegevoegd.
* Update URI-header toegevoegd voor externe WordPress-updates.
* GitHub-releasegegevens worden tijdelijk gecachet in een site transient.
* Private repository downloads zijn voorbereid via een optionele tokenconstante.
* Automatische achtergrondupdates kunnen centraal worden aan- of uitgezet via constante.

= 0.1.0 =
* Eerste testbare versie met alleen een admin-dashboard.
