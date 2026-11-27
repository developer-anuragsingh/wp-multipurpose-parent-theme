<?php
/**
 * PHPStan bootstrap: constants WordPress or functions.php define at runtime.
 *
 * PHPStan reads code without running it, so constants defined from runtime
 * values (get_template_directory()) are unknown to it. Values here only give
 * them a type; nothing executes this file in WordPress.
 *
 * @package AtlasTheme
 */
define( 'ATLAS_THEME_VERSION', '0.0.0' );
define( 'ATLAS_THEME_DIR', dirname( __DIR__, 2 ) . '/theme' );
define( 'ATLAS_THEME_URI', 'https://example.com/wp-content/themes/atlas-theme' );
define( 'WPINC', 'wp-includes' );
