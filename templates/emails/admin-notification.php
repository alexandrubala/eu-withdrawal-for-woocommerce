<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
/**
 * Shop admin notification when a withdrawal request is submitted.
 *
 * @package EUWithdrawal
 *
 * @var string                             $request_uuid
 * @var string                             $order_number
 * @var string                             $submitted_at
 * @var string                             $customer_name
 * @var string                             $customer_email
 * @var string                             $customer_phone
 * @var string                             $reason
 * @var string                             $reason_label
 * @var string                             $product_opened
 * @var array<int, array<string, mixed>>   $photos
 * @var string                             $request_type
 * @var string                             $refund_iban
 * @var string                             $refund_account_name
 * @var string                             $admin_url
 * @var array<int, array<string, mixed>>   $products
 */

use EUWithdrawal\Domain\Request_Type;
use EUWithdrawal\Integrations\Legal_String_Catalog;

defined( 'ABSPATH' ) || exit;

$request_uuid         = $request_uuid ?? '';
$order_number         = $order_number ?? '';
$submitted_at         = $submitted_at ?? current_time( 'mysql' );
$customer_name        = $customer_name ?? '';
$customer_email       = $customer_email ?? '';
$customer_phone       = $customer_phone ?? '';
$reason               = $reason ?? '';
$reason_label         = $reason_label ?? '';
$product_opened       = $product_opened ?? '';
$photos               = $photos ?? array();
$request_type         = $request_type ?? Request_Type::REFUND;
$refund_iban          = $refund_iban ?? '';
$refund_account_name  = $refund_account_name ?? '';
$admin_url            = $admin_url ?? '';
$products             = $products ?? array();

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
								<?php echo esc_html__( 'New return / withdrawal request', 'eu-withdrawal-for-woocommerce' ); ?>
							</h1>
							<p style="margin:0;font-size:15px;line-height:1.6;color:#4b5563;">
								<?php echo esc_html__( 'A customer submitted a return / withdrawal request on your store.', 'eu-withdrawal-for-woocommerce' ); ?>
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
										<?php echo esc_html( Legal_String_Catalog::translate( 'request_type_legend' ) ); ?>
									</td>
									<td style="padding:8px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( Request_Type::label( $request_type ) ); ?>
									</td>
								</tr>
								<tr>
									<td style="padding:8px 0;font-size:13px;color:#6b7280;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'date' ) ); ?>
									</td>
									<td style="padding:8px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( $date . ' ' . $time ); ?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td style="padding:0 32px 24px;">
							<h2 style="margin:0 0 12px;font-size:16px;color:#111827;">
								<?php echo esc_html__( 'Customer details', 'eu-withdrawal-for-woocommerce' ); ?>
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
								<?php if ( '' !== $refund_iban ) : ?>
								<tr>
									<td style="padding:6px 0;font-size:13px;color:#6b7280;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'iban' ) ); ?>
									</td>
									<td style="padding:6px 0;font-size:14px;color:#111827;font-family:monospace;">
										<?php echo esc_html( $refund_iban ); ?>
									</td>
								</tr>
								<?php endif; ?>
								<?php if ( '' !== $refund_account_name ) : ?>
								<tr>
									<td style="padding:6px 0;font-size:13px;color:#6b7280;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'account_holder' ) ); ?>
									</td>
									<td style="padding:6px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( $refund_account_name ); ?>
									</td>
								</tr>
								<?php endif; ?>
								<?php if ( '' !== $reason_label ) : ?>
								<tr>
									<td style="padding:6px 0;font-size:13px;color:#6b7280;vertical-align:top;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'return_reason' ) ); ?>
									</td>
									<td style="padding:6px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( $reason_label ); ?>
									</td>
								</tr>
								<?php endif; ?>
								<?php if ( '' !== $product_opened ) : ?>
								<tr>
									<td style="padding:6px 0;font-size:13px;color:#6b7280;vertical-align:top;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'product_opened' ) ); ?>
									</td>
									<td style="padding:6px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( Legal_String_Catalog::product_opened_label( $product_opened ) ); ?>
									</td>
								</tr>
								<?php endif; ?>
								<?php if ( '' !== $reason ) : ?>
								<tr>
									<td style="padding:6px 0;font-size:13px;color:#6b7280;vertical-align:top;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'additional_details' ) ); ?>
									</td>
									<td style="padding:6px 0;font-size:14px;color:#111827;">
										<?php echo esc_html( $reason ); ?>
									</td>
								</tr>
								<?php endif; ?>
								<?php if ( ! empty( $photos ) ) : ?>
								<tr>
									<td style="padding:6px 0;font-size:13px;color:#6b7280;vertical-align:top;">
										<?php echo esc_html( Legal_String_Catalog::translate( 'reason_photos' ) ); ?>
									</td>
									<td style="padding:6px 0;font-size:14px;color:#111827;">
										<?php foreach ( $photos as $photo ) : ?>
											<a href="<?php echo esc_url( (string) ( $photo['url'] ?? '' ) ); ?>"><img src="<?php echo esc_url( (string) ( $photo['thumb'] ?? '' ) ); ?>" alt="<?php echo esc_attr( (string) ( $photo['name'] ?? '' ) ); ?>" width="72" height="72" style="display:inline-block;margin:0 6px 6px 0;border-radius:4px;" /></a>
										<?php endforeach; ?>
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
					<?php if ( '' !== $admin_url ) : ?>
					<tr>
						<td style="padding:16px 32px 32px;border-top:1px solid #e5e7eb;">
							<p style="margin:0;">
								<a href="<?php echo esc_url( $admin_url ); ?>" style="display:inline-block;padding:10px 16px;background:#2271b1;color:#ffffff;text-decoration:none;border-radius:4px;font-size:14px;">
									<?php echo esc_html__( 'View request in admin', 'eu-withdrawal-for-woocommerce' ); ?>
								</a>
							</p>
						</td>
					</tr>
					<?php endif; ?>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
