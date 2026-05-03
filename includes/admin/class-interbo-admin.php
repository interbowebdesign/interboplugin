<?php
/**
 * Admin functionality.
 *
 * @package InterboSiteDefaults
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WordPress admin screens for the plugin.
 */
class Interbo_Admin {

	/**
	 * Required capability for all plugin admin pages.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Main menu slug.
	 *
	 * @var string
	 */
	const MENU_SLUG = 'interbo-site-defaults';

	/**
	 * Nonce action prepared for future admin forms.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'interbo_site_defaults_admin_action';

	/**
	 * Nonce field name prepared for future admin forms.
	 *
	 * @var string
	 */
	const NONCE_NAME = 'interbo_site_defaults_nonce';

	/**
	 * Registers admin hooks.
	 *
	 * @return void
	 */
	public function add_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( INTERBO_SITE_DEFAULTS_FILE ), array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 * Runs admin initialization.
	 *
	 * @return void
	 */
	public function admin_init() {
		// Reserved for future settings registration. v0.3 has no form handling.
	}

	/**
	 * Registers the Interbo admin menu and dashboard submenu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Interbo Dashboard', 'interbo-site-defaults' ),
			__( 'Interbo', 'interbo-site-defaults' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_dashboard_page' ),
			'dashicons-admin-generic',
			58
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Interbo Dashboard', 'interbo-site-defaults' ),
			__( 'Dashboard', 'interbo-site-defaults' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_dashboard_page' )
		);
	}

	/**
	 * Adds a dashboard shortcut to the plugin list table.
	 *
	 * @param array $links Existing plugin action links.
	 * @return array
	 */
	public function add_plugin_action_links( $links ) {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return $links;
		}

		$dashboard_url  = add_query_arg(
			array(
				'page' => self::MENU_SLUG,
			),
			admin_url( 'admin.php' )
		);
		$dashboard_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $dashboard_url ),
			esc_html__( 'Dashboard', 'interbo-site-defaults' )
		);

		array_unshift( $links, $dashboard_link );

		return $links;
	}

	/**
	 * Sanitizes text input for future admin forms.
	 *
	 * @param string|array $value Raw input value.
	 * @return string|array
	 */
	public function sanitize_text_input( $value ) {
		if ( is_array( $value ) ) {
			return map_deep( wp_unslash( $value ), 'sanitize_text_field' );
		}

		return sanitize_text_field( wp_unslash( $value ) );
	}

	/**
	 * Gets read-only site information for the dashboard widget.
	 *
	 * @return array
	 */
	private function get_site_information() {
		$theme         = wp_get_theme();
		$theme_name    = $theme->get( 'Name' );
		$theme_version = $theme->get( 'Version' );
		$theme_label   = $theme_name;
		$plugin_count  = $this->get_active_plugins_count();

		if ( '' !== $theme_version ) {
			$theme_label = sprintf(
				/* translators: 1: theme name, 2: theme version. */
				__( '%1$s versie %2$s', 'interbo-site-defaults' ),
				$theme_name,
				$theme_version
			);
		}

		return array(
			array(
				'label' => __( 'Site URL', 'interbo-site-defaults' ),
				'value' => home_url( '/' ),
			),
			array(
				'label' => __( 'WordPress-versie', 'interbo-site-defaults' ),
				'value' => get_bloginfo( 'version' ),
			),
			array(
				'label' => __( 'PHP-versie', 'interbo-site-defaults' ),
				'value' => PHP_VERSION,
			),
			array(
				'label' => __( 'Database', 'interbo-site-defaults' ),
				'value' => $this->get_database_server_info(),
			),
			array(
				'label' => __( 'Actief thema', 'interbo-site-defaults' ),
				'value' => $theme_label,
			),
			array(
				'label' => __( 'Actieve plugins', 'interbo-site-defaults' ),
				'value' => sprintf(
					/* translators: %s: active plugin count. */
					_n( '%s plugin', '%s plugins', $plugin_count, 'interbo-site-defaults' ),
					number_format_i18n( $plugin_count )
				),
			),
			array(
				'label' => __( 'Tijdzone', 'interbo-site-defaults' ),
				'value' => wp_timezone_string(),
			),
			array(
				'label' => __( 'Omgeving', 'interbo-site-defaults' ),
				'value' => wp_get_environment_type(),
			),
		);
	}

	/**
	 * Gets WordPress recommended specs compared with the current site.
	 *
	 * @return array
	 */
	private function get_wordpress_specification_comparison( $requirements ) {
		$database_info   = $this->get_database_server_info();
		$server_software = $this->get_server_software();

		return array(
			array(
				'label'    => __( 'PHP', 'interbo-site-defaults' ),
				'required' => sprintf(
					/* translators: %s: recommended PHP version. */
					__( 'PHP %s of hoger', 'interbo-site-defaults' ),
					$requirements['php']
				),
				'actual'   => PHP_VERSION,
				'status'   => $this->get_version_status( PHP_VERSION, $requirements['php'] ),
			),
			array(
				'label'    => __( 'Database', 'interbo-site-defaults' ),
				'required' => sprintf(
					/* translators: 1: recommended MariaDB version, 2: recommended MySQL version. */
					__( 'MariaDB %1$s+ of MySQL %2$s+', 'interbo-site-defaults' ),
					$requirements['mariadb'],
					$requirements['mysql']
				),
				'actual'   => $database_info,
				'status'   => $this->get_database_status( $database_info, $requirements ),
			),
			array(
				'label'    => __( 'HTTPS', 'interbo-site-defaults' ),
				'required' => $requirements['https'],
				'actual'   => $this->site_uses_https() ? __( 'Actief', 'interbo-site-defaults' ) : __( 'Niet actief', 'interbo-site-defaults' ),
				'status'   => $this->site_uses_https() ? 'ok' : 'attention',
			),
			array(
				'label'    => __( 'Webserver', 'interbo-site-defaults' ),
				'required' => $requirements['webserver'],
				'actual'   => $server_software,
				'status'   => $this->get_webserver_status( $server_software ),
			),
		);
	}

	/**
	 * Gets WordPress requirements from cache, WordPress.org, or local fallback values.
	 *
	 * @return array
	 */
	private function get_wordpress_requirements() {
		$cached_requirements = get_site_transient( INTERBO_SITE_DEFAULTS_WP_REQUIREMENTS_CACHE_KEY );

		if ( $this->is_valid_requirements_data( $cached_requirements ) ) {
			return $cached_requirements;
		}

		$requirements = $this->fetch_wordpress_requirements();
		$cache_ttl    = WEEK_IN_SECONDS;

		if ( ! $this->is_valid_requirements_data( $requirements ) ) {
			$requirements = $this->get_fallback_wordpress_requirements();
			$cache_ttl    = DAY_IN_SECONDS;
		}

		set_site_transient( INTERBO_SITE_DEFAULTS_WP_REQUIREMENTS_CACHE_KEY, $requirements, $cache_ttl );

		return $requirements;
	}

	/**
	 * Fetches and parses the official WordPress requirements page.
	 *
	 * @return array|false
	 */
	private function fetch_wordpress_requirements() {
		$response = wp_remote_get(
			INTERBO_SITE_DEFAULTS_WP_REQUIREMENTS_URL,
			array(
				'timeout'     => 8,
				'redirection' => 3,
				'headers'     => array(
					'User-Agent' => 'Interbo Site Defaults/' . INTERBO_SITE_DEFAULTS_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$page_content = wp_remote_retrieve_body( $response );

		if ( ! is_string( $page_content ) || '' === $page_content ) {
			return false;
		}

		$requirements = $this->get_fallback_wordpress_requirements();
		$text_content = html_entity_decode( wp_strip_all_tags( $page_content ), ENT_QUOTES, get_bloginfo( 'charset' ) );
		$php_version  = $this->extract_requirement_version( '/PHP(?:\s+version)?\s+([0-9]+(?:\.[0-9]+)+)\s+or\s+greater/i', $text_content );
		$mariadb      = $this->extract_requirement_version( '/MariaDB(?:\s+version)?\s+([0-9]+(?:\.[0-9]+)+)\s+or\s+greater/i', $text_content );
		$mysql        = $this->extract_requirement_version( '/MySQL(?:\s+version)?\s+([0-9]+(?:\.[0-9]+)+)\s+or\s+greater/i', $text_content );

		if ( '' !== $php_version ) {
			$requirements['php'] = $php_version;
		}

		if ( '' !== $mariadb ) {
			$requirements['mariadb'] = $mariadb;
		}

		if ( '' !== $mysql ) {
			$requirements['mysql'] = $mysql;
		}

		$requirements['https']        = false !== stripos( $text_content, 'HTTPS support' ) ? __( 'HTTPS ondersteuning', 'interbo-site-defaults' ) : $requirements['https'];
		$requirements['webserver']    = $this->get_webserver_requirement_label( $text_content );
		$requirements['source']       = 'remote';
		$requirements['retrieved_at'] = time();

		return $requirements;
	}

	/**
	 * Gets local fallback WordPress requirements.
	 *
	 * @return array
	 */
	private function get_fallback_wordpress_requirements() {
		return array(
			'php'          => INTERBO_SITE_DEFAULTS_WP_RECOMMENDED_PHP,
			'mysql'        => INTERBO_SITE_DEFAULTS_WP_RECOMMENDED_MYSQL,
			'mariadb'      => INTERBO_SITE_DEFAULTS_WP_RECOMMENDED_MARIADB,
			'https'        => __( 'HTTPS ondersteuning', 'interbo-site-defaults' ),
			'webserver'    => __( 'Apache of Nginx aanbevolen', 'interbo-site-defaults' ),
			'source'       => 'fallback',
			'retrieved_at' => time(),
		);
	}

	/**
	 * Validates requirements data shape.
	 *
	 * @param mixed $requirements Requirements data.
	 * @return bool
	 */
	private function is_valid_requirements_data( $requirements ) {
		if ( ! is_array( $requirements ) ) {
			return false;
		}

		foreach ( array( 'php', 'mysql', 'mariadb', 'https', 'webserver', 'source', 'retrieved_at' ) as $key ) {
			if ( ! isset( $requirements[ $key ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Extracts a version number from requirements page text.
	 *
	 * @param string $pattern Regex pattern.
	 * @param string $content Page text content.
	 * @return string
	 */
	private function extract_requirement_version( $pattern, $content ) {
		if ( 1 !== preg_match( $pattern, $content, $matches ) || empty( $matches[1] ) ) {
			return '';
		}

		return sanitize_text_field( $matches[1] );
	}

	/**
	 * Gets the recommended webserver label from requirements text.
	 *
	 * @param string $content Page text content.
	 * @return string
	 */
	private function get_webserver_requirement_label( $content ) {
		if ( false === stripos( $content, 'Apache' ) || false === stripos( $content, 'Nginx' ) ) {
			return __( 'Apache of Nginx aanbevolen', 'interbo-site-defaults' );
		}

		if ( false !== stripos( $content, 'mod_rewrite' ) ) {
			return __( 'Nginx of Apache met mod_rewrite aanbevolen', 'interbo-site-defaults' );
		}

		return __( 'Apache of Nginx aanbevolen', 'interbo-site-defaults' );
	}

	/**
	 * Gets the current database server information.
	 *
	 * @return string
	 */
	private function get_database_server_info() {
		global $wpdb;

		if ( ! is_object( $wpdb ) || ! method_exists( $wpdb, 'db_server_info' ) ) {
			return __( 'Onbekend', 'interbo-site-defaults' );
		}

		$database_info = $wpdb->db_server_info();

		if ( ! is_string( $database_info ) || '' === $database_info ) {
			return __( 'Onbekend', 'interbo-site-defaults' );
		}

		return sanitize_text_field( $database_info );
	}

	/**
	 * Gets the current server software.
	 *
	 * @return string
	 */
	private function get_server_software() {
		$server_software = filter_input( INPUT_SERVER, 'SERVER_SOFTWARE', FILTER_UNSAFE_RAW );

		if ( ! is_string( $server_software ) || '' === $server_software ) {
			return __( 'Onbekend', 'interbo-site-defaults' );
		}

		return sanitize_text_field( wp_unslash( $server_software ) );
	}

	/**
	 * Gets the active plugin count.
	 *
	 * @return int
	 */
	private function get_active_plugins_count() {
		$active_plugins = get_option( 'active_plugins', array() );
		$count          = is_array( $active_plugins ) ? count( $active_plugins ) : 0;

		if ( is_multisite() ) {
			$network_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$count          += is_array( $network_plugins ) ? count( $network_plugins ) : 0;
		}

		return $count;
	}

	/**
	 * Checks whether a version meets a recommended minimum.
	 *
	 * @param string $actual_version Actual version.
	 * @param string $minimum_version Minimum recommended version.
	 * @return string
	 */
	private function get_version_status( $actual_version, $minimum_version ) {
		if ( '' === $actual_version ) {
			return 'unknown';
		}

		return version_compare( $actual_version, $minimum_version, '>=' ) ? 'ok' : 'attention';
	}

	/**
	 * Checks the database version against WordPress recommendations.
	 *
	 * @param string $database_info Database server info.
	 * @return string
	 */
	private function get_database_status( $database_info, $requirements ) {
		$database_version = $this->get_database_version( $database_info );

		if ( '' === $database_version ) {
			return 'unknown';
		}

		$minimum_version = false !== stripos( $database_info, 'mariadb' ) ? $requirements['mariadb'] : $requirements['mysql'];

		return version_compare( $database_version, $minimum_version, '>=' ) ? 'ok' : 'attention';
	}

	/**
	 * Extracts the database version from a database server string.
	 *
	 * @param string $database_info Database server info.
	 * @return string
	 */
	private function get_database_version( $database_info ) {
		if ( false === preg_match_all( '/\d+(?:\.\d+)+/', $database_info, $matches ) || empty( $matches[0] ) ) {
			return '';
		}

		return (string) end( $matches[0] );
	}

	/**
	 * Checks whether the site uses HTTPS.
	 *
	 * @return bool
	 */
	private function site_uses_https() {
		return is_ssl() || 'https' === wp_parse_url( home_url( '/' ), PHP_URL_SCHEME );
	}

	/**
	 * Checks whether the detected webserver matches the recommended types.
	 *
	 * @param string $server_software Server software.
	 * @return string
	 */
	private function get_webserver_status( $server_software ) {
		if ( __( 'Onbekend', 'interbo-site-defaults' ) === $server_software ) {
			return 'unknown';
		}

		return preg_match( '/apache|nginx/i', $server_software ) ? 'ok' : 'attention';
	}

	/**
	 * Gets a human-readable status label.
	 *
	 * @param string $status Status code.
	 * @return string
	 */
	private function get_status_label( $status ) {
		if ( 'ok' === $status ) {
			return __( 'Voldoet', 'interbo-site-defaults' );
		}

		if ( 'attention' === $status ) {
			return __( 'Aandacht', 'interbo-site-defaults' );
		}

		return __( 'Onbekend', 'interbo-site-defaults' );
	}

	/**
	 * Gets a human-readable requirements source label.
	 *
	 * @param array $requirements Requirements data.
	 * @return string
	 */
	private function get_requirements_source_label( $requirements ) {
		if ( isset( $requirements['source'] ) && 'remote' === $requirements['source'] ) {
			return __( 'Periodiek opgehaald bij WordPress.org', 'interbo-site-defaults' );
		}

		return __( 'Fallbackwaarden uit de plugin', 'interbo-site-defaults' );
	}

	/**
	 * Renders the dashboard page.
	 *
	 * @return void
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Je hebt geen rechten om deze pagina te bekijken.', 'interbo-site-defaults' ) );
		}

		$page_class      = 'wrap interbo-site-defaults';
		$plugin_name     = __( 'Interbo Site Defaults', 'interbo-site-defaults' );
		$version         = INTERBO_SITE_DEFAULTS_VERSION;
		$release_status  = INTERBO_SITE_DEFAULTS_RELEASE_STATUS;
		$release_channel = INTERBO_SITE_DEFAULTS_RELEASE_CHANNEL;
		$update_source   = INTERBO_SITE_DEFAULTS_UPDATE_URI;
		$api_endpoint    = INTERBO_SITE_DEFAULTS_GITHUB_RELEASES_API;
		$token_status    = is_string( INTERBO_SITE_DEFAULTS_GITHUB_TOKEN ) && '' !== trim( INTERBO_SITE_DEFAULTS_GITHUB_TOKEN ) ? __( 'Geconfigureerd via constante', 'interbo-site-defaults' ) : __( 'Niet geconfigureerd', 'interbo-site-defaults' );
		$autoupdate      = INTERBO_SITE_DEFAULTS_AUTOUPDATE ? __( 'Ingeschakeld', 'interbo-site-defaults' ) : __( 'Uitgeschakeld', 'interbo-site-defaults' );
		$site_info       = $this->get_site_information();
		$wp_requirements = $this->get_wordpress_requirements();
		$wp_specs        = $this->get_wordpress_specification_comparison( $wp_requirements );
		$summary         = __( 'Versie 0.3 voegt een read-only site info widget toe voor snelle support- en onderhoudschecks.', 'interbo-site-defaults' );
		$description     = __( 'Interbo Site Defaults is de beheerde basis voor gedeelde Interbo Webdesign site defaults. Versie 0.3 toont sitegegevens en vergelijkt belangrijke serverwaarden met periodiek opgehaalde aanbevelingen uit de WordPress requirements.', 'interbo-site-defaults' );
		$notes           = array(
			__( 'Dashboard is alleen beschikbaar voor gebruikers met manage_options.', 'interbo-site-defaults' ),
			__( 'Site info is read-only en slaat geen waarden op.', 'interbo-site-defaults' ),
			__( 'Updatecontrole gebruikt de WordPress Update URI-header en de GitHub Releases API.', 'interbo-site-defaults' ),
			__( 'Automatische achtergrondupdates kunnen via INTERBO_SITE_DEFAULTS_AUTOUPDATE worden uitgeschakeld.', 'interbo-site-defaults' ),
			__( 'Releasegegevens worden tijdelijk gecachet in een site transient.', 'interbo-site-defaults' ),
			__( 'Deze versie bewaart geen opties, custom tabellen of gebruikersmeta.', 'interbo-site-defaults' ),
		);
		?>
		<div class="<?php echo esc_attr( $page_class ); ?>">
			<h1><?php echo esc_html( $plugin_name ); ?></h1>

			<div class="notice notice-info inline">
				<p><?php echo esc_html( $summary ); ?></p>
			</div>

			<h2><?php esc_html_e( 'Status', 'interbo-site-defaults' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Pluginnaam', 'interbo-site-defaults' ); ?></th>
						<td><?php echo esc_html( $plugin_name ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Versie', 'interbo-site-defaults' ); ?></th>
						<td><code><?php echo esc_html( $version ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Release status', 'interbo-site-defaults' ); ?></th>
						<td><?php echo esc_html( $release_status ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Releasekanaal', 'interbo-site-defaults' ); ?></th>
						<td><?php echo esc_html( $release_channel ); ?></td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Beschrijving', 'interbo-site-defaults' ); ?></h2>
			<div class="interbo-site-defaults__description">
				<?php echo wp_kses_post( wpautop( $description ) ); ?>
			</div>

			<h2><?php esc_html_e( 'Site info widget', 'interbo-site-defaults' ); ?></h2>
			<table class="widefat striped" role="presentation">
				<tbody>
					<?php foreach ( $site_info as $item ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $item['label'] ); ?></th>
							<td><?php echo esc_html( $item['value'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'WordPress specs', 'interbo-site-defaults' ); ?></h2>
			<table class="widefat striped" role="presentation">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Onderdeel', 'interbo-site-defaults' ); ?></th>
						<th scope="col"><?php esc_html_e( 'WordPress docs', 'interbo-site-defaults' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Deze site', 'interbo-site-defaults' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Status', 'interbo-site-defaults' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $wp_specs as $spec ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $spec['label'] ); ?></th>
							<td><?php echo esc_html( $spec['required'] ); ?></td>
							<td><?php echo esc_html( $spec['actual'] ); ?></td>
							<td><strong><?php echo esc_html( $this->get_status_label( $spec['status'] ) ); ?></strong></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="description">
				<?php esc_html_e( 'Bron:', 'interbo-site-defaults' ); ?>
				<a href="<?php echo esc_url( INTERBO_SITE_DEFAULTS_WP_REQUIREMENTS_URL ); ?>"><?php esc_html_e( 'WordPress requirements', 'interbo-site-defaults' ); ?></a>.
				<?php echo esc_html( $this->get_requirements_source_label( $wp_requirements ) ); ?>
				<?php
				printf(
					/* translators: %s: date and time. */
					esc_html__( 'Laatst bijgewerkt: %s.', 'interbo-site-defaults' ),
					esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), absint( $wp_requirements['retrieved_at'] ) ) )
				);
				?>
			</p>

			<h2><?php esc_html_e( 'Updatebron', 'interbo-site-defaults' ); ?></h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Update URI', 'interbo-site-defaults' ); ?></th>
						<td><a href="<?php echo esc_url( $update_source ); ?>"><?php echo esc_html( $update_source ); ?></a></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'GitHub API', 'interbo-site-defaults' ); ?></th>
						<td><code><?php echo esc_html( $api_endpoint ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'GitHub token', 'interbo-site-defaults' ); ?></th>
						<td><?php echo esc_html( $token_status ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Automatische updates', 'interbo-site-defaults' ); ?></th>
						<td><?php echo esc_html( $autoupdate ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Pakketbron', 'interbo-site-defaults' ); ?></th>
						<td><?php esc_html_e( 'Release asset interbo-site-defaults.zip, met fallback naar GitHub zipball.', 'interbo-site-defaults' ); ?></td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Ontwikkelnotities', 'interbo-site-defaults' ); ?></h2>
			<ul class="ul-disc">
				<?php foreach ( $notes as $note ) : ?>
					<li><?php echo esc_html( $note ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}
