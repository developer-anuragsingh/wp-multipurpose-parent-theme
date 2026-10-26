<?php
/**
 * Title: Contact Form
 * Slug: atlas/contact-form
 * Categories: atlas
 * Inserter: true
 * Text Domain: atlas-theme
 *
 * Accessible contact form with a working no-JS fallback and a progressively
 * enhanced path. The real <form method="post" action="…"> posts straight to the
 * atlas/v1/contact REST route so a visitor without JS still submits and the
 * server handles the request; the atlas/contact-form Interactivity store
 * intercepts the submit, validates inline for UX, and POSTs the same payload
 * with the seeded wp_rest nonce (one server handler, one validation path).
 *
 * This is a PHP pattern (not static block markup) because it renders per-request
 * values at output time: the no-JS action URL, a hidden _wpnonce for the plain
 * POST, a timing timestamp, and an escaped server-side status message when the
 * no-JS redirect returns ?atlas-contact=sent|error|invalid. Every visible string
 * is translated and escaped at the point of output.
 *
 * @package AtlasTheme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$atlas_contact_action = esc_url( rest_url( 'atlas/v1/contact' ) );
$atlas_contact_ts     = esc_attr( (string) time() );

// Per-instance ids so multiple forms on one page never collide.
$atlas_contact_uid     = wp_unique_id( 'atlas-contact-' );
$atlas_name_id         = $atlas_contact_uid . '-name';
$atlas_name_error_id   = $atlas_contact_uid . '-name-error';
$atlas_email_id        = $atlas_contact_uid . '-email';
$atlas_email_error_id  = $atlas_contact_uid . '-email-error';
$atlas_message_id      = $atlas_contact_uid . '-message';
$atlas_message_err_id  = $atlas_contact_uid . '-message-error';
$atlas_status_id       = $atlas_contact_uid . '-status';

// Per-instance Interactivity context: submit state + per-field validity.
$atlas_contact_context = wp_json_encode(
	array(
		'submitting' => false,
		'status'     => '',
		'statusType' => '',
		'fields'     => array(
			'name'    => array(
				'valid'   => true,
				'touched' => false,
			),
			'email'   => array(
				'valid'   => true,
				'touched' => false,
			),
			'message' => array(
				'valid'   => true,
				'touched' => false,
			),
		),
	)
);

// No-JS server-side status, shown when the REST redirect returns the flag.
$atlas_contact_outcome = isset( $_GET['atlas-contact'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display flag from our own post-submit redirect; no state change.
	? sanitize_key( wp_unslash( $_GET['atlas-contact'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	: '';

$atlas_contact_messages = array(
	'sent'    => __( 'Thanks — your message has been sent.', 'atlas-theme' ),
	'invalid' => __( 'Please check the form and try again.', 'atlas-theme' ),
	'error'   => __( 'Something went wrong. Please try again later.', 'atlas-theme' ),
);
?>

<!-- wp:group {"tagName":"section","layout":{"type":"constrained"},"style":{"spacing":{"blockGap":"var:preset|spacing|40"}}} -->
<section class="wp-block-group">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading"><?php echo esc_html__( 'Contact us', 'atlas-theme' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:html -->
	<form
		class="atlas-contact-form"
		method="post"
		action="<?php echo $atlas_contact_action; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped with esc_url() above. ?>"
		data-wp-interactive="atlas/contact-form"
		data-wp-context='<?php echo esc_attr( $atlas_contact_context ); ?>'
		data-wp-on--submit="actions.submit"
		data-hp-field="atlas_contact_hp"
		data-ts-field="atlas_contact_ts"
	>
		<?php
		// Nonce for the no-JS POST path; the enhanced path sends it as the
		// X-WP-Nonce header instead. Printed, not returned.
		wp_nonce_field( 'wp_rest', '_wpnonce', false, true );
		?>

		<p
			id="<?php echo esc_attr( $atlas_status_id ); ?>"
			class="atlas-contact-form__status"
			data-role="status"
			tabindex="-1"
			role="status"
			aria-live="polite"
			<?php echo '' !== $atlas_contact_outcome && isset( $atlas_contact_messages[ $atlas_contact_outcome ] ) ? 'data-status-type="' . ( 'sent' === $atlas_contact_outcome ? 'success' : 'error' ) . '"' : ''; ?>
			data-wp-text="state.statusText"
			data-wp-bind--data-status-type="context.statusType"
		><?php echo '' !== $atlas_contact_outcome && isset( $atlas_contact_messages[ $atlas_contact_outcome ] ) ? esc_html( $atlas_contact_messages[ $atlas_contact_outcome ] ) : ''; ?></p>

		<div class="atlas-contact-form__field">
			<label class="atlas-contact-form__label" for="<?php echo esc_attr( $atlas_name_id ); ?>"><?php echo esc_html__( 'Name', 'atlas-theme' ); ?></label>
			<input
				class="atlas-contact-form__input"
				type="text"
				id="<?php echo esc_attr( $atlas_name_id ); ?>"
				name="name"
				data-field="name"
				required
				maxlength="100"
				autocomplete="name"
				aria-describedby="<?php echo esc_attr( $atlas_name_error_id ); ?>"
				data-wp-bind--aria-invalid="state.nameAriaInvalid"
				data-wp-on--blur="actions.validateField"
				data-wp-on--input="actions.revalidateIfErrored"
			/>
			<p class="atlas-contact-form__error" id="<?php echo esc_attr( $atlas_name_error_id ); ?>" role="alert" aria-live="polite" data-wp-text="state.nameErrorText"></p>
		</div>

		<div class="atlas-contact-form__field">
			<label class="atlas-contact-form__label" for="<?php echo esc_attr( $atlas_email_id ); ?>"><?php echo esc_html__( 'Email', 'atlas-theme' ); ?></label>
			<input
				class="atlas-contact-form__input"
				type="email"
				id="<?php echo esc_attr( $atlas_email_id ); ?>"
				name="email"
				data-field="email"
				required
				maxlength="254"
				autocomplete="email"
				aria-describedby="<?php echo esc_attr( $atlas_email_error_id ); ?>"
				data-wp-bind--aria-invalid="state.emailAriaInvalid"
				data-wp-on--blur="actions.validateField"
				data-wp-on--input="actions.revalidateIfErrored"
			/>
			<p class="atlas-contact-form__error" id="<?php echo esc_attr( $atlas_email_error_id ); ?>" role="alert" aria-live="polite" data-wp-text="state.emailErrorText"></p>
		</div>

		<div class="atlas-contact-form__field">
			<label class="atlas-contact-form__label" for="<?php echo esc_attr( $atlas_message_id ); ?>"><?php echo esc_html__( 'Message', 'atlas-theme' ); ?></label>
			<textarea
				class="atlas-contact-form__textarea"
				id="<?php echo esc_attr( $atlas_message_id ); ?>"
				name="message"
				data-field="message"
				required
				minlength="10"
				maxlength="5000"
				rows="6"
				aria-describedby="<?php echo esc_attr( $atlas_message_err_id ); ?>"
				data-wp-bind--aria-invalid="state.messageAriaInvalid"
				data-wp-on--blur="actions.validateField"
				data-wp-on--input="actions.revalidateIfErrored"
			></textarea>
			<p class="atlas-contact-form__error" id="<?php echo esc_attr( $atlas_message_err_id ); ?>" role="alert" aria-live="polite" data-wp-text="state.messageErrorText"></p>
		</div>

		<?php /* Honeypot: visually hidden, out of the tab order, hidden from AT. A real human never fills it. */ ?>
		<div class="atlas-contact-form__hp" aria-hidden="true">
			<label for="<?php echo esc_attr( $atlas_contact_uid ); ?>-hp"><?php echo esc_html__( 'Leave this field empty', 'atlas-theme' ); ?></label>
			<input
				type="text"
				id="<?php echo esc_attr( $atlas_contact_uid ); ?>-hp"
				name="atlas_contact_hp"
				data-role="hp"
				tabindex="-1"
				autocomplete="off"
				value=""
			/>
		</div>

		<?php /* Timing field: the render timestamp, checked server-side against MIN_SUBMIT_SECONDS. */ ?>
		<input type="hidden" name="atlas_contact_ts" data-role="ts" value="<?php echo $atlas_contact_ts; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped with esc_attr() above. ?>" />

		<button
			class="atlas-contact-form__submit"
			type="submit"
			data-wp-bind--disabled="state.isSubmitting"
		><?php echo esc_html__( 'Send message', 'atlas-theme' ); ?></button>
	</form>
	<!-- /wp:html -->
</section>
<!-- /wp:group -->
