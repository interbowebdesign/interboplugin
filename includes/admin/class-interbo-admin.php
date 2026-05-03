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
		// Reserved for future settings registration. v0.2 has no form handling.
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
		$summary         = __( 'Versie 0.2 voegt centrale updatecontrole en automatische achtergrondupdates via GitHub Releases toe. Er worden nog geen plugininstellingen opgeslagen.', 'interbo-site-defaults' );
		$description     = __( 'Interbo Site Defaults is de beheerde basis voor gedeelde Interbo Webdesign site defaults. Versie 0.2 controleert via de WordPress update-API of er een nieuwere centrale GitHub-release beschikbaar is en kan die automatisch installeren.', 'interbo-site-defaults' );
		$notes           = array(
			__( 'Dashboard is alleen beschikbaar voor gebruikers met manage_options.', 'interbo-site-defaults' ),
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
