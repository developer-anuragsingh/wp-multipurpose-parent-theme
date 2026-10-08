<?php
/**
 * Theme Bootstrap File
 *
 * @package NexusTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NEXUS_THEME_VERSION', '1.0.0' );
define( 'NEXUS_THEME_DIR', get_template_directory() );
define( 'NEXUS_THEME_URI', get_template_directory_uri() );

add_action( 'after_setup_theme', function() {
	load_theme_textdomain( 'nexus-theme', NEXUS_THEME_DIR . '/languages' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
} );
