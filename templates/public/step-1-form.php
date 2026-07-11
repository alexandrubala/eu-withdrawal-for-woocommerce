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

defined( 'ABSPATH' ) || exit;

$name         = $name ?? '';
$email        = $email ?? '';
$order_number = $order_number ?? '';
$phone        = $phone ?? '';
$reason       = $reason ?? '';
?>
<form class="eu-withdrawal__form eu-withdrawal__form--step1" method="post" novalidate>
	<h2 class="eu-withdrawal__heading"><?php esc_html_e( 'Withdrawal request', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></h2>
	<p class="eu-withdrawal__intro">
		<?php esc_html_e( 'Enter your order details to begin the EU withdrawal process.', EU_WITHDRAWAL_TEXT_DOMAIN ); ?>
	</p>

	<p class="eu-withdrawal__field">
		<label for="eu-withdrawal-name">
			<?php esc_html_e( 'Full name', EU_WITHDRAWAL_TEXT_DOMAIN ); ?>
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
			<?php esc_html_e( 'Email', EU_WITHDRAWAL_TEXT_DOMAIN ); ?>
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
			<?php esc_html_e( 'Order number', EU_WITHDRAWAL_TEXT_DOMAIN ); ?>
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
			<?php esc_html_e( 'Phone', EU_WITHDRAWAL_TEXT_DOMAIN ); ?>
			<span class="eu-withdrawal__optional"><?php esc_html_e( '(optional)', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></span>
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
			<?php esc_html_e( 'Reason for withdrawal', EU_WITHDRAWAL_TEXT_DOMAIN ); ?>
			<span class="eu-withdrawal__optional"><?php esc_html_e( '(optional)', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></span>
		</label>
		<textarea
			id="eu-withdrawal-reason"
			name="reason"
			rows="4"
		><?php echo esc_textarea( $reason ); ?></textarea>
	</p>

	<p class="eu-withdrawal__actions">
		<button type="submit" class="eu-withdrawal__submit button">
			<?php esc_html_e( 'Continue', EU_WITHDRAWAL_TEXT_DOMAIN ); ?>
		</button>
	</p>
</form>
