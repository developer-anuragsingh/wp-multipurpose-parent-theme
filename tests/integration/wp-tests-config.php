<?php
/**
 * WordPress test-suite configuration for the integration suite.
 *
 * Every value comes from an environment variable so the same file serves CI
 * (MySQL service container) and local runs. The database is EMPTIED on every
 * run: never point it at a database you care about.
 *
 * @package AtlasTheme
 */

$atlas_env = static function ( string $name, string $fallback ): string {
	$value = getenv( $name );
	return false === $value || '' === $value ? $fallback : $value;
};

define( 'ABSPATH', dirname( __DIR__, 2 ) . '/vendor/roots/wordpress-no-content/' );

define( 'DB_NAME', $atlas_env( 'WP_TESTS_DB_NAME', 'wordpress_test' ) );
define( 'DB_USER', $atlas_env( 'WP_TESTS_DB_USER', 'root' ) );
define( 'DB_PASSWORD', $atlas_env( 'WP_TESTS_DB_PASSWORD', '' ) );
define( 'DB_HOST', $atlas_env( 'WP_TESTS_DB_HOST', '127.0.0.1' ) );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

// Optional: run against SQLite instead of MySQL (no database server needed).
// Set WP_TESTS_SQLITE_PLUGIN to a "sqlite-database-integration" plugin folder.
$atlas_sqlite = $atlas_env( 'WP_TESTS_SQLITE_PLUGIN', '' );
if ( '' !== $atlas_sqlite ) {
	$atlas_content = sys_get_temp_dir() . '/atlas-wp-tests-content-' . substr( md5( dirname( __DIR__, 2 ) ), 0, 10 );
	if ( ! is_dir( $atlas_content . '/plugins' ) ) {
		mkdir( $atlas_content . '/plugins', 0777, true );
	}
	if ( ! file_exists( $atlas_content . '/plugins/sqlite-database-integration' ) ) {
		symlink( $atlas_sqlite, $atlas_content . '/plugins/sqlite-database-integration' );
	}
	copy( $atlas_sqlite . '/db.copy', $atlas_content . '/db.php' );
	define( 'WP_CONTENT_DIR', $atlas_content );
	define( 'DB_ENGINE', 'sqlite' );
	define( 'DB_DIR', $atlas_content . '/database/' );
	define( 'DB_FILE', 'tests.sqlite' );
}

$table_prefix = 'wptests_'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

define( 'WP_TESTS_DOMAIN', 'example.org' );
define( 'WP_TESTS_EMAIL', 'admin@example.org' );
define( 'WP_TESTS_TITLE', 'Atlas Test Site' );
define( 'WP_PHP_BINARY', 'php' );
define( 'WPLANG', '' );
define( 'WP_DEBUG', true );
