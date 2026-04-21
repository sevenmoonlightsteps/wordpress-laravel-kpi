<?php
/**
 * Shortcode fallback for KPI Dashboard.
 *
 * Usage: [kpi_dashboard]
 *
 * Outputs a container div with a WP REST nonce as a data attribute.
 * The block JS (Phase 4) mounts into this div.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the [kpi_dashboard] shortcode.
 *
 * @param array<string, mixed> $atts Shortcode attributes (unused — reserved for Phase 4).
 * @return string HTML output.
 */
function kpi_dashboard_shortcode_render( array $atts = array() ): string {
	// Only render for logged-in users; the REST endpoint requires authentication.
	if ( ! is_user_logged_in() ) {
		return '<p class="kpi-dashboard-notice">' . esc_html__( 'You must be logged in to view the KPI Dashboard.', 'kpi-dashboard' ) . '</p>';
	}

	$nonce = wp_create_nonce( 'wp_rest' );

	return sprintf(
		'<div id="kpi-dashboard-root" data-nonce="%s"></div>',
		esc_attr( $nonce )
	);
}
add_shortcode( 'kpi_dashboard', 'kpi_dashboard_shortcode_render' );
