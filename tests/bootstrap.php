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

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
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

global $atlas_test_posts, $atlas_test_bloginfo, $atlas_test_status_header;
$atlas_test_posts         = array();
$atlas_test_status_header = 200;
$atlas_test_bloginfo      = array(
	'name'        => 'Atlas Test Site',
	'description' => 'Fast, accessible, multipurpose parent theme.',
);

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = 'name' ) {
		global $atlas_test_bloginfo;
		return $atlas_test_bloginfo[ $show ] ?? '';
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $string );
		$string = strip_tags( $string );
		if ( $remove_breaks ) {
			$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
		}
		return trim( $string );
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = null ) {
		global $atlas_test_posts;
		$results = array();
		$limit   = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : 50;
		$count   = 0;
		foreach ( $atlas_test_posts as $post ) {
			if ( $count >= $limit ) {
				break;
			}
			$results[] = (object) $post;
			$count++;
		}
		return $results;
	}
}

if ( ! function_exists( 'get_post' ) ) {
	function get_post( $post = null ) {
		global $atlas_test_posts;
		$id = is_object( $post ) ? ( $post->ID ?? 0 ) : (int) $post;
		return isset( $atlas_test_posts[ $id ] ) ? (object) $atlas_test_posts[ $id ] : null;
	}
}

if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $post = 0 ) {
		$p = get_post( $post );
		return $p ? ( $p->post_title ?? '' ) : '';
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post = 0 ) {
		$p = get_post( $post );
		return $p ? ( 'https://example.com/' . ( $p->post_name ?? 'page' ) . '/' ) : 'https://example.com/';
	}
}

if ( ! function_exists( 'get_the_excerpt' ) ) {
	function get_the_excerpt( $post = 0 ) {
		$p = get_post( $post );
		return $p ? ( $p->post_excerpt ?? '' ) : '';
	}
}

if ( ! function_exists( 'get_post_status' ) ) {
	function get_post_status( $post = 0 ) {
		$p = get_post( $post );
		return $p ? ( $p->post_status ?? 'publish' ) : false;
	}
}

if ( ! function_exists( 'get_post_type' ) ) {
	function get_post_type( $post = 0 ) {
		$p = get_post( $post );
		return $p ? ( $p->post_type ?? 'page' ) : false;
	}
}

if ( ! function_exists( 'is_post_type_viewable' ) ) {
	function is_post_type_viewable( $post_type ) {
		return in_array( $post_type, array( 'page', 'post' ), true );
	}
}

if ( ! function_exists( 'post_password_required' ) ) {
	function post_password_required( $post = null ) {
		$p = get_post( $post );
		return ! empty( $p->post_password );
	}
}

if ( ! function_exists( 'parse_blocks' ) ) {
	function parse_blocks( $content ) {
		if ( empty( $content ) ) {
			return array();
		}
		if ( preg_match_all( '/<!-- wp:([a-z0-9\/-]+)(?:\s+(\{.*?\}))?\s*-->(.*?)<!-- \/wp:\1 -->/s', $content, $matches, PREG_SET_ORDER ) ) {
			$blocks = array();
			foreach ( $matches as $match ) {
				$block_name = $match[1];
				if ( false === strpos( $block_name, '/' ) ) {
					$block_name = 'core/' . $block_name;
				}
				$attrs    = ! empty( $match[2] ) ? json_decode( $match[2], true ) : array();
				$blocks[] = array(
					'blockName'    => $block_name,
					'attrs'        => is_array( $attrs ) ? $attrs : array(),
					'innerHTML'    => $match[3],
					'innerContent' => array( $match[3] ),
					'innerBlocks'  => array(),
				);
			}
			return $blocks;
		}
		return array(
			array(
				'blockName'    => 'core/paragraph',
				'attrs'        => array(),
				'innerHTML'    => $content,
				'innerContent' => array( $content ),
				'innerBlocks'  => array(),
			),
		);
	}
}

if ( ! function_exists( 'status_header' ) ) {
	function status_header( $code, $description = '' ) {
		global $atlas_test_status_header;
		$atlas_test_status_header = (int) $code;
	}
}

if ( ! function_exists( 'render_block' ) ) {
	function render_block( $parsed_block ) {
		return $parsed_block['innerHTML'] ?? '';
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
