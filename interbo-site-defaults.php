<?php
/**
 * Plugin Name: Interbo Site Defaults
 * Plugin URI: https://www.interbo.nl/
 * Description: Beheerde basisplugin voor Interbo Webdesign websites.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Interbo Webdesign
 * Author URI: https://www.interbo.nl/
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: interbo-site-defaults
 * Domain Path: /languages
 *
 * @package InterboSiteDefaults
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'INTERBO_SITE_DEFAULTS_VERSION', '0.1.0' );
define( 'INTERBO_SITE_DEFAULTS_FILE', __FILE__ );
define( 'INTERBO_SITE_DEFAULTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'INTERBO_SITE_DEFAULTS_URL', plugin_dir_url( __FILE__ ) );

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
