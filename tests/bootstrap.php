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
		$url = (string) filter_var( $url, FILTER_SANITIZE_URL );
		// Mirror core: safe protocols only, and entity-encode for HTML output.
		if ( preg_match( '#^([a-z][a-z0-9+.-]*):#i', $url, $m ) && ! in_array( strtolower( $m[1] ), array( 'http', 'https', 'mailto', 'tel' ), true ) ) {
			return '';
		}
		return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		$url = (string) filter_var( $url, FILTER_SANITIZE_URL );
		// Mirror core: only allow safe protocols (never javascript:, data:, vbscript:).
		if ( preg_match( '#^([a-z][a-z0-9+.-]*):#i', $url, $m ) && ! in_array( strtolower( $m[1] ), array( 'http', 'https', 'mailto', 'tel' ), true ) ) {
			return '';
		}
		return $url;
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

if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) {
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

global $atlas_test_options;
$atlas_test_options = array();

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		global $atlas_test_options;
		$atlas_test_options[ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default_value = false ) {
		global $atlas_test_options;
		return $atlas_test_options[ $option ] ?? $default_value;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return false;
	}
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	function wp_doing_ajax() {
		return false;
	}
}

if ( ! function_exists( 'wp_doing_cron' ) ) {
	function wp_doing_cron() {
		return false;
	}
}

global $atlas_test_current_user_can;
$atlas_test_current_user_can = true;

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability, ...$args ) {
		global $atlas_test_current_user_can;
		if ( is_callable( $atlas_test_current_user_can ) ) {
			return call_user_func( $atlas_test_current_user_can, $capability, ...$args );
		}
		return (bool) $atlas_test_current_user_can;
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

// In-memory store for the private submission post type.
global $atlas_test_store, $atlas_test_store_next, $atlas_test_post_types, $atlas_test_cron, $atlas_test_policy;
$atlas_test_store      = array();
$atlas_test_store_next = 1;
$atlas_test_post_types = array();
$atlas_test_cron       = array();
$atlas_test_policy     = array();

foreach ( array( 'HOUR_IN_SECONDS' => 3600, 'DAY_IN_SECONDS' => 86400 ) as $atlas_test_const => $atlas_test_val ) {
	if ( ! defined( $atlas_test_const ) ) {
		define( $atlas_test_const, $atlas_test_val );
	}
}

if ( ! function_exists( 'wp_slash' ) ) {
	function wp_slash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'register_post_type' ) ) {
	function register_post_type( $type, $args = array() ) {
		global $atlas_test_post_types;
		$atlas_test_post_types[ $type ] = $args;
		return (object) $args;
	}
}

if ( ! function_exists( 'wp_insert_post' ) ) {
	function wp_insert_post( $postarr, $wp_error = false ) {
		global $atlas_test_store, $atlas_test_store_next;
		$id                         = $atlas_test_store_next++;
		$postarr['ID']              = $id;
		$postarr['post_date_gmt']   = $postarr['post_date_gmt'] ?? gmdate( 'Y-m-d H:i:s' );
		$postarr['meta']            = $postarr['meta_input'] ?? array();
		$atlas_test_store[ $id ]    = $postarr;
		return $id;
	}
}

if ( ! function_exists( 'wp_delete_post' ) ) {
	function wp_delete_post( $id, $force = false ) {
		global $atlas_test_store;
		if ( ! isset( $atlas_test_store[ $id ] ) ) {
			return false;
		}
		unset( $atlas_test_store[ $id ] );
		return true;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	function get_post_meta( $id, $key = '', $single = false ) {
		global $atlas_test_store;
		return $atlas_test_store[ $id ]['meta'][ $key ] ?? '';
	}
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook ) {
		global $atlas_test_cron;
		return $atlas_test_cron[ $hook ] ?? false;
	}
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook ) {
		global $atlas_test_cron;
		$atlas_test_cron[ $hook ] = array( 'at' => $timestamp, 'recurrence' => $recurrence );
		return true;
	}
}

if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
	function wp_add_privacy_policy_content( $name, $content ) {
		global $atlas_test_policy;
		$atlas_test_policy[ $name ] = $content;
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return $data;
	}
}

if ( ! function_exists( 'wpautop' ) ) {
	function wpautop( $text ) {
		return '<p>' . $text . '</p>';
	}
}

if ( ! function_exists( 'get_posts' ) ) {
	function get_posts( $args = null ) {
		global $atlas_test_posts, $atlas_test_store;

		if ( is_array( $args ) && 'atlas_submission' === ( $args['post_type'] ?? '' ) ) {
			$matches = array();
			foreach ( $atlas_test_store as $id => $row ) {
				if ( isset( $args['meta_query'][0]['key'] ) && ( $row['meta'][ $args['meta_query'][0]['key'] ] ?? null ) !== $args['meta_query'][0]['value'] ) {
					continue;
				}
				if ( isset( $args['date_query'][0]['before'] ) && ! ( $row['post_date_gmt'] < $args['date_query'][0]['before'] ) ) {
					continue;
				}
				$matches[ $id ] = $row;
			}
			ksort( $matches );
			$per     = (int) ( $args['posts_per_page'] ?? 50 );
			$paged   = max( 1, (int) ( $args['paged'] ?? 1 ) );
			$matches = array_slice( $matches, ( $paged - 1 ) * $per, $per, true );
			return 'ids' === ( $args['fields'] ?? '' ) ? array_keys( $matches ) : array_map( static fn( $r ) => (object) $r, array_values( $matches ) );
		}

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
		global $atlas_test_posts, $atlas_test_store, $atlas_test_request;
		if ( $post instanceof WP_Post ) {
			return $post;
		}
		if ( null === $post && isset( $atlas_test_request['post'] ) ) {
			return $atlas_test_request['post'];
		}
		$sid = is_object( $post ) ? ( $post->ID ?? 0 ) : (int) $post;
		if ( isset( $atlas_test_store[ $sid ] ) ) {
			return (object) $atlas_test_store[ $sid ];
		}
		$id = is_object( $post ) ? ( $post->ID ?? 0 ) : (int) $post;
		return isset( $atlas_test_posts[ $id ] ) ? (object) $atlas_test_posts[ $id ] : null;
	}
}

if ( ! function_exists( 'get_post_field' ) ) {
	function get_post_field( $field, $post = null ) {
		$p = get_post( $post );
		return $p ? ( $p->$field ?? '' ) : '';
	}
}

if ( ! function_exists( 'get_locale' ) ) {
	function get_locale() {
		return 'en_US';
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

global $atlas_test_headers;
$atlas_test_headers = array();

if ( ! function_exists( 'nocache_headers' ) ) {
	function nocache_headers() {
		global $atlas_test_headers;
		if ( ! is_array( $atlas_test_headers ) ) {
			$atlas_test_headers = array();
		}
		$atlas_test_headers['Cache-Control'] = 'no-cache, must-revalidate, max-age=0';
		$atlas_test_headers['Expires']       = 'Wed, 11 Jan 1984 05:00:00 GMT';
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

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	function add_query_arg( ...$args ) {
		if ( 2 === count( $args ) && is_array( $args[0] ) ) {
			$params = $args[0];
			$url    = $args[1];
		} elseif ( 3 === count( $args ) ) {
			$params = array( $args[0] => $args[1] );
			$url    = $args[2];
		} else {
			return '';
		}
		$sep = false !== strpos( $url, '?' ) ? '&' : '?';
		return $url . $sep . http_build_query( $params );
	}
}

if ( ! function_exists( 'remove_query_arg' ) ) {
	function remove_query_arg( $key, $url = '' ) {
		$parts = explode( '?', $url, 2 );
		if ( count( $parts ) < 2 ) {
			return $url;
		}
		parse_str( $parts[1], $query );
		if ( is_array( $key ) ) {
			foreach ( $key as $k ) {
				unset( $query[ $k ] );
			}
		} else {
			unset( $query[ $key ] );
		}
		return empty( $query ) ? $parts[0] : $parts[0] . '?' . http_build_query( $query );
	}
}

// Block-template doubles (maintenance override).
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

global $atlas_test_block_templates;
$atlas_test_block_templates = array();

if ( ! function_exists( 'get_stylesheet' ) ) {
	function get_stylesheet() {
		return 'atlas-theme';
	}
}

if ( ! function_exists( 'get_block_template' ) ) {
	function get_block_template( $id, $template_type = 'wp_template' ) {
		global $atlas_test_block_templates;
		return $atlas_test_block_templates[ $id ] ?? null;
	}
}

// Request-context doubles for Seo/Schema (conditional tags driven by one array).
global $atlas_test_request;
$atlas_test_request = array();

foreach ( array( 'is_search', 'is_archive', 'is_front_page', 'is_home', 'is_category', 'is_tag', 'is_tax', 'is_post_type_archive' ) as $atlas_test_tag ) {
	if ( ! function_exists( $atlas_test_tag ) ) {
		eval( 'function ' . $atlas_test_tag . '() { global $atlas_test_request; return ! empty( $atlas_test_request["' . $atlas_test_tag . '"] ); }' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- Test-only generated doubles.
	}
}

if ( ! function_exists( 'is_singular' ) ) {
	function is_singular( $type = '' ) {
		global $atlas_test_request;
		$singular = $atlas_test_request['is_singular'] ?? '';
		return '' !== $singular && ( '' === $type || $type === $singular );
	}
}

if ( ! function_exists( 'get_the_ID' ) ) {
	function get_the_ID() {
		global $atlas_test_request;
		return $atlas_test_request['post_id'] ?? false;
	}
}

if ( ! function_exists( 'get_query_var' ) ) {
	function get_query_var( $var, $default = '' ) {
		global $wp_query, $atlas_test_request;
		if ( isset( $atlas_test_request[ $var ] ) ) {
			return $atlas_test_request[ $var ];
		}
		if ( is_object( $wp_query ) && isset( $wp_query->$var ) ) {
			return $wp_query->$var;
		}
		if ( is_object( $wp_query ) && isset( $wp_query->query_vars[ $var ] ) ) {
			return $wp_query->query_vars[ $var ];
		}
		return $default;
	}
}

if ( ! function_exists( 'wp_get_document_title' ) ) {
	function wp_get_document_title() {
		global $atlas_test_request;
		return $atlas_test_request['title'] ?? 'Atlas Test Site';
	}
}

if ( ! function_exists( 'wp_get_canonical_url' ) ) {
	function wp_get_canonical_url() {
		global $atlas_test_request;
		return $atlas_test_request['canonical'] ?? false;
	}
}

if ( ! function_exists( 'get_the_post_thumbnail_url' ) ) {
	function get_the_post_thumbnail_url( $post = null, $size = 'post-thumbnail' ) {
		global $atlas_test_request;
		return $atlas_test_request['thumbnail'] ?? false;
	}
}

if ( ! function_exists( 'get_theme_mod' ) ) {
	function get_theme_mod( $name, $default_value = false ) {
		return $default_value;
	}
}

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
	function wp_get_attachment_image_url( $id, $size = 'thumbnail' ) {
		return false;
	}
}

global $atlas_test_removed_actions;
$atlas_test_removed_actions = array();

if ( ! function_exists( 'remove_action' ) ) {
	function remove_action( $hook, $callback, $priority = 10 ) {
		global $atlas_test_removed_actions;
		$atlas_test_removed_actions[] = $hook . '|' . $callback . '|' . $priority;
		return true;
	}
}

// Schema doubles: WP_Post / WP_Term / WP_Post_Type and the template tags the graph reads.
if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public $ID            = 0;
		public $post_title    = '';
		public $post_name     = '';
		public $post_content  = '';
		public $post_author   = 1;
		public $post_date     = '2026-01-02 03:04:05';
		public $post_modified = '2026-02-03 04:05:06';
		public function __construct( array $props = array() ) {
			foreach ( $props as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Term' ) ) {
	class WP_Term {
		public $term_id = 0;
		public $name    = '';
		public function __construct( array $props = array() ) {
			foreach ( $props as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Post_Type' ) ) {
	class WP_Post_Type {
		public $name   = '';
		public $labels;
		public function __construct( string $name, string $label ) {
			$this->name   = $name;
			$this->labels = (object) array( 'name' => $label );
		}
	}
}

foreach ( array(
	'get_queried_object'         => '$atlas_test_request["queried"] ?? null',
	'get_the_category'          => '$atlas_test_request["categories"] ?? array()',
	'get_category_link'         => '"https://example.com/category/" . (int) $a',
	'get_term_link'             => '"https://example.com/term/" . (int) $a->term_id',
	'get_post_type_archive_link' => '"https://example.com/" . $a . "/"',
	'get_the_author_meta'       => '$atlas_test_request["author"] ?? "Jane Doe"',
	'get_the_date'              => 'gmdate( "c", strtotime( $b->post_date ) )',
	'get_the_modified_date'     => 'gmdate( "c", strtotime( $b->post_modified ) )',
) as $atlas_test_fn => $atlas_test_expr ) {
	if ( ! function_exists( $atlas_test_fn ) ) {
		// get_the_date / get_the_modified_date take ( $format, $post ); the rest take one argument.
		$atlas_test_sig = in_array( $atlas_test_fn, array( 'get_the_date', 'get_the_modified_date', 'get_the_author_meta' ), true ) ? '$a = null, $b = null' : '$a = null';
		eval( 'function ' . $atlas_test_fn . '( ' . $atlas_test_sig . ' ) { global $atlas_test_request; return ' . $atlas_test_expr . '; }' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged -- Test-only generated doubles.
	}
}

// Contact-form / REST doubles.
global $atlas_test_mail, $atlas_test_mail_result, $atlas_test_ext_cache, $atlas_test_object_cache;
$atlas_test_mail        = array();
$atlas_test_mail_result = true;
$atlas_test_ext_cache   = false;
$atlas_test_object_cache = array();

if ( ! function_exists( 'wp_salt' ) ) {
	function wp_salt( $scheme = 'auth' ) {
		return 'atlas-test-salt-' . $scheme;
	}
}

if ( ! function_exists( 'is_email' ) ) {
	function is_email( $email ) {
		return false !== filter_var( $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return trim( (string) $email );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return is_string( $str ) ? trim( strip_tags( $str ) ) : '';
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'wp_get_referer' ) ) {
	function wp_get_referer() {
		return 'https://example.com/contact/';
	}
}

if ( ! function_exists( 'rest_url' ) ) {
	function rest_url( $path = '' ) {
		return 'https://example.com/wp-json/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'wp_mail' ) ) {
	function wp_mail( $to, $subject, $message, $headers = '' ) {
		global $atlas_test_mail, $atlas_test_mail_result;
		$atlas_test_mail[] = compact( 'to', 'subject', 'message', 'headers' );
		return $atlas_test_mail_result;
	}
}

if ( ! function_exists( 'wp_using_ext_object_cache' ) ) {
	function wp_using_ext_object_cache() {
		global $atlas_test_ext_cache;
		return $atlas_test_ext_cache;
	}
}

if ( ! function_exists( 'wp_cache_add' ) ) {
	function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
		global $atlas_test_object_cache;
		if ( isset( $atlas_test_object_cache[ $group . $key ] ) ) {
			return false;
		}
		$atlas_test_object_cache[ $group . $key ] = $data;
		return true;
	}
}

if ( ! function_exists( 'wp_cache_incr' ) ) {
	function wp_cache_incr( $key, $offset = 1, $group = '' ) {
		global $atlas_test_object_cache;
		if ( ! isset( $atlas_test_object_cache[ $group . $key ] ) ) {
			return false;
		}
		$atlas_test_object_cache[ $group . $key ] += $offset;
		return $atlas_test_object_cache[ $group . $key ];
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $code;
		public $message;
		public $data;
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}
		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	class WP_REST_Server {
		const READABLE  = 'GET';
		const CREATABLE = 'POST';
	}
}

if ( ! class_exists( 'WP_HTTP_Response' ) ) {
	class WP_HTTP_Response {
		public $data;
		public $status;
		public $headers = array();
		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}
		public function header( $key, $value ) {
			$this->headers[ $key ] = $value;
		}
		public function get_status() {
			return $this->status;
		}
		public function get_data() {
			return $this->data;
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response extends WP_HTTP_Response {}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {
		private $params;
		private $headers;
		public function __construct( array $params = array(), array $headers = array() ) {
			$this->params  = $params;
			$this->headers = array_change_key_case( $headers, CASE_LOWER );
		}
		public function get_param( $key ) {
			return $this->params[ $key ] ?? null;
		}
		public function get_header( $key ) {
			return $this->headers[ strtolower( $key ) ] ?? null;
		}
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
