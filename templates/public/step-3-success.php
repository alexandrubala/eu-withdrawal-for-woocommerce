<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
/**
 * Step 3 – success message after submission.
 *
 * @package EUWithdrawal
 *
 * @var string $request_uuid Unique request identifier.
 * @var string $submitted_at MySQL datetime string in site timezone.
 */

use EUWithdrawal\Integrations\Legal_String_Catalog;

defined( 'ABSPATH' ) || exit;

$request_uuid = $request_uuid ?? '';
$submitted_at = $submitted_at ?? current_time( 'mysql' );

$timestamp = strtotime( $submitted_at );
$date      = $timestamp ? wp_date( get_option( 'date_format' ), $timestamp ) : '';
$time      = $timestamp ? wp_date( get_option( 'time_format' ), $timestamp ) : '';
?>
<div class="eu-withdrawal__success">
	<h2 class="eu-withdrawal__heading"><?php echo esc_html( Legal_String_Catalog::translate( 'success_heading' ) ); ?></h2>
	<p class="eu-withdrawal__intro">
		<?php echo esc_html( Legal_String_Catalog::translate( 'success_intro' ) ); ?>
	</p>

	<dl class="eu-withdrawal__receipt">
		<div class="eu-withdrawal__receipt-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'date' ) ); ?></dt>
			<dd><?php echo esc_html( $date ); ?></dd>
		</div>
		<div class="eu-withdrawal__receipt-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'time' ) ); ?></dt>
			<dd><?php echo esc_html( $time ); ?></dd>
		</div>
		<div class="eu-withdrawal__receipt-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'request_number' ) ); ?></dt>
			<dd><code class="eu-withdrawal__uuid"><?php echo esc_html( $request_uuid ); ?></code></dd>
		</div>
	</dl>
</div>
