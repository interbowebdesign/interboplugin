<?php
/**
 * Main plugin class.
 *
 * @package InterboSiteDefaults
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core plugin bootstrap.
 */
final class Interbo_Plugin {

	/**
	 * Plugin singleton instance.
	 *
	 * @var Interbo_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Admin class instance.
	 *
	 * @var Interbo_Admin|null
	 */
	private $admin = null;

	/**
	 * Gets the plugin singleton instance.
	 *
	 * @return Interbo_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->register_hooks();
		$this->load_admin();
	}

	/**
	 * Prevents cloning the singleton.
	 */
	private function __clone() {}

	/**
	 * Prevents unserializing the singleton.
	 */
	public function __wakeup() {
		_doing_it_wrong(
			__METHOD__,
			esc_html__( 'Unserializing instances of this class is not allowed.', 'interbo-site-defaults' ),
			'0.1.0'
		);
	}

	/**
	 * Registers core hooks.
	 *
	 * @return void
	 */
	private function register_hooks() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Loads plugin translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'interbo-site-defaults',
			false,
			dirname( plugin_basename( INTERBO_SITE_DEFAULTS_FILE ) ) . '/languages'
		);
	}

	/**
	 * Loads admin-only functionality.
	 *
	 * @return void
	 */
	private function load_admin() {
		if ( ! is_admin() ) {
			return;
		}

		require_once INTERBO_SITE_DEFAULTS_PATH . 'includes/admin/class-interbo-admin.php';

		$this->admin = new Interbo_Admin();
		$this->admin->add_hooks();
	}
}
