<?php
/**
 * readme.txt and SECURITY.md guards.
 *
 * WordPress reads a theme's requirements from style.css, and the WordPress.org
 * directory reads them from readme.txt. When the two disagree, users see one
 * set of requirements and get another. The parent readme also drifted to a
 * Stable tag three releases old, so the release build now stamps it, and these
 * checks keep the build doing that.
 *
 * @package AtlasTheme
 */
class Test_Readme {
	/**
	 * Header fields of a readme.txt or a style.css ("Name: value" lines).
	 *
	 * @param string $file Absolute path.
	 * @return array<string,string> Field name => value.
	 */
	private static function headers( string $file ): array {
		$fields = array();
		$text   = (string) file_get_contents( $file );
		if ( preg_match_all( '/^[ \t\/*]*([A-Za-z][A-Za-z ]+?):[ \t]*(.+?)[ \t]*$/m', $text, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $row ) {
				if ( ! isset( $fields[ $row[1] ] ) ) {
					$fields[ $row[1] ] = $row[2];
				}
			}
		}
		return $fields;
	}
	public static function run(): array {
		$results = array();
		$root    = dirname( ATLAS_THEME_DIR );
		$themes  = array( 'atlas-theme' => ATLAS_THEME_DIR );
		foreach ( glob( $root . '/child-themes/*', GLOB_ONLYDIR ) as $dir ) {
			$themes[ basename( $dir ) ] = $dir;
		}
		$required = array( 'Contributors', 'Requires at least', 'Tested up to', 'Requires PHP', 'Stable tag', 'License', 'License URI' );
		foreach ( $themes as $slug => $dir ) {
			$readme_file = $dir . '/readme.txt';
			$results[]   = array(
				'name'   => "Readme: {$slug} ships a readme.txt",
				'passed' => is_file( $readme_file ),
			);
			if ( ! is_file( $readme_file ) ) {
				continue;
			}
			$readme  = self::headers( $readme_file );
			$style   = self::headers( $dir . '/style.css' );
			$missing = array_diff( $required, array_keys( $readme ) );
			$results[] = array(
				'name'   => "Readme: {$slug} readme.txt has every WordPress.org header field" . ( $missing ? ' (missing: ' . implode( ', ', $missing ) . ')' : '' ),
				'passed' => array() === $missing,
			);
			foreach ( array( 'Requires at least', 'Tested up to', 'Requires PHP' ) as $field ) {
				$results[] = array(
					'name'   => sprintf( 'Readme: %s "%s" matches style.css (%s vs %s)', $slug, $field, $readme[ $field ] ?? '-', $style[ $field ] ?? '-' ),
					'passed' => isset( $readme[ $field ], $style[ $field ] ) && $readme[ $field ] === $style[ $field ],
				);
			}
			$results[] = array(
				'name'   => "Readme: {$slug} Stable tag is a version number",
				'passed' => 1 === preg_match( '/^\d+\.\d+\.\d+$/', $readme['Stable tag'] ?? '' ),
			);
		}
		$build     = (string) file_get_contents( $root . '/scripts/build-release.sh' );
		$results[] = array(
			'name'   => 'Readme: the release build stamps the Stable tag of the parent and every child zip from style.css',
			'passed' => str_contains( $build, 'stamp_stable_tag "${STAGE}/${SLUG}/readme.txt" "${VERSION}"' )
				&& str_contains( $build, 'stamp_stable_tag "${CHILD_STAGE}/${CHILD_SLUG}/readme.txt" "${CHILD_VER}"' ),
		);
		$security  = is_file( $root . '/SECURITY.md' ) ? (string) file_get_contents( $root . '/SECURITY.md' ) : '';
		$results[] = array(
			'name'   => 'Security policy: SECURITY.md exists and asks for private reporting, not a public issue',
			'passed' => str_contains( $security, 'Report a vulnerability' ) && str_contains( $security, 'Do not open a public issue' ),
		);
		$parent    = (string) file_get_contents( ATLAS_THEME_DIR . '/readme.txt' );
		$results[] = array(
			'name'   => 'Readme: the parent readme points users to the security policy',
			'passed' => str_contains( $parent, 'SECURITY.md' ),
		);
		return $results;
	}
}
