<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
/**
 * Customer withdrawal confirmation email (durable medium).
 *
 * @package EUWithdrawal
 *
 * @var string               $request_uuid   Unique request identifier.
 * @var string               $order_number   WooCommerce order number.
 * @var string               $submitted_at   MySQL datetime in site timezone.
 * @var string               $customer_name  Customer full name.
 * @var string               $customer_email Customer email address.
 * @var string               $customer_phone Optional phone number.
 * @var string               $reason         Optional withdrawal reason.
 * @var array<int, array<string, mixed>> $products Order line items.
 */

use EUWithdrawal\Integrations\Legal_String_Catalog;

defined( 'ABSPATH' ) || exit;

$request_uuid   = $request_uuid ?? '';
$order_number   = $order_number ?? '';
$submitted_at   = $submitted_at ?? current_time( 'mysql' );
$customer_name  = $customer_name ?? '';
$customer_email = $customer_email ?? '';
$customer_phone = $customer_phone ?? '';
$reason         = $reason ?? '';
$products       = $products ?? array();

$timestamp = strtotime( $submitted_at );
$date      = $timestamp ? wp_date( get_option( 'date_format' ), $timestamp ) : '';
$time      = $timestamp ? wp_date( get_option( 'time_format' ), $timestamp ) : '';

$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
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
								<?php echo esc_html( Legal_String_Catalog::translate( 'email_confirmation_heading' ) ); ?>
							</h1>
							<p style="margin:0;font-size:15px;line-height:1.6;color:#4b5563;">
								<?php echo esc_html( Legal_String_Catalog::translate( 'email_confirmation_intro' ) ); ?>
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
										<?php echo esc_html( Legal_String_Catalog::translate( 'date' ) ); ?>
									</td>
									<td style="padding:8px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( $date ); ?>
									</td>
								</tr>
								<tr>
									<td style="padding:8px 0;font-size:13px;color:#6b7280;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'time' ) ); ?>
									</td>
									<td style="padding:8px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( $time ); ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="padding:0 32px 24px;">
							<h2 style="margin:0 0 12px;font-size:16px;color:#111827;">
								<?php echo esc_html( Legal_String_Catalog::translate( 'your_details' ) ); ?>
							</h2>
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
								<tr>
									<td style="padding:6px 0;font-size:13px;color:#6b7280;width:40%;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'full_name' ) ); ?>
									</td>
									<td style="padding:6px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( $customer_name ); ?>
									</td>
								</tr>
								<tr>
									<td style="padding:6px 0;font-size:13px;color:#6b7280;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'email' ) ); ?>
									</td>
									<td style="padding:6px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( $customer_email ); ?>
									</td>
								</tr>
								<?php if ( '' !== $customer_phone ) : ?>
								<tr>
									<td style="padding:6px 0;font-size:13px;color:#6b7280;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'phone' ) ); ?>
									</td>
									<td style="padding:6px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( $customer_phone ); ?>
									</td>
								</tr>
								<?php endif; ?>
								<?php if ( '' !== $reason ) : ?>
								<tr>
									<td style="padding:6px 0;font-size:13px;color:#6b7280;vertical-align:top;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'reason_for_withdrawal' ) ); ?>
									</td>
									<td style="padding:6px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( $reason ); ?>
									</td>
								</tr>
								<?php endif; ?>
							</table>
						</td>
					</tr>
					<?php if ( ! empty( $products ) ) : ?>
					<tr>
						<td style="padding:0 32px 24px;">
							<h2 style="margin:0 0 12px;font-size:16px;color:#111827;">
								<?php echo esc_html( Legal_String_Catalog::translate( 'products' ) ); ?>
							</h2>
							<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
								<?php foreach ( $products as $product ) : ?>
								<tr>
									<td style="padding:8px 0;border-bottom:1px solid #f3f4f6;font-size:14px;color:#111827;">
										<?php
										echo esc_html(
											sprintf(
												/* translators: 1: product name, 2: quantity */
												__( '%1$s × %2$d', 'eu-withdrawal-for-woocommerce' ),
												(string) ( $product['name'] ?? '' ),
												(int) ( $product['quantity'] ?? 0 )
											)
										);
										?>
									</td>
								</tr>
								<?php endforeach; ?>
							</table>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<td style="padding:16px 32px 32px;border-top:1px solid #e5e7eb;">
							<p style="margin:0;font-size:14px;line-height:1.6;color:#374151;">
								<?php echo esc_html( Legal_String_Catalog::translate( 'email_legal_disclaimer' ) ); ?>
							</p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
