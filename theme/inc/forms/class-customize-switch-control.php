<?php
/**
 * Modern iOS / Gutenberg style Toggle Switch Customizer Control.
 *
 * Replaces standard HTML checkboxes in the WordPress Customizer with an
 * accessible, animated ON/OFF toggle switch for instant visual feedback.
 *
 * @package AtlasTheme
 */

namespace Atlas;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( '\WP_Customize_Control' ) ) {
	return;
}

/**
 * Toggle Switch Customizer Control.
 */
class Customize_Switch_Control extends \WP_Customize_Control {

	/**
	 * Control type slug.
	 *
	 * @var string
	 */
	public $type = 'atlas_switch';

	/**
	 * Render the control's content in the Customizer sidebar.
	 *
	 * @return void
	 */
	public function render_content(): void {
		$input_id = '_customize-input-' . $this->id;
		$is_on    = ( '1' === (string) $this->value() || true === $this->value() );
		?>
		<div class="atlas-switch-control">
			<div class="atlas-switch-info">
				<?php if ( ! empty( $this->label ) ) : ?>
					<label for="<?php echo esc_attr( $input_id ); ?>" class="customize-control-title">
						<?php echo esc_html( $this->label ); ?>
					</label>
				<?php endif; ?>
				<?php if ( ! empty( $this->description ) ) : ?>
					<span class="description customize-control-description">
						<?php echo esc_html( $this->description ); ?>
					</span>
				<?php endif; ?>
			</div>
			<div class="atlas-switch-wrapper">
				<label class="atlas-switch" for="<?php echo esc_attr( $input_id ); ?>">
					<input
						type="checkbox"
						id="<?php echo esc_attr( $input_id ); ?>"
						class="atlas-switch-input"
						value="1"
						<?php $this->link(); ?>
						<?php checked( $is_on ); ?>
					/>
					<span class="atlas-switch-track" aria-hidden="true">
						<span class="atlas-switch-thumb"></span>
					</span>
					<span class="atlas-switch-status" aria-hidden="true">
						<?php echo $is_on ? esc_html__( 'ON', 'atlas-theme' ) : esc_html__( 'OFF', 'atlas-theme' ); ?>
					</span>
				</label>
			</div>
		</div>
		<?php
	}
}
