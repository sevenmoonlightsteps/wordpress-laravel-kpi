<?php
/**
 * WP REST proxy endpoint for KPI Dashboard.
 *
 * Proxies GET /api/kpi/summary from Laravel so the Bearer token
 * is never exposed to the browser.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the REST route.
 */
function kpi_dashboard_register_rest_routes(): void {
	register_rest_route(
		'kpi-dashboard/v1',
		'/summary',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'kpi_dashboard_rest_summary_callback',
			'permission_callback' => 'kpi_dashboard_rest_permission_check',
		)
	);
}
add_action( 'rest_api_init', 'kpi_dashboard_register_rest_routes' );

/**
 * Permission callback: only logged-in WP users may call this endpoint.
 */
function kpi_dashboard_rest_permission_check(): bool {
	return is_user_logged_in();
}

/**
 * Proxy callback: fetch KPI summary from Laravel and return the data.
 *
 * @return WP_REST_Response|WP_Error
 */
function kpi_dashboard_rest_summary_callback(): WP_REST_Response|WP_Error {
	$api_url   = get_option( 'kpi_dashboard_api_url', 'http://localhost:8081' );
	$api_token = get_option( 'kpi_dashboard_api_token', '' );

	if ( empty( $api_token ) ) {
		return new WP_Error(
			'kpi_dashboard_no_token',
			__( 'KPI Dashboard API token is not configured.', 'kpi-dashboard' ),
			array( 'status' => 500 )
		);
	}

	$laravel_url = trailingslashit( (string) $api_url ) . 'api/kpi/summary';

	$response = wp_remote_get(
		$laravel_url,
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_token,
				'Accept'        => 'application/json',
			),
			'timeout' => 15,
		)
	);

	if ( is_wp_error( $response ) ) {
		return new WP_Error(
			'kpi_dashboard_upstream_error',
			$response->get_error_message(),
			array( 'status' => 502 )
		);
	}

	$http_code = wp_remote_retrieve_response_code( $response );
	$body      = wp_remote_retrieve_body( $response );

	if ( $http_code >= 400 ) {
		return new WP_Error(
			'kpi_dashboard_upstream_http_error',
			sprintf(
				/* translators: %d: HTTP status code from upstream Laravel API */
				__( 'Laravel API returned HTTP %d.', 'kpi-dashboard' ),
				$http_code
			),
			array( 'status' => 502 )
		);
	}

	$data = json_decode( $body, true );

	if ( json_last_error() !== JSON_ERROR_NONE ) {
		return new WP_Error(
			'kpi_dashboard_invalid_json',
			__( 'Laravel API returned invalid JSON.', 'kpi-dashboard' ),
			array( 'status' => 502 )
		);
	}

	return new WP_REST_Response( $data, 200 );
}
