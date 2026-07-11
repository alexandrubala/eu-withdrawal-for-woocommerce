<?php
/**
 * Step 2 – read-only confirmation before final submission.
 *
 * @package EUWithdrawal
 *
 * @var \EUWithdrawal\Domain\Step1_Input $input         Step 1 data.
 * @var string                           $session_token Session token for Step 2.
 */

use EUWithdrawal\Integrations\Legal_String_Catalog;

defined( 'ABSPATH' ) || exit;

if ( ! isset( $input ) || ! $input instanceof \EUWithdrawal\Domain\Step1_Input ) {
	return;
}

$session_token = $session_token ?? '';
?>
<div class="eu-withdrawal__confirm eu-withdrawal__form--step2">
	<h2 class="eu-withdrawal__heading"><?php echo esc_html( Legal_String_Catalog::translate( 'confirm_heading' ) ); ?></h2>
	<p class="eu-withdrawal__intro">
		<?php echo esc_html( Legal_String_Catalog::translate( 'confirm_intro' ) ); ?>
	</p>

	<dl class="eu-withdrawal__summary">
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'full_name' ) ); ?></dt>
			<dd><?php echo esc_html( $input->name ); ?></dd>
		</div>
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'email' ) ); ?></dt>
			<dd><?php echo esc_html( $input->email ); ?></dd>
		</div>
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'order_number' ) ); ?></dt>
			<dd><?php echo esc_html( $input->order_number ); ?></dd>
		</div>
		<?php if ( '' !== $input->phone ) : ?>
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'phone' ) ); ?></dt>
			<dd><?php echo esc_html( $input->phone ); ?></dd>
		</div>
		<?php endif; ?>
		<?php if ( '' !== $input->reason ) : ?>
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'reason_for_withdrawal' ) ); ?></dt>
			<dd><?php echo esc_html( $input->reason ); ?></dd>
		</div>
		<?php endif; ?>
	</dl>

	<form class="eu-withdrawal__form eu-withdrawal__form--confirm" method="post">
		<input type="hidden" name="session_token" value="<?php echo esc_attr( $session_token ); ?>" />

		<p class="eu-withdrawal__actions">
			<button type="button" class="eu-withdrawal__back button button-secondary">
				<?php echo esc_html( Legal_String_Catalog::translate( 'back' ) ); ?>
			</button>
			<button type="submit" class="eu-withdrawal__confirm button">
				<?php echo esc_html( Legal_String_Catalog::translate( 'confirm_withdrawal' ) ); ?>
			</button>
		</p>
	</form>
</div>
