<?php
/**
 * Uninstall handler.
 *
 * @package InterboSiteDefaults
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_site_transient( 'interbo_site_defaults_latest_release' );
delete_site_transient( 'interbo_site_defaults_wp_requirements' );

// Version 0.3 stores no options, custom tables, or user meta.
