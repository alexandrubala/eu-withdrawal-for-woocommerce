<?php
/**
 * Step 2 – read-only confirmation before final submission.
 *
 * @package EUWithdrawal
 *
 * @var \EUWithdrawal\Domain\Step1_Input $input         Step 1 data.
 * @var string                           $session_token Session token for Step 2.
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $input ) || ! $input instanceof \EUWithdrawal\Domain\Step1_Input ) {
	return;
}

$session_token = $session_token ?? '';
?>
<div class="eu-withdrawal__confirm eu-withdrawal__form--step2">
	<h2 class="eu-withdrawal__heading"><?php esc_html_e( 'Confirm your withdrawal', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></h2>
	<p class="eu-withdrawal__intro">
		<?php esc_html_e( 'Please review your details before submitting the withdrawal request.', EU_WITHDRAWAL_TEXT_DOMAIN ); ?>
	</p>

	<dl class="eu-withdrawal__summary">
		<div class="eu-withdrawal__summary-row">
			<dt><?php esc_html_e( 'Full name', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></dt>
			<dd><?php echo esc_html( $input->name ); ?></dd>
		</div>
		<div class="eu-withdrawal__summary-row">
			<dt><?php esc_html_e( 'Email', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></dt>
			<dd><?php echo esc_html( $input->email ); ?></dd>
		</div>
		<div class="eu-withdrawal__summary-row">
			<dt><?php esc_html_e( 'Order number', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></dt>
			<dd><?php echo esc_html( $input->order_number ); ?></dd>
		</div>
		<?php if ( '' !== $input->phone ) : ?>
		<div class="eu-withdrawal__summary-row">
			<dt><?php esc_html_e( 'Phone', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></dt>
			<dd><?php echo esc_html( $input->phone ); ?></dd>
		</div>
		<?php endif; ?>
		<?php if ( '' !== $input->reason ) : ?>
		<div class="eu-withdrawal__summary-row">
			<dt><?php esc_html_e( 'Reason for withdrawal', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></dt>
			<dd><?php echo esc_html( $input->reason ); ?></dd>
		</div>
		<?php endif; ?>
	</dl>

	<form class="eu-withdrawal__form eu-withdrawal__form--confirm" method="post">
		<input type="hidden" name="session_token" value="<?php echo esc_attr( $session_token ); ?>" />

		<p class="eu-withdrawal__actions">
			<button type="button" class="eu-withdrawal__back button button-secondary">
				<?php esc_html_e( 'Back', EU_WITHDRAWAL_TEXT_DOMAIN ); ?>
			</button>
			<button type="submit" class="eu-withdrawal__confirm button">
				<?php esc_html_e( 'Confirm withdrawal', EU_WITHDRAWAL_TEXT_DOMAIN ); ?>
			</button>
		</p>
	</form>
</div>
