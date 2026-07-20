<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
/**
 * Step 1 – order lookup (guest) or eligible order dropdown (logged-in).
 *
 * @package EUWithdrawal
 *
 * @var string                          $name            Customer name field value.
 * @var string                          $email           Email field value.
 * @var string                          $order_number    Order number field value.
 * @var string                          $phone           Phone field value.
 * @var array<int, \WC_Order>           $eligible_orders Eligible orders for logged-in user.
 * @var bool                            $is_logged_in    Whether the customer is logged in.
 */

use EUWithdrawal\Integrations\Legal_String_Catalog;
use EUWithdrawal\Services\Settings;

defined( 'ABSPATH' ) || exit;

$name            = $name ?? '';
$email           = $email ?? '';
$order_number    = $order_number ?? '';
$phone           = $phone ?? '';
$eligible_orders = $eligible_orders ?? array();
$is_logged_in    = $is_logged_in ?? is_user_logged_in();
$return_days     = Settings::return_days();
?>
<form class="eu-withdrawal__form eu-withdrawal__form--step1" method="post" novalidate>
	<h2 class="eu-withdrawal__heading"><?php echo esc_html( Legal_String_Catalog::translate( 'withdrawal_request_heading' ) ); ?></h2>
	<p class="eu-withdrawal__intro">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %d: number of days */
				Legal_String_Catalog::translate( 'withdrawal_intro_days' ),
				$return_days
			)
		);
		?>
	</p>

	<?php if ( $is_logged_in && ! empty( $eligible_orders ) ) : ?>
		<p class="eu-withdrawal__field">
			<label for="eu-withdrawal-order-id">
				<?php echo esc_html( Legal_String_Catalog::translate( 'select_order' ) ); ?>
				<span class="required" aria-hidden="true">*</span>
			</label>
			<select id="eu-withdrawal-order-id" name="order_id" required>
				<option value=""><?php echo esc_html( Legal_String_Catalog::translate( 'select_order_placeholder' ) ); ?></option>
				<?php foreach ( $eligible_orders as $order ) : ?>
					<?php if ( ! $order instanceof \WC_Order ) { continue; } ?>
					<option value="<?php echo esc_attr( (string) $order->get_id() ); ?>">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: order number, 2: formatted date, 3: formatted total */
								__( 'Order #%1$s — %2$s — %3$s', 'eu-withdrawal-for-woocommerce' ),
								$order->get_order_number(),
								wc_format_datetime( $order->get_date_created() ),
								wp_strip_all_tags( $order->get_formatted_order_total() )
							)
						);
						?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<input type="hidden" name="name" value="<?php echo esc_attr( $name ); ?>" />
		<input type="hidden" name="email" value="<?php echo esc_attr( $email ); ?>" />
		<input type="hidden" name="phone" value="<?php echo esc_attr( $phone ); ?>" />
	<?php else : ?>
		<?php if ( $is_logged_in && empty( $eligible_orders ) ) : ?>
			<p class="eu-withdrawal__note">
				<?php echo esc_html( Legal_String_Catalog::translate( 'no_eligible_orders' ) ); ?>
			</p>
			<p class="eu-withdrawal__note">
				<?php echo esc_html( Legal_String_Catalog::translate( 'guest_lookup_fallback' ) ); ?>
			</p>
		<?php endif; ?>

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
	<?php endif; ?>

	<p class="eu-withdrawal__actions">
		<button type="submit" class="eu-withdrawal__submit button">
			<?php echo esc_html( Legal_String_Catalog::translate( 'continue' ) ); ?>
		</button>
	</p>
</form>
