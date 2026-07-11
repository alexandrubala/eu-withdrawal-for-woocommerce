<?php
/**
 * Step 3 – success message after submission.
 *
 * @package EUWithdrawal
 *
 * @var string $request_uuid Unique request identifier.
 * @var string $submitted_at MySQL datetime string in site timezone.
 */

defined( 'ABSPATH' ) || exit;

$request_uuid = $request_uuid ?? '';
$submitted_at = $submitted_at ?? current_time( 'mysql' );

$timestamp = strtotime( $submitted_at );
$date      = $timestamp ? wp_date( get_option( 'date_format' ), $timestamp ) : '';
$time      = $timestamp ? wp_date( get_option( 'time_format' ), $timestamp ) : '';
?>
<div class="eu-withdrawal__success">
	<h2 class="eu-withdrawal__heading"><?php esc_html_e( 'Withdrawal request received', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></h2>
	<p class="eu-withdrawal__intro">
		<?php esc_html_e( 'Your withdrawal request has been submitted successfully.', EU_WITHDRAWAL_TEXT_DOMAIN ); ?>
	</p>

	<dl class="eu-withdrawal__receipt">
		<div class="eu-withdrawal__receipt-row">
			<dt><?php esc_html_e( 'Date', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></dt>
			<dd><?php echo esc_html( $date ); ?></dd>
		</div>
		<div class="eu-withdrawal__receipt-row">
			<dt><?php esc_html_e( 'Time', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></dt>
			<dd><?php echo esc_html( $time ); ?></dd>
		</div>
		<div class="eu-withdrawal__receipt-row">
			<dt><?php esc_html_e( 'Request number', EU_WITHDRAWAL_TEXT_DOMAIN ); ?></dt>
			<dd><code class="eu-withdrawal__uuid"><?php echo esc_html( $request_uuid ); ?></code></dd>
		</div>
	</dl>
</div>
