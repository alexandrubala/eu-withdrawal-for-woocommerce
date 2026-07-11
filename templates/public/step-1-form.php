<?php
/**
 * Step 1 – withdrawal request form.
 *
 * @package EUWithdrawal
 *
 * @var string $name         Customer name field value.
 * @var string $email        Email field value.
 * @var string $order_number Order number field value.
 * @var string $phone        Phone field value.
 * @var string $reason       Reason field value.
 */

use EUWithdrawal\Integrations\Legal_String_Catalog;

defined( 'ABSPATH' ) || exit;

$name         = $name ?? '';
$email        = $email ?? '';
$order_number = $order_number ?? '';
$phone        = $phone ?? '';
$reason       = $reason ?? '';
?>
<form class="eu-withdrawal__form eu-withdrawal__form--step1" method="post" novalidate>
	<h2 class="eu-withdrawal__heading"><?php echo esc_html( Legal_String_Catalog::translate( 'withdrawal_request_heading' ) ); ?></h2>
	<p class="eu-withdrawal__intro">
		<?php echo esc_html( Legal_String_Catalog::translate( 'withdrawal_intro' ) ); ?>
	</p>

	<p class="eu-withdrawal__field">
		<label for="eu-withdrawal-name">
			<?php echo esc_html( Legal_String_Catalog::translate( 'full_name' ) ); ?>
			<span class="required" aria-hidden="true">*</span>
		</label>
		<input
			type="text"
			id="eu-withdrawal-name"
			name="name"
			value="<?php echo esc_attr( $name ); ?>"
			required
			autocomplete="name"
		/>
	</p>

	<p class="eu-withdrawal__field">
		<label for="eu-withdrawal-email">
			<?php echo esc_html( Legal_String_Catalog::translate( 'email' ) ); ?>
			<span class="required" aria-hidden="true">*</span>
		</label>
		<input
			type="email"
			id="eu-withdrawal-email"
			name="email"
			value="<?php echo esc_attr( $email ); ?>"
			required
			autocomplete="email"
		/>
	</p>

	<p class="eu-withdrawal__field">
		<label for="eu-withdrawal-order-number">
			<?php echo esc_html( Legal_String_Catalog::translate( 'order_number' ) ); ?>
			<span class="required" aria-hidden="true">*</span>
		</label>
		<input
			type="text"
			id="eu-withdrawal-order-number"
			name="order_number"
			value="<?php echo esc_attr( $order_number ); ?>"
			required
			autocomplete="off"
		/>
	</p>

	<p class="eu-withdrawal__field">
		<label for="eu-withdrawal-phone">
			<?php echo esc_html( Legal_String_Catalog::translate( 'phone' ) ); ?>
			<span class="eu-withdrawal__optional"><?php echo esc_html( Legal_String_Catalog::translate( 'optional' ) ); ?></span>
		</label>
		<input
			type="tel"
			id="eu-withdrawal-phone"
			name="phone"
			value="<?php echo esc_attr( $phone ); ?>"
			autocomplete="tel"
		/>
	</p>

	<p class="eu-withdrawal__field">
		<label for="eu-withdrawal-reason">
			<?php echo esc_html( Legal_String_Catalog::translate( 'reason_for_withdrawal' ) ); ?>
			<span class="eu-withdrawal__optional"><?php echo esc_html( Legal_String_Catalog::translate( 'optional' ) ); ?></span>
		</label>
		<textarea
			id="eu-withdrawal-reason"
			name="reason"
			rows="4"
		><?php echo esc_textarea( $reason ); ?></textarea>
	</p>

	<p class="eu-withdrawal__actions">
		<button type="submit" class="eu-withdrawal__submit button">
			<?php echo esc_html( Legal_String_Catalog::translate( 'continue' ) ); ?>
		</button>
	</p>
</form>
