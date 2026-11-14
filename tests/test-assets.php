<?php
/**
 * Tests for Atlas\Assets: copy controls, theme-mode script and CSP nonce.
 *
 * @package AtlasTheme
 */

class Test_Assets {

	public static function run(): array {
		global $atlas_test_enqueued_modules;
		$results = array();

		$plain = '<blockquote class="wp-block-quote"><p>Hi</p></blockquote>';

		$results[] = array(
			'name'   => 'Assets: a normal quote passes through untouched',
			'passed' => $plain === Atlas\Assets::render_copy_controls( $plain, array( 'attrs' => array() ) ),
		);

		$atlas_test_enqueued_modules = array();
		$copyable                    = Atlas\Assets::render_copy_controls(
			$plain,
			array( 'attrs' => array( 'className' => Atlas\Assets::COPY_MARKER ) )
		);

		$results[] = array(
			'name'   => 'Assets: a copyable quote gets controls inside the blockquote and enqueues the module',
			'passed' => str_contains( $copyable, 'data-wp-interactive="atlas/copy"' )
				&& strripos( $copyable, 'atlas-copy' ) < strripos( $copyable, '</blockquote>' )
				&& ! empty( $atlas_test_enqueued_modules[ Atlas\Assets::HANDLE_COPY ] ),
		);

		$results[] = array(
			'name'   => 'Assets: copy context JSON is attribute-escaped (no raw quotes break out)',
			'passed' => 1 === preg_match( "/data-wp-context='[^']*'/", $copyable ) && ! str_contains( $copyable, 'data-wp-context="' ),
		);

		$script = Atlas\Assets::theme_mode_script();

		$results[] = array(
			'name'   => 'Assets: theme-mode script is static, self-contained and free of template/PHP interpolation',
			'passed' => str_contains( $script, "localStorage.getItem('atlas-theme-mode')" )
				&& ! str_contains( $script, '<' )
				&& ! str_contains( $script, '$' ),
		);

		ob_start();
		Atlas\Assets::print_theme_mode_script();
		$printed = (string) ob_get_clean();

		$results[] = array(
			'name'   => 'Assets: the printed inline script carries the per-request nonce and exactly the hashed body',
			'passed' => str_contains( $printed, 'nonce="' . Atlas\Assets::get_csp_nonce() . '"' )
				&& str_contains( $printed, '>' . $script . '</script>' ),
		);

		$nonce = Atlas\Assets::get_csp_nonce();

		$results[] = array(
			'name'   => 'Assets: CSP nonce is memoized per request and is at least 128 bits of base64',
			'passed' => $nonce === Atlas\Assets::get_csp_nonce() && strlen( (string) base64_decode( $nonce, true ) ) >= 16,
		);

		return $results;
	}
}
