<?php
/**
 * KPI Theme Functions
 *
 * Child theme for Twenty Twenty-Five
 */

/**
 * Enqueue parent theme stylesheet
 */
function kpi_theme_enqueue_styles() {
	wp_enqueue_style( 'twentytwentyfive-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'kpi-theme-style', get_stylesheet_uri(), array( 'twentytwentyfive-style' ) );
}

add_action( 'wp_enqueue_scripts', 'kpi_theme_enqueue_styles' );

/**
 * Set content width for the theme
 */
function kpi_theme_setup() {
	global $content_width;
	$content_width = 1200;
}

add_action( 'after_setup_theme', 'kpi_theme_setup' );
