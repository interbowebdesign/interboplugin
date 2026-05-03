<?php
/**
 * GitHub release updater.
 *
 * @package InterboSiteDefaults
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin update checks against the central GitHub release source.
 */
class Interbo_Updater {

	/**
	 * Site transient key for the latest release payload.
	 *
	 * @var string
	 */
	const CACHE_KEY = 'interbo_site_defaults_latest_release';

	/**
	 * Preferred release asset name.
	 *
	 * @var string
	 */
	const PACKAGE_ASSET_NAME = 'interbo-site-defaults.zip';

	/**
	 * Registers updater hooks.
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_filter( 'update_plugins_github.com', array( $this, 'filter_update_response' ), 10, 4 );
		add_filter( 'plugins_api', array( $this, 'filter_plugin_information' ), 10, 3 );
		add_filter( 'upgrader_pre_download', array( $this, 'download_private_package' ), 10, 4 );
		add_filter( 'upgrader_source_selection', array( $this, 'normalize_package_source' ), 11, 4 );
		add_action( 'delete_site_transient_update_plugins', array( $this, 'clear_release_cache' ), 10, 0 );
	}

	/**
	 * Adds update data for this plugin when a newer GitHub release exists.
	 *
	 * @param array|false $update      Existing update data.
	 * @param array       $plugin_data Plugin headers.
	 * @param string      $plugin_file Plugin file.
	 * @param string[]    $locales     Installed locales.
	 * @return array|false
	 */
	public function filter_update_response( $update, $plugin_data, $plugin_file, $locales ) {
		unset( $plugin_data, $locales );

		if ( plugin_basename( INTERBO_SITE_DEFAULTS_FILE ) !== $plugin_file ) {
			return $update;
		}

		$release = $this->get_latest_release();

		if ( is_wp_error( $release ) ) {
			return $update;
		}

		$latest_version = $this->get_release_version( $release );

		if ( '' === $latest_version ) {
			return $update;
		}

		if ( '' === $this->get_package_url( $release ) ) {
			return $update;
		}

		// Always return metadata so WordPress can populate both `response` and `no_update`.
		// Without this, external plugins may miss the auto-update toggle in the plugins table.
		return $this->prepare_update_data( $release, $plugin_file );
	}

	/**
	 * Adds release information to the WordPress plugin details modal.
	 *
	 * @param false|object|array $result Existing plugin API result.
	 * @param string             $action Plugin API action.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object|array
	 */
	public function filter_plugin_information( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! is_object( $args ) || empty( $args->slug ) || INTERBO_SITE_DEFAULTS_SLUG !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();

		if ( is_wp_error( $release ) ) {
			return $result;
		}

		$latest_version = $this->get_release_version( $release );
		$release_notes  = ! empty( $release['body'] ) ? $release['body'] : __( 'Geen release notes beschikbaar.', 'interbo-site-defaults' );

		return (object) array(
			'name'          => __( 'Interbo Site Defaults', 'interbo-site-defaults' ),
			'slug'          => INTERBO_SITE_DEFAULTS_SLUG,
			'version'       => $latest_version,
			'author'        => __( 'Interbo Webdesign', 'interbo-site-defaults' ),
			'homepage'      => INTERBO_SITE_DEFAULTS_UPDATE_URI,
			'requires'      => INTERBO_SITE_DEFAULTS_REQUIRES_WP,
			'tested'        => INTERBO_SITE_DEFAULTS_TESTED_UP_TO,
			'requires_php'  => INTERBO_SITE_DEFAULTS_REQUIRES_PHP,
			'last_updated'  => $release['published_at'],
			'download_link' => $this->get_package_url( $release ),
			'sections'      => array(
				'description' => wp_kses_post( wpautop( esc_html__( 'Beheerde basisplugin voor Interbo Webdesign site defaults.', 'interbo-site-defaults' ) ) ),
				'changelog'   => $this->format_release_notes( $release_notes ),
			),
		);
	}

	/**
	 * Downloads private GitHub packages when an optional token is configured.
	 *
	 * @param bool        $reply      Existing download short-circuit value.
	 * @param string      $package    Package URL.
	 * @param WP_Upgrader $upgrader   Upgrader instance.
	 * @param array       $hook_extra Extra arguments passed by the upgrader.
	 * @return bool|string|WP_Error
	 */
	public function download_private_package( $reply, $package, $upgrader, $hook_extra ) {
		unset( $upgrader );

		if ( false !== $reply || ! $this->is_current_plugin_upgrade( $hook_extra ) || '' === $this->get_github_token() || ! $this->is_github_package_url( $package ) ) {
			return $reply;
		}

		if ( ! function_exists( 'wp_tempnam' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$temporary_file = wp_tempnam( $package );

		if ( ! $temporary_file ) {
			return new WP_Error(
				'interbo_updater_temp_file_failed',
				__( 'Er kon geen tijdelijk updatebestand worden aangemaakt.', 'interbo-site-defaults' )
			);
		}

		$response = wp_remote_get( $package, $this->get_request_args( 300, $temporary_file, 'application/octet-stream' ) );

		if ( is_wp_error( $response ) ) {
			wp_delete_file( $temporary_file );

			return $response;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			wp_delete_file( $temporary_file );

			return new WP_Error(
				'interbo_updater_download_failed',
				__( 'Het updatepakket kon niet worden gedownload vanaf GitHub.', 'interbo-site-defaults' )
			);
		}

		return $temporary_file;
	}

	/**
	 * Normalizes GitHub archive folders to the installed plugin directory name.
	 *
	 * @global WP_Filesystem_Base $wp_filesystem WordPress filesystem API.
	 *
	 * @param string|WP_Error $source        Package source path.
	 * @param string          $remote_source Remote working directory path.
	 * @param WP_Upgrader     $upgrader      Upgrader instance.
	 * @param array           $hook_extra    Extra arguments passed by the upgrader.
	 * @return string|WP_Error
	 */
	public function normalize_package_source( $source, $remote_source, $upgrader, $hook_extra ) {
		global $wp_filesystem;

		unset( $upgrader );

		if ( is_wp_error( $source ) || ! $this->is_current_plugin_upgrade( $hook_extra ) ) {
			return $source;
		}

		$expected_directory = dirname( plugin_basename( INTERBO_SITE_DEFAULTS_FILE ) );
		$source_path        = untrailingslashit( $source );

		if ( $expected_directory === basename( $source_path ) ) {
			return $source;
		}

		if ( ! $wp_filesystem || ! $wp_filesystem->is_dir( $source_path ) ) {
			return $source;
		}

		$normalized_source = trailingslashit( $remote_source ) . $expected_directory;

		if ( $wp_filesystem->exists( $normalized_source ) ) {
			$wp_filesystem->delete( $normalized_source, true );
		}

		if ( ! $wp_filesystem->move( $source_path, $normalized_source, true ) ) {
			return new WP_Error(
				'interbo_updater_source_normalization_failed',
				__( 'Het updatepakket kon niet naar de juiste pluginmap worden voorbereid.', 'interbo-site-defaults' )
			);
		}

		return trailingslashit( $normalized_source );
	}

	/**
	 * Gets the latest release from cache or GitHub.
	 *
	 * @return array|WP_Error
	 */
	public function get_latest_release() {
		$cached_release = get_site_transient( self::CACHE_KEY );

		if ( is_array( $cached_release ) && ! empty( $cached_release['tag_name'] ) ) {
			return $cached_release;
		}

		$response = wp_remote_get( INTERBO_SITE_DEFAULTS_GITHUB_RELEASES_API, $this->get_request_args( 10 ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return new WP_Error(
				'interbo_updater_release_request_failed',
				__( 'De GitHub-releasegegevens konden niet worden opgehaald.', 'interbo-site-defaults' )
			);
		}

		$releases = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $releases ) ) {
			return new WP_Error(
				'interbo_updater_invalid_release_response',
				__( 'De GitHub-releasegegevens hebben geen geldig JSON-formaat.', 'interbo-site-defaults' )
			);
		}

		$release = $this->select_latest_release( $releases );

		if ( is_wp_error( $release ) ) {
			return $release;
		}

		set_site_transient( self::CACHE_KEY, $release, 6 * HOUR_IN_SECONDS );

		return $release;
	}

	/**
	 * Clears the cached GitHub release when WordPress clears plugin update data.
	 *
	 * @return void
	 */
	public function clear_release_cache() {
		delete_site_transient( self::CACHE_KEY );
	}

	/**
	 * Builds update data for WordPress core.
	 *
	 * @param array  $release     Sanitized release data.
	 * @param string $plugin_file Plugin file.
	 * @return array
	 */
	private function prepare_update_data( $release, $plugin_file ) {
		return array(
			'id'           => INTERBO_SITE_DEFAULTS_UPDATE_URI,
			'slug'         => INTERBO_SITE_DEFAULTS_SLUG,
			'plugin'       => $plugin_file,
			'version'      => $this->get_release_version( $release ),
			'url'          => $release['html_url'],
			'package'      => $this->get_package_url( $release ),
			'tested'       => INTERBO_SITE_DEFAULTS_TESTED_UP_TO,
			'requires'     => INTERBO_SITE_DEFAULTS_REQUIRES_WP,
			'requires_php' => INTERBO_SITE_DEFAULTS_REQUIRES_PHP,
			'autoupdate'   => (bool) INTERBO_SITE_DEFAULTS_AUTOUPDATE,
		);
	}

	/**
	 * Selects the newest valid release from the GitHub response.
	 *
	 * @param array $releases Raw release response.
	 * @return array|WP_Error
	 */
	private function select_latest_release( $releases ) {
		$selected_release = null;
		$selected_version = '';

		foreach ( $releases as $release ) {
			if ( ! is_array( $release ) || ! empty( $release['draft'] ) ) {
				continue;
			}

			if ( ! $this->include_prereleases() && ! empty( $release['prerelease'] ) ) {
				continue;
			}

			$version = $this->normalize_version( isset( $release['tag_name'] ) ? $release['tag_name'] : '' );

			if ( '' === $version ) {
				continue;
			}

			if ( null === $selected_release || version_compare( $version, $selected_version, '>' ) ) {
				$selected_release = $release;
				$selected_version = $version;
			}
		}

		if ( null === $selected_release ) {
			return new WP_Error(
				'interbo_updater_no_valid_release',
				__( 'Er is geen geldige GitHub-release gevonden.', 'interbo-site-defaults' )
			);
		}

		return $this->sanitize_release( $selected_release );
	}

	/**
	 * Sanitizes release data received from GitHub.
	 *
	 * @param array $release Raw release data.
	 * @return array
	 */
	private function sanitize_release( $release ) {
		return array(
			'tag_name'     => sanitize_text_field( $this->get_string_value( isset( $release['tag_name'] ) ? $release['tag_name'] : '' ) ),
			'name'         => sanitize_text_field( $this->get_string_value( isset( $release['name'] ) ? $release['name'] : '' ) ),
			'body'         => sanitize_textarea_field( $this->get_string_value( isset( $release['body'] ) ? $release['body'] : '' ) ),
			'html_url'     => esc_url_raw( $this->get_string_value( isset( $release['html_url'] ) ? $release['html_url'] : INTERBO_SITE_DEFAULTS_UPDATE_URI ) ),
			'zipball_url'  => esc_url_raw( $this->get_string_value( isset( $release['zipball_url'] ) ? $release['zipball_url'] : '' ) ),
			'published_at' => sanitize_text_field( $this->get_string_value( isset( $release['published_at'] ) ? $release['published_at'] : '' ) ),
			'assets'       => $this->sanitize_assets( isset( $release['assets'] ) ? $release['assets'] : array() ),
			'prerelease'   => ! empty( $release['prerelease'] ),
		);
	}

	/**
	 * Sanitizes GitHub release assets.
	 *
	 * @param array $assets Raw release assets.
	 * @return array
	 */
	private function sanitize_assets( $assets ) {
		$sanitized_assets = array();

		if ( ! is_array( $assets ) ) {
			return $sanitized_assets;
		}

		foreach ( $assets as $asset ) {
			if ( ! is_array( $asset ) || empty( $asset['browser_download_url'] ) ) {
				continue;
			}

			$sanitized_assets[] = array(
				'name'                 => sanitize_file_name( $this->get_string_value( isset( $asset['name'] ) ? $asset['name'] : '' ) ),
				'browser_download_url' => esc_url_raw( $this->get_string_value( $asset['browser_download_url'] ) ),
			);
		}

		return $sanitized_assets;
	}

	/**
	 * Gets the version number from a sanitized release.
	 *
	 * @param array $release Release data.
	 * @return string
	 */
	private function get_release_version( $release ) {
		return $this->normalize_version( isset( $release['tag_name'] ) ? $release['tag_name'] : '' );
	}

	/**
	 * Normalizes a GitHub tag into a plugin version.
	 *
	 * @param string $tag_name Release tag.
	 * @return string
	 */
	private function normalize_version( $tag_name ) {
		$version = preg_replace( '/^v/i', '', sanitize_text_field( $this->get_string_value( $tag_name ) ) );

		if ( ! is_string( $version ) || 1 !== preg_match( '/^\d+(?:\.\d+){0,3}(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
			return '';
		}

		return $version;
	}

	/**
	 * Converts scalar API values to strings and discards unsupported shapes.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private function get_string_value( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return (string) $value;
	}

	/**
	 * Gets the best package URL from a release.
	 *
	 * @param array $release Release data.
	 * @return string
	 */
	private function get_package_url( $release ) {
		if ( ! empty( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( self::PACKAGE_ASSET_NAME === $asset['name'] ) {
					return $asset['browser_download_url'];
				}
			}

			foreach ( $release['assets'] as $asset ) {
				if ( '.zip' === substr( strtolower( $asset['name'] ), -4 ) ) {
					return $asset['browser_download_url'];
				}
			}
		}

		return $release['zipball_url'];
	}

	/**
	 * Builds GitHub HTTP request arguments.
	 *
	 * @param int    $timeout       Request timeout.
	 * @param string $stream_target Optional file path for streamed downloads.
	 * @param string $accept_header Optional Accept header.
	 * @return array
	 */
	private function get_request_args( $timeout, $stream_target = '', $accept_header = 'application/vnd.github+json' ) {
		$args = array(
			'timeout'     => absint( $timeout ),
			'redirection' => 3,
			'headers'     => array(
				'Accept'     => sanitize_text_field( $accept_header ),
				'User-Agent' => 'Interbo Site Defaults/' . INTERBO_SITE_DEFAULTS_VERSION,
			),
		);

		if ( '' !== $this->get_github_token() ) {
			$args['headers']['Authorization'] = 'Bearer ' . $this->get_github_token();
		}

		if ( '' !== $stream_target ) {
			$args['stream']   = true;
			$args['filename'] = $stream_target;
		}

		return $args;
	}

	/**
	 * Gets the optional GitHub token constant.
	 *
	 * @return string
	 */
	private function get_github_token() {
		if ( ! defined( 'INTERBO_SITE_DEFAULTS_GITHUB_TOKEN' ) || ! is_string( INTERBO_SITE_DEFAULTS_GITHUB_TOKEN ) ) {
			return '';
		}

		return trim( INTERBO_SITE_DEFAULTS_GITHUB_TOKEN );
	}

	/**
	 * Determines whether beta/pre-release releases should be considered.
	 *
	 * @return bool
	 */
	private function include_prereleases() {
		return 'beta' === INTERBO_SITE_DEFAULTS_RELEASE_CHANNEL;
	}

	/**
	 * Checks whether an upgrader hook is for this plugin.
	 *
	 * @param array $hook_extra Extra arguments passed by the upgrader.
	 * @return bool
	 */
	private function is_current_plugin_upgrade( $hook_extra ) {
		return isset( $hook_extra['plugin'] ) && plugin_basename( INTERBO_SITE_DEFAULTS_FILE ) === $hook_extra['plugin'];
	}

	/**
	 * Checks whether a URL belongs to GitHub package delivery.
	 *
	 * @param string $url Package URL.
	 * @return bool
	 */
	private function is_github_package_url( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );

		return in_array( $host, array( 'api.github.com', 'github.com', 'codeload.github.com' ), true );
	}

	/**
	 * Formats release notes for the plugin details modal.
	 *
	 * @param string $release_notes Release notes.
	 * @return string
	 */
	private function format_release_notes( $release_notes ) {
		return wp_kses_post( wpautop( esc_html( $release_notes ) ) );
	}
}
