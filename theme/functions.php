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
 * Hand-rolled autoloader for the Nexus\ namespace.
 *
 * Maps Nexus\ to theme/inc/ using WordPress Coding Standards file naming:
 * each class resolves to a `class-{name}.php` file with the name lowercased
 * and underscores converted to hyphens (e.g. Nexus\Setup -> inc/class-setup.php,
 * Nexus\Foo_Bar -> inc/class-foo-bar.php). Sub-namespaces map to subdirectories.
 * We register our own loader rather than relying on Composer's because
 * agent-rules §4 bans runtime dependencies: no vendor/autoload.php ships with
 * the theme. Classes outside the Nexus\ namespace are ignored so this loader
 * never interferes with other autoloaders.
 *
 * @param string $class_name Fully-qualified class name requested by PHP.
 * @return void
 */
spl_autoload_register(
	function ( $class_name ) {
		if ( 0 !== strpos( $class_name, 'Nexus\\' ) ) {
			return;
		}

		$relative  = substr( $class_name, strlen( 'Nexus\\' ) );
		$parts     = explode( '\\', $relative );
		$class     = array_pop( $parts );
		$file_name = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';

		$sub_path = empty( $parts ) ? '' : implode( DIRECTORY_SEPARATOR, array_map( 'strtolower', $parts ) ) . DIRECTORY_SEPARATOR;
		$file     = NEXUS_THEME_DIR . '/inc/' . $sub_path . $file_name;

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

Nexus\Setup::init();
