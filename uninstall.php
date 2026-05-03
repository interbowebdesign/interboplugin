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

// Version 0.2 stores no options, custom tables, or user meta.
