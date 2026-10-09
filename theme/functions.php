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

/**
 * Hand-rolled PSR-4-style autoloader for the Nexus\ namespace.
 *
 * Maps Nexus\ to theme/inc/ (e.g. Nexus\Setup -> inc/Setup.php). We register
 * our own loader rather than relying on Composer's because agent-rules §4 bans
 * runtime dependencies: no vendor/autoload.php ships with the theme, so the
 * autoloader must be self-contained. Classes outside the Nexus\ namespace are
 * ignored so this loader never interferes with other autoloaders.
 *
 * @param string $class_name Fully-qualified class name requested by PHP.
 * @return void
 */
spl_autoload_register(
	function ( $class_name ) {
		if ( 0 !== strpos( $class_name, 'Nexus\\' ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( 'Nexus\\' ) );
		$relative = str_replace( '\\', DIRECTORY_SEPARATOR, $relative );
		$file     = NEXUS_THEME_DIR . '/inc/' . $relative . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

Nexus\Setup::init();
