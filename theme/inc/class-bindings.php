<?php
/**
 * Block Bindings concern.
 *
 * Owns the Atlas theme's custom Block Bindings source and the demo post meta it
 * reads. Registers one binding source (atlas/post-meta) that connects a
 * registered post meta value to the bindable attributes of core blocks
 * (core/paragraph, core/heading content; core/image, core/button url; core/image
 * alt), editable inline in the Editor (WordPress 6.7+). All resolved values are
 * escaped for their target attribute and fail closed to a safe empty string when
 * the post id or meta is missing. Mirrors Atlas\UI_States precisely — a static
 * registrar wired from functions.php after Atlas\UI_States::init(). Resolved via
 * the hand-rolled autoloader (Atlas\ -> inc/).
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static registrar for the Block Bindings features.
 *
 * Static by design: there is a single theme instance per request, so no state
 * needs to be carried on an object. All public surface is reachable from the
 * WordPress hooks wired in init().
 */
class Bindings {

	/**
	 * Binding source name the custom source registers under.
	 *
	 * Namespaced (atlas/) per the Atlas\ prefix rule; post-meta names what it
	 * binds. Not user-facing, so it is a semantic constant (not gettext). The
	 * demo pattern's block markup repeats this literal string because block
	 * markup cannot reference a PHP constant — keep the two in lockstep.
	 *
	 * @var string
	 */
	const SOURCE = 'atlas/post-meta';

	/**
	 * Demo post meta key the binding source reads.
	 *
	 * A benign, non-PII, display-oriented short string (a post subtitle/deck).
	 * Prefixed atlas_ per the global-prefix rule. Not user-facing, so it is a
	 * semantic constant (not gettext). The demo pattern repeats this literal in
	 * its args.key — keep the two in lockstep.
	 *
	 * @var string
	 */
	const META_SUBTITLE = 'atlas_subtitle';

	/**
	 * Register the Block Bindings WordPress hooks.
	 *
	 * Called once from functions.php immediately after Atlas\UI_States::init().
	 * Both the post meta and the binding source must be registered on the init
	 * hook, so each is wired there. Keeps all bindings hook wiring in one place
	 * so the boot order is explicit.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
		add_action( 'init', array( self::class, 'register_sources' ) );
	}

	/**
	 * Register the demo post meta the binding source reads.
	 *
	 * Runs on init. Registers a single string meta on the core 'post' type that
	 * is readable/writable over REST (so the Editor can edit the bound value
	 * inline) with a typed single-string schema. The value is sanitized as plain
	 * display text. Authorization is an object-level capability check
	 * (current_user_can( 'edit_post', $post_id )) — not a blanket role check — so
	 * only users who may edit the specific post may write its subtitle.
	 *
	 * @return void
	 */
	public static function register_meta(): void {
		register_post_meta(
			'post',
			self::META_SUBTITLE,
			array(
				'single'            => true,
				'type'              => 'string',
				'default'           => '',
				'show_in_rest'      => array(
					'schema' => array(
						'type' => 'string',
					),
				),
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => array( self::class, 'can_edit_meta' ),
			)
		);
	}

	/**
	 * Authorization callback for the demo post meta.
	 *
	 * Performs an object-level capability check: the current user may read or
	 * write the meta only when they can edit the specific post it belongs to. A
	 * blanket role check (or a nonce) is deliberately not used — authorization is
	 * per-object. register_post_meta passes the post id as the third argument.
	 *
	 * @param bool   $allowed  Whether the user can act on the meta (ignored; recomputed).
	 * @param string $meta_key The meta key being checked (unused; single key).
	 * @param int    $post_id  The id of the post the meta belongs to.
	 * @return bool True when the current user may edit the specific post.
	 */
	public static function can_edit_meta( $allowed, $meta_key, $post_id ): bool {
		unset( $allowed, $meta_key );

		return current_user_can( 'edit_post', absint( $post_id ) );
	}

	/**
	 * Register the custom Block Bindings source.
	 *
	 * Runs on init. Guards function_exists( 'register_block_bindings_source' )
	 * so the theme fails closed on cores older than the bindings API rather than
	 * fataling (belt-and-suspenders: the theme's WP floor already ships it). The
	 * source declares the postId/postType context it needs so get_meta_value can
	 * resolve the post from the block's context.
	 *
	 * @return void
	 */
	public static function register_sources(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			self::SOURCE,
			array(
				'label'              => __( 'Atlas Post Meta', 'atlas-theme' ),
				'get_value_callback' => array( self::class, 'get_meta_value' ),
				'uses_context'       => array( 'postId', 'postType' ),
			)
		);
	}

	/**
	 * Resolve a bound attribute value from the demo post meta.
	 *
	 * The Block Bindings value callback (signature: source args, block instance,
	 * attribute name). Fails closed throughout: resolves the post id from the
	 * block's context (declared via uses_context) and returns '' immediately when
	 * it is missing, then reads the requested meta key (from the pattern-supplied
	 * args.key, falling back to the demo key) and returns '' when it is empty. A
	 * non-empty value is escaped for its target attribute — esc_html for content,
	 * esc_url for url, esc_attr for alt, esc_html for anything else — so the
	 * callback never returns null and never returns an unescaped string.
	 *
	 * @param array                 $source_args    Arguments passed from the block's binding (e.g. 'key').
	 * @param \WP_Block             $block_instance The block instance consuming the binding.
	 * @param string                $attribute_name The bound attribute (content, url, alt, ...).
	 * @return string The escaped meta value, or '' when the post or meta is missing.
	 */
	public static function get_meta_value( array $source_args, $block_instance, string $attribute_name ): string {
		$post_id = isset( $block_instance->context['postId'] )
			? absint( $block_instance->context['postId'] )
			: 0;

		if ( 0 === $post_id ) {
			return '';
		}

		$key = isset( $source_args['key'] ) ? (string) $source_args['key'] : self::META_SUBTITLE;
		$raw = get_post_meta( $post_id, $key, true );

		if ( '' === $raw || null === $raw || false === $raw ) {
			return '';
		}

		switch ( $attribute_name ) {
			case 'url':
				return esc_url( $raw );
			case 'alt':
				return esc_attr( $raw );
			case 'content':
			default:
				return esc_html( $raw );
		}
	}
}
