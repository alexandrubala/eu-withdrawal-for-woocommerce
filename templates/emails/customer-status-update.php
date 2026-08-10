<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
/**
 * Customer email when a withdrawal request is processed or refunded.
 *
 * @package EUWithdrawal
 *
 * @var string $request_uuid
 * @var string $order_number
 * @var string $customer_name
 * @var string $status processed|refunded
 */

use EUWithdrawal\Domain\Withdrawal_Status;
use EUWithdrawal\Integrations\Legal_String_Catalog;

defined( 'ABSPATH' ) || exit;

$request_uuid  = $request_uuid ?? '';
$order_number  = $order_number ?? '';
$customer_name = $customer_name ?? '';
$status        = $status ?? '';

$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

if ( Withdrawal_Status::REFUNDED === $status ) {
	$heading = __( 'Your refund has been completed', 'eu-withdrawal-for-woocommerce' );
	$intro   = __( 'Good news — your return / withdrawal request has been refunded. The money should appear according to your bank or card provider timelines.', 'eu-withdrawal-for-woocommerce' );
} else {
	$heading = __( 'Your request is being processed', 'eu-withdrawal-for-woocommerce' );
	$intro   = __( 'We have started processing your return / withdrawal request. We will update you again when the refund is completed.', 'eu-withdrawal-for-woocommerce' );
}
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( determine_locale() ); ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $site_name ); ?></title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
	<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f4f4f5;padding:24px 0;">
		<tr>
			<td align="center">
				<table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;background-color:#ffffff;border:1px solid #e5e7eb;border-radius:8px;">
					<tr>
						<td style="padding:32px 32px 16px;">
							<h1 style="margin:0 0 8px;font-size:22px;line-height:1.3;color:#111827;">
								<?php echo esc_html( $heading ); ?>
							</h1>
							<p style="margin:0;font-size:15px;line-height:1.6;color:#4b5563;">
								<?php
								if ( '' !== $customer_name ) {
									printf(
										/* translators: %s: customer name */
										esc_html__( 'Hello %s,', 'eu-withdrawal-for-woocommerce' ),
										esc_html( $customer_name )
									);
								}
								?>
							</p>
							<p style="margin:12px 0 0;font-size:15px;line-height:1.6;color:#4b5563;">
								<?php echo esc_html( $intro ); ?>
							</p>
						</td>
					</tr>
					<tr>
						<td style="padding:0 32px 24px;">
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-top:1px solid #e5e7eb;">
								<tr>
									<td style="padding:16px 0 8px;font-size:13px;color:#6b7280;width:40%;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'request_number' ) ); ?>
									</td>
									<td style="padding:16px 0 8px;font-size:14px;color:#111827;font-family:monospace;">
										<?php echo esc_html( $request_uuid ); ?>
									</td>
								</tr>
								<tr>
									<td style="padding:8px 0;font-size:13px;color:#6b7280;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'order_number' ) ); ?>
									</td>
									<td style="padding:8px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( $order_number ); ?>
									</td>
								</tr>
								<tr>
									<td style="padding:8px 0;font-size:13px;color:#6b7280;">
										<?php echo esc_html__( 'Status', 'eu-withdrawal-for-woocommerce' ); ?>
									</td>
									<td style="padding:8px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( Withdrawal_Status::label( $status ) ); ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="padding:16px 32px 32px;border-top:1px solid #e5e7eb;">
							<p style="margin:0;font-size:14px;line-height:1.6;color:#374151;">
								<?php echo esc_html__( 'If you have questions, reply to this email or contact the store.', 'eu-withdrawal-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
