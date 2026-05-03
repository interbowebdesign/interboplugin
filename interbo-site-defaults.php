<?php
/**
 * Plugin Name: Interbo Site Defaults
 * Plugin URI: https://www.interbo.nl/
 * Description: Beheerde basisplugin voor Interbo Webdesign websites.
 * Version: 0.2.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Interbo Webdesign
 * Author URI: https://www.interbo.nl/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: interbo-site-defaults
 * Domain Path: /languages
 * Update URI: https://github.com/interbowebdesign/interboplugin
 *
 * @package InterboSiteDefaults
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'INTERBO_SITE_DEFAULTS_VERSION', '0.2.0' );
define( 'INTERBO_SITE_DEFAULTS_FILE', __FILE__ );
define( 'INTERBO_SITE_DEFAULTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'INTERBO_SITE_DEFAULTS_URL', plugin_dir_url( __FILE__ ) );
define( 'INTERBO_SITE_DEFAULTS_SLUG', 'interbo-site-defaults' );
define( 'INTERBO_SITE_DEFAULTS_REQUIRES_WP', '6.0' );
define( 'INTERBO_SITE_DEFAULTS_TESTED_UP_TO', '6.9' );
define( 'INTERBO_SITE_DEFAULTS_REQUIRES_PHP', '7.4' );
define( 'INTERBO_SITE_DEFAULTS_RELEASE_STATUS', 'development' );
define( 'INTERBO_SITE_DEFAULTS_RELEASE_CHANNEL', 'beta' );
define( 'INTERBO_SITE_DEFAULTS_UPDATE_URI', 'https://github.com/interbowebdesign/interboplugin' );
define( 'INTERBO_SITE_DEFAULTS_GITHUB_RELEASES_API', 'https://api.github.com/repos/interbowebdesign/interboplugin/releases' );

if ( ! defined( 'INTERBO_SITE_DEFAULTS_GITHUB_TOKEN' ) ) {
	define( 'INTERBO_SITE_DEFAULTS_GITHUB_TOKEN', '' );
}

if ( ! defined( 'INTERBO_SITE_DEFAULTS_AUTOUPDATE' ) ) {
	define( 'INTERBO_SITE_DEFAULTS_AUTOUPDATE', true );
}

require_once INTERBO_SITE_DEFAULTS_PATH . 'includes/class-interbo-plugin.php';

/**
 * Returns the main plugin instance.
 *
 * @return Interbo_Plugin
 */
function interbo_site_defaults() {
	return Interbo_Plugin::instance();
}

interbo_site_defaults();
