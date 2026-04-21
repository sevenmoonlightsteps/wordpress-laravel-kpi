<?php
/**
 * Plugin Name: KPI Dashboard
 * Description: Embeds a live KPI dashboard fed by a Laravel API.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'KPI_DASHBOARD_VERSION', '1.0.0' );
define( 'KPI_DASHBOARD_DIR', plugin_dir_path( __FILE__ ) );
define( 'KPI_DASHBOARD_URL', plugin_dir_url( __FILE__ ) );

require_once KPI_DASHBOARD_DIR . 'includes/settings.php';
require_once KPI_DASHBOARD_DIR . 'includes/rest-api.php';
require_once KPI_DASHBOARD_DIR . 'includes/shortcode.php';

/**
 * Register the Gutenberg block.
 */
function kpi_dashboard_register_block(): void {
	register_block_type( KPI_DASHBOARD_DIR . 'src/block' );
}
add_action( 'init', 'kpi_dashboard_register_block' );

/**
 * Enqueue block editor assets only in the block editor context.
 */
function kpi_dashboard_enqueue_editor_assets(): void {
	wp_enqueue_script(
		'kpi-dashboard-block-editor',
		KPI_DASHBOARD_URL . 'src/block/index.js',
		array( 'wp-blocks', 'wp-element' ),
		KPI_DASHBOARD_VERSION,
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'kpi_dashboard_enqueue_editor_assets' );

/**
 * Enqueue frontend assets for both block and shortcode contexts.
 * Loads Chart.js from CDN and the view script that initializes the dashboard.
 */
function kpi_dashboard_enqueue_frontend_assets(): void {
	if ( ! is_admin() ) {
		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js',
			array(),
			'4',
			true
		);
		wp_enqueue_script(
			'kpi-dashboard-view',
			KPI_DASHBOARD_URL . 'src/block/view.js',
			array( 'chartjs' ),
			KPI_DASHBOARD_VERSION,
			true
		);
		wp_enqueue_style(
			'kpi-dashboard-style',
			KPI_DASHBOARD_URL . 'src/block/dashboard.css',
			array(),
			KPI_DASHBOARD_VERSION
		);
	}
}
add_action( 'wp_enqueue_scripts', 'kpi_dashboard_enqueue_frontend_assets' );

/**
 * Add module type to the view script tag for ES module support.
 */
add_filter( 'script_loader_tag', function( $tag, $handle ) {
	if ( $handle === 'kpi-dashboard-view' ) {
		return str_replace( '<script ', '<script type="module" ', $tag );
	}
	return $tag;
}, 10, 2 );
