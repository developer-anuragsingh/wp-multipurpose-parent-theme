<?php
/**
 * Theme setup concern.
 *
 * Centralises the theme's bootstrap behaviour (theme supports, textdomain,
 * asset enqueues) behind a single static entry point so functions.php stays a
 * thin loader. Resolved via the hand-rolled autoloader (Nexus\ -> inc/).
 *
 * @package NexusTheme
 */

namespace Nexus;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static bootstrap for the Nexus theme.
 *
 * Groups the WordPress hook registrations the theme needs at boot. Static by
 * design: there is a single theme instance per request, so no state needs to
 * be carried on an object.
 */
class Setup {

	/**
	 * Register the theme's WordPress hooks.
	 *
	 * Called once from functions.php after the autoloader is in place. Keeps
	 * all hook wiring in one place so the boot order is explicit and testable.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'after_setup_theme', array( self::class, 'setup' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
	}

	/**
	 * Declare theme support and load the translation files.
	 *
	 * Runs on after_setup_theme because theme supports and the textdomain must
	 * be registered before the rest of the request renders. The text domain is
	 * the literal 'nexus-theme' as mandated by the project rules.
	 *
	 * @return void
	 */
	public static function setup(): void {
		load_theme_textdomain( 'nexus-theme', NEXUS_THEME_DIR . '/languages' );
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
	}

	/**
	 * Enqueue the theme's front-end stylesheet.
	 *
	 * Runs on wp_enqueue_scripts so the main stylesheet is versioned against
	 * NEXUS_THEME_VERSION for cache busting on each release.
	 *
	 * @return void
	 */
	public static function enqueue_assets(): void {
		wp_enqueue_style( 'nexus-theme', get_stylesheet_uri(), array(), NEXUS_THEME_VERSION );
	}
}
