<?php
/**
 * Admin settings page for KPI Dashboard plugin.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register plugin settings.
 */
function kpi_dashboard_register_settings(): void {
	register_setting(
		'kpi_dashboard_options',
		'kpi_dashboard_api_url',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'esc_url_raw',
			'default'           => 'http://localhost:8081',
		)
	);

	register_setting(
		'kpi_dashboard_options',
		'kpi_dashboard_api_token',
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		)
	);

	add_settings_section(
		'kpi_dashboard_main_section',
		__( 'Laravel API Connection', 'kpi-dashboard' ),
		'kpi_dashboard_section_callback',
		'kpi-dashboard-settings'
	);

	add_settings_field(
		'kpi_dashboard_api_url',
		__( 'Laravel API Base URL', 'kpi-dashboard' ),
		'kpi_dashboard_api_url_field_callback',
		'kpi-dashboard-settings',
		'kpi_dashboard_main_section'
	);

	add_settings_field(
		'kpi_dashboard_api_token',
		__( 'Sanctum Bearer Token', 'kpi-dashboard' ),
		'kpi_dashboard_api_token_field_callback',
		'kpi-dashboard-settings',
		'kpi_dashboard_main_section'
	);
}
add_action( 'admin_init', 'kpi_dashboard_register_settings' );

/**
 * Add settings page under the Settings menu.
 */
function kpi_dashboard_add_admin_menu(): void {
	add_options_page(
		__( 'KPI Dashboard Settings', 'kpi-dashboard' ),
		__( 'KPI Dashboard', 'kpi-dashboard' ),
		'manage_options',
		'kpi-dashboard-settings',
		'kpi_dashboard_settings_page_render'
	);
}
add_action( 'admin_menu', 'kpi_dashboard_add_admin_menu' );

/**
 * Section description callback.
 */
function kpi_dashboard_section_callback(): void {
	echo '<p>' . esc_html__( 'Configure the connection to your Laravel KPI API.', 'kpi-dashboard' ) . '</p>';
}

/**
 * API URL field callback.
 */
function kpi_dashboard_api_url_field_callback(): void {
	$value = get_option( 'kpi_dashboard_api_url', 'http://localhost:8081' );
	printf(
		'<input type="url" id="kpi_dashboard_api_url" name="kpi_dashboard_api_url" value="%s" class="regular-text" placeholder="http://localhost:8081" />',
		esc_attr( (string) $value )
	);
	echo '<p class="description">' . esc_html__( 'Base URL of the Laravel API (no trailing slash).', 'kpi-dashboard' ) . '</p>';
}

/**
 * API token field callback.
 * The actual token value is never printed — only a blank password field is shown.
 * The token is updated only when a non-empty value is submitted.
 */
function kpi_dashboard_api_token_field_callback(): void {
	$has_token = ! empty( get_option( 'kpi_dashboard_api_token', '' ) );
	echo '<input type="password" id="kpi_dashboard_api_token" name="kpi_dashboard_api_token" value="" class="regular-text" autocomplete="new-password" />';
	if ( $has_token ) {
		echo '<p class="description">' . esc_html__( 'A token is already saved. Enter a new value to replace it, or leave blank to keep the current token.', 'kpi-dashboard' ) . '</p>';
	} else {
		echo '<p class="description">' . esc_html__( 'Sanctum Bearer token used to authenticate with the Laravel API.', 'kpi-dashboard' ) . '</p>';
	}
}

/**
 * Render the settings page.
 */
function kpi_dashboard_settings_page_render(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle token-exchange form (from auth.php) before rendering.
	$auth_notice = kpi_dashboard_handle_auth_form();

	// Handle settings form submission with nonce verification.
	if ( isset( $_POST['kpi_dashboard_settings_nonce'] ) ) {
		check_admin_referer( 'kpi_dashboard_save_settings', 'kpi_dashboard_settings_nonce' );

		// Update API URL.
		if ( isset( $_POST['kpi_dashboard_api_url'] ) ) {
			update_option( 'kpi_dashboard_api_url', esc_url_raw( sanitize_text_field( wp_unslash( (string) $_POST['kpi_dashboard_api_url'] ) ) ) );
		}

		// Only update the token if a non-empty value was submitted.
		if ( ! empty( $_POST['kpi_dashboard_api_token'] ) ) {
			update_option( 'kpi_dashboard_api_token', sanitize_text_field( wp_unslash( (string) $_POST['kpi_dashboard_api_token'] ) ) );
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'kpi-dashboard' ) . '</p></div>';
	}

	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form method="post" action="">
			<?php
			wp_nonce_field( 'kpi_dashboard_save_settings', 'kpi_dashboard_settings_nonce' );
			settings_fields( 'kpi_dashboard_options' );
			do_settings_sections( 'kpi-dashboard-settings' );
			submit_button( __( 'Save Settings', 'kpi-dashboard' ) );
			?>
		</form>
		<?php kpi_dashboard_render_auth_section( $auth_notice ); ?>
	</div>
	<?php
}
