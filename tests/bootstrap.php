<?php
/**
 * Test bootstrap file for Atlas theme unit tests.
 *
 * Provides a lightweight WordPress test double environment allowing fast,
 * isolated unit testing of Atlas theme PHP concerns without requiring a
 * heavy MySQL database or external WordPress installation.
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! defined( 'ATLAS_THEME_DIR' ) ) {
	define( 'ATLAS_THEME_DIR', dirname( __DIR__ ) . '/theme' );
}

if ( ! defined( 'ATLAS_THEME_URI' ) ) {
	define( 'ATLAS_THEME_URI', 'https://example.com/wp-content/themes/atlas-theme' );
}

if ( ! defined( 'ATLAS_THEME_VERSION' ) ) {
	define( 'ATLAS_THEME_VERSION', '1.0.0' );
}

if ( ! function_exists( '__return_true' ) ) {
	function __return_true() {
		return true;
	}
}

if ( ! function_exists( '__return_false' ) ) {
	function __return_false() {
		return false;
	}
}

// Global filter/hook mocks.
global $atlas_test_filters, $atlas_test_actions;
$atlas_test_filters = array();
$atlas_test_actions = array();

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		global $atlas_test_filters;
		$atlas_test_filters[ $hook ][] = array(
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook, $value, ...$args ) {
		global $atlas_test_filters;
		if ( ! empty( $atlas_test_filters[ $hook ] ) ) {
			foreach ( $atlas_test_filters[ $hook ] as $item ) {
				$callback = $item['callback'];
				$value    = call_user_func( $callback, $value, ...$args );
			}
		}
		return $value;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		global $atlas_test_actions;
		$atlas_test_actions[ $hook ][] = array(
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook, ...$args ) {
		global $atlas_test_actions;
		if ( ! empty( $atlas_test_actions[ $hook ] ) ) {
			foreach ( $atlas_test_actions[ $hook ] as $item ) {
				$callback = $item['callback'];
				call_user_func( $callback, ...$args );
			}
		}
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return is_string( $str ) ? trim( strip_tags( $str ) ) : '';
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo $text;
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

global $atlas_test_enqueued_modules;
$atlas_test_enqueued_modules = array();

if ( ! function_exists( 'wp_enqueue_script_module' ) ) {
	function wp_enqueue_script_module( $handle ) {
		global $atlas_test_enqueued_modules;
		$atlas_test_enqueued_modules[ $handle ] = true;
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return 'https://example.com' . $path;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default_value = false ) {
		return $default_value;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		return true;
	}
}

global $atlas_test_transients;
$atlas_test_transients = array();

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		global $atlas_test_transients;
		return $atlas_test_transients[ $transient ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		global $atlas_test_transients;
		$atlas_test_transients[ $transient ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		global $atlas_test_transients;
		unset( $atlas_test_transients[ $transient ] );
		return true;
	}
}

// Register the theme autoloader.
spl_autoload_register(
	function ( $class_name ) {
		if ( 0 !== strpos( $class_name, 'Atlas\\' ) ) {
			return;
		}

		$relative  = substr( $class_name, strlen( 'Atlas\\' ) );
		$parts     = explode( '\\', $relative );
		$class     = array_pop( $parts );
		$file_name = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';

		$sub_path = empty( $parts ) ? '' : implode( DIRECTORY_SEPARATOR, array_map( 'strtolower', $parts ) ) . DIRECTORY_SEPARATOR;
		$file     = ATLAS_THEME_DIR . '/inc/' . $sub_path . $file_name;

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);
