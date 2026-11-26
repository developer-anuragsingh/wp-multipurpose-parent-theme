<?php
/**
 * Bootstrap for the integration suite: loads the REAL WordPress test library
 * and activates this theme inside it.
 *
 * The theme lives in ./theme, but WordPress resolves themes by folder name and
 * this theme's slug (and text domain) is "atlas-theme". A temporary themes root
 * with an "atlas-theme" symlink pointing at ./theme makes the real slug resolve
 * without copying files. Activation is done through option filters, so nothing
 * is written to the database during bootstrap.
 *
 * @package AtlasTheme
 */

$atlas_root = dirname( __DIR__, 2 );

require_once $atlas_root . '/vendor/autoload.php';

putenv( 'WP_PHPUNIT__TESTS_CONFIG=' . __DIR__ . '/wp-tests-config.php' );

$atlas_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( false === $atlas_tests_dir || '' === $atlas_tests_dir ) {
	$atlas_tests_dir = $atlas_root . '/vendor/wp-phpunit/wp-phpunit';
}

require_once $atlas_tests_dir . '/includes/functions.php';

// One themes root per checkout, so two clones or worktrees can never test each
// other's theme through a shared symlink.
$atlas_themes_root = sys_get_temp_dir() . '/atlas-wp-tests-themes-' . substr( md5( $atlas_root ), 0, 10 );
$atlas_theme_link  = $atlas_themes_root . '/atlas-theme';
if ( ! is_dir( $atlas_themes_root ) ) {
	mkdir( $atlas_themes_root, 0777, true );
}
if ( is_link( $atlas_theme_link ) && realpath( $atlas_theme_link ) !== realpath( $atlas_root . '/theme' ) ) {
	unlink( $atlas_theme_link );
}
if ( ! file_exists( $atlas_theme_link ) ) {
	symlink( $atlas_root . '/theme', $atlas_theme_link );
}
if ( realpath( $atlas_theme_link ) !== realpath( $atlas_root . '/theme' ) ) {
	fwrite( STDERR, "Integration bootstrap: {$atlas_theme_link} does not point at this checkout's theme." . PHP_EOL );
	exit( 1 );
}

tests_add_filter(
	'muplugins_loaded',
	static function () use ( $atlas_themes_root ) {
		register_theme_directory( $atlas_themes_root );

		foreach ( array( 'template', 'stylesheet' ) as $atlas_option ) {
			add_filter( 'pre_option_' . $atlas_option, static fn() => 'atlas-theme' );
			add_filter( 'pre_option_' . $atlas_option . '_root', static fn() => $atlas_themes_root );
		}

		// The temporary root lives outside wp-content, which would make core
		// build theme URLs from the filesystem path. Serve it as if it were
		// wp-content/themes, like on a real site.
		add_filter(
			'theme_root_uri',
			static function ( $uri, $siteurl, $stylesheet_or_template ) use ( $atlas_themes_root ) {
				unset( $siteurl );
				return 'atlas-theme' === $stylesheet_or_template || $uri === $atlas_themes_root ? content_url( 'themes' ) : $uri;
			},
			10,
			3
		);
	}
);

require $atlas_tests_dir . '/includes/bootstrap.php';
