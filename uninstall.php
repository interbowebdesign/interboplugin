<?php
/**
 * Uninstall handler.
 *
 * @package InterboSiteDefaults
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Version 0.1 stores no options, transients, custom tables, or user meta.
