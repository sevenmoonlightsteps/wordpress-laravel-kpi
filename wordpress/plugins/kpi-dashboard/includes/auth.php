<?php
/**
 * Token exchange: obtain a Sanctum token from Laravel using a WP Application Password.
 *
 * Adds an "Authenticate" section to the KPI Dashboard settings page.
 * On form submission the plugin calls Laravel server-side, so the WP
 * Application Password never reaches the browser.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle the token-exchange form and return a notice string (or empty string).
 *
 * Returns an array: ['type' => 'success'|'error', 'message' => string] or [].
 */
function kpi_dashboard_handle_auth_form(): array {
	if ( empty( $_POST['kpi_dashboard_auth_nonce'] ) ) {
		return [];
	}

	check_admin_referer( 'kpi_dashboard_exchange_token', 'kpi_dashboard_auth_nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		return [ 'type' => 'error', 'message' => __( 'Insufficient permissions.', 'kpi-dashboard' ) ];
	}

	$wp_username     = sanitize_text_field( wp_unslash( (string) ( $_POST['kpi_auth_wp_username'] ?? '' ) ) );
	$wp_app_password = sanitize_text_field( wp_unslash( (string) ( $_POST['kpi_auth_wp_app_password'] ?? '' ) ) );

	if ( empty( $wp_username ) || empty( $wp_app_password ) ) {
		return [ 'type' => 'error', 'message' => __( 'Username and Application Password are required.', 'kpi-dashboard' ) ];
	}

	$api_url  = get_option( 'kpi_dashboard_api_url', 'http://localhost:8081' );
	$site_url = home_url();

	$response = wp_remote_post(
		trailingslashit( (string) $api_url ) . 'api/auth/wp-token',
		[
			'headers' => [ 'Content-Type' => 'application/json', 'Accept' => 'application/json' ],
			'body'    => wp_json_encode(
				[
					'wp_site_url'     => $site_url,
					'wp_username'     => $wp_username,
					'wp_app_password' => $wp_app_password,
				]
			),
			'timeout' => 15,
		]
	);

	if ( is_wp_error( $response ) ) {
		return [
			'type'    => 'error',
			'message' => sprintf(
				/* translators: %s: error message from WP HTTP API */
				__( 'Could not reach Laravel: %s', 'kpi-dashboard' ),
				$response->get_error_message()
			),
		];
	}

	$http_code = wp_remote_retrieve_response_code( $response );
	$body      = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( $http_code === 401 ) {
		return [ 'type' => 'error', 'message' => __( 'WordPress credentials rejected by Laravel. Check your username and Application Password.', 'kpi-dashboard' ) ];
	}

	if ( $http_code !== 201 || empty( $body['data']['token'] ) ) {
		return [
			'type'    => 'error',
			'message' => sprintf(
				/* translators: %d: HTTP status code returned by Laravel */
				__( 'Token exchange failed (HTTP %d). Check Laravel logs.', 'kpi-dashboard' ),
				$http_code
			),
		];
	}

	update_option( 'kpi_dashboard_api_token', sanitize_text_field( $body['data']['token'] ) );

	return [ 'type' => 'success', 'message' => __( 'Sanctum token obtained and saved successfully.', 'kpi-dashboard' ) ];
}

/**
 * Render the "Authenticate" section inside the settings page.
 * Call this from within the settings page render function.
 *
 * @param array $notice Result from kpi_dashboard_handle_auth_form().
 */
function kpi_dashboard_render_auth_section( array $notice ): void {
	if ( ! empty( $notice ) ) {
		$class = $notice['type'] === 'success' ? 'notice-success' : 'notice-error';
		printf(
			'<div class="notice %s is-dismissible"><p>%s</p></div>',
			esc_attr( $class ),
			esc_html( $notice['message'] )
		);
	}
	?>
	<hr />
	<h2><?php esc_html_e( 'Authenticate via WordPress Application Password', 'kpi-dashboard' ); ?></h2>
	<p><?php esc_html_e( 'Enter your WordPress credentials below. The plugin will call Laravel, verify your identity using the WordPress REST API, and automatically save the Sanctum token. Your Application Password is never stored.', 'kpi-dashboard' ); ?></p>
	<p>
		<?php
		printf(
			'<a href="%s" target="_blank">%s</a>',
			esc_url( admin_url( 'profile.php#application-passwords-section' ) ),
			esc_html__( 'Generate an Application Password in your profile', 'kpi-dashboard' )
		);
		?>
	</p>
	<form method="post" action="">
		<?php wp_nonce_field( 'kpi_dashboard_exchange_token', 'kpi_dashboard_auth_nonce' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="kpi_auth_wp_username"><?php esc_html_e( 'WordPress Username', 'kpi-dashboard' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						id="kpi_auth_wp_username"
						name="kpi_auth_wp_username"
						class="regular-text"
						autocomplete="username"
						value="<?php echo esc_attr( wp_get_current_user()->user_login ); ?>"
					/>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="kpi_auth_wp_app_password"><?php esc_html_e( 'Application Password', 'kpi-dashboard' ); ?></label>
				</th>
				<td>
					<input
						type="password"
						id="kpi_auth_wp_app_password"
						name="kpi_auth_wp_app_password"
						class="regular-text"
						autocomplete="new-password"
					/>
					<p class="description">
						<?php esc_html_e( 'The xxxx xxxx xxxx format generated under your profile.', 'kpi-dashboard' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Get Token from Laravel', 'kpi-dashboard' ), 'secondary' ); ?>
	</form>
	<?php
}
