<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
/**
 * Details step – customer summary, products, reason, photos, shipping, IBAN.
 *
 * @package EUWithdrawal
 *
 * @var \EUWithdrawal\Domain\Step1_Input $input
 * @var string                           $session_token
 * @var \WC_Order                        $order
 * @var array<int, array<string, mixed>> $products
 * @var string                           $require_iban
 * @var string                           $courier_text
 * @var string                           $refund_note
 * @var int                              $days_remaining
 * @var bool                             $show_phone_field
 */

use EUWithdrawal\Domain\Request_Type;
use EUWithdrawal\Integrations\Legal_String_Catalog;
use EUWithdrawal\Services\Settings;

defined( 'ABSPATH' ) || exit;

if ( ! isset( $input ) || ! $input instanceof \EUWithdrawal\Domain\Step1_Input ) {
	return;
}

$session_token    = $session_token ?? '';
$products         = $products ?? array();
$require_iban     = $require_iban ?? '1';
$courier_text     = $courier_text ?? '';
$refund_note      = $refund_note ?? '';
$days_remaining   = $days_remaining ?? 0;
$show_phone_field = ! empty( $show_phone_field );
$order_date       = isset( $order ) && $order instanceof \WC_Order ? wc_format_datetime( $order->get_date_created() ) : '';
$max_photos       = Settings::max_photos();
$optional_label   = Legal_String_Catalog::translate( 'optional' );
?>
<form class="eu-withdrawal__form eu-withdrawal__form--details" method="post" novalidate>
	<p class="eu-withdrawal__intro">
		<?php echo esc_html( Legal_String_Catalog::format( 'details_intro', $input->order_number, $days_remaining ) ); ?>
	</p>

	<input type="hidden" name="session_token" value="<?php echo esc_attr( $session_token ); ?>" />
	<input type="hidden" name="request_type" value="<?php echo esc_attr( Request_Type::REFUND ); ?>" />

	<?php if ( Settings::field_enabled( 'summary' ) ) : ?>
		<h3 class="eu-withdrawal__subheading"><?php echo esc_html( Legal_String_Catalog::translate( 'your_details' ) ); ?></h3>
		<dl class="eu-withdrawal__summary">
			<div class="eu-withdrawal__summary-row">
				<dt><?php echo esc_html( Legal_String_Catalog::translate( 'full_name' ) ); ?></dt>
				<dd><?php echo esc_html( $input->name ); ?></dd>
			</div>
			<div class="eu-withdrawal__summary-row">
				<dt><?php echo esc_html( Legal_String_Catalog::translate( 'email' ) ); ?></dt>
				<dd><?php echo esc_html( $input->email ); ?></dd>
			</div>
			<?php if ( '' !== $input->phone && ! $show_phone_field ) : ?>
				<div class="eu-withdrawal__summary-row">
					<dt><?php echo esc_html( Legal_String_Catalog::translate( 'phone' ) ); ?></dt>
					<dd><?php echo esc_html( $input->phone ); ?></dd>
				</div>
			<?php endif; ?>
			<div class="eu-withdrawal__summary-row">
				<dt><?php echo esc_html( Legal_String_Catalog::translate( 'order_number' ) ); ?></dt>
				<dd><?php echo esc_html( $input->order_number ); ?></dd>
			</div>
			<?php if ( '' !== $order_date ) : ?>
				<div class="eu-withdrawal__summary-row">
					<dt><?php echo esc_html( Legal_String_Catalog::translate( 'order_date' ) ); ?></dt>
					<dd><?php echo esc_html( $order_date ); ?></dd>
				</div>
			<?php endif; ?>
		</dl>
	<?php endif; ?>

	<?php if ( $show_phone_field ) : ?>
		<p class="eu-withdrawal__field">
			<label for="eu-withdrawal-details-phone">
				<?php echo esc_html( Legal_String_Catalog::translate( 'phone' ) ); ?>
				<span class="eu-withdrawal__optional"><?php echo esc_html( $optional_label ); ?></span>
			</label>
			<input
				type="tel"
				id="eu-withdrawal-details-phone"
				name="phone"
				value="<?php echo esc_attr( $input->phone ); ?>"
				maxlength="50"
				autocomplete="tel"
			/>
		</p>
	<?php endif; ?>

	<?php if ( Settings::field_enabled( 'request_box' ) ) : ?>
		<h2 class="eu-withdrawal__heading"><?php echo esc_html( Legal_String_Catalog::translate( 'details_heading' ) ); ?></h2>
		<div class="eu-withdrawal__fieldset eu-withdrawal__fieldset--single">
			<p class="eu-withdrawal__choice eu-withdrawal__choice--selected">
				<span class="eu-withdrawal__choice-title"><?php echo esc_html( Request_Type::label( Request_Type::REFUND ) ); ?></span>
				<span class="eu-withdrawal__choice-desc"><?php echo esc_html( Legal_String_Catalog::translate( 'refund_choice_desc' ) ); ?></span>
			</p>
		</div>
	<?php endif; ?>

	<div class="eu-withdrawal__products">
		<h3 class="eu-withdrawal__subheading"><?php echo esc_html( Legal_String_Catalog::translate( 'select_products' ) ); ?></h3>
		<?php if ( empty( $products ) ) : ?>
			<p><?php echo esc_html( Legal_String_Catalog::translate( 'no_products' ) ); ?></p>
		<?php else : ?>
			<ul class="eu-withdrawal__product-list">
				<?php foreach ( $products as $product ) : ?>
					<?php
					$item_id = (string) ( $product['item_id'] ?? '' );
					$max_qty = max( 1, (int) ( $product['quantity'] ?? 1 ) );
					?>
					<li class="eu-withdrawal__product-card">
						<label class="eu-withdrawal__product-select">
							<input
								type="checkbox"
								name="product_items[]"
								value="<?php echo esc_attr( $item_id ); ?>"
								checked
							/>
							<span class="eu-withdrawal__product-body">
								<?php if ( ! empty( $product['image'] ) ) : ?>
									<img
										class="eu-withdrawal__product-image"
										src="<?php echo esc_url( (string) $product['image'] ); ?>"
										alt=""
										width="64"
										height="64"
										loading="lazy"
									/>
								<?php else : ?>
									<span class="eu-withdrawal__product-image eu-withdrawal__product-image--placeholder" aria-hidden="true"></span>
								<?php endif; ?>
								<span class="eu-withdrawal__product-meta">
									<span class="eu-withdrawal__product-name"><?php echo esc_html( (string) ( $product['name'] ?? '' ) ); ?></span>
									<?php if ( ! empty( $product['attributes'] ) && is_array( $product['attributes'] ) ) : ?>
										<span class="eu-withdrawal__product-attrs">
											<?php
											$attr_parts = array();
											foreach ( $product['attributes'] as $label => $value ) {
												$attr_parts[] = esc_html( $label ) . ': ' . esc_html( (string) $value );
											}
											echo implode( ' · ', $attr_parts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
											?>
										</span>
									<?php endif; ?>
									<span class="eu-withdrawal__product-price">
										<?php echo wp_kses_post( (string) ( $product['price_html'] ?? '' ) ); ?>
										<?php if ( ! empty( $product['sku'] ) ) : ?>
											<span class="eu-withdrawal__product-sku">SKU: <?php echo esc_html( (string) $product['sku'] ); ?></span>
										<?php endif; ?>
									</span>
								</span>
							</span>
						</label>
						<label class="eu-withdrawal__product-qty">
							<span><?php echo esc_html( Legal_String_Catalog::translate( 'quantity' ) ); ?></span>
							<input
								type="number"
								name="product_qty[<?php echo esc_attr( $item_id ); ?>]"
								min="1"
								max="<?php echo esc_attr( (string) $max_qty ); ?>"
								value="<?php echo esc_attr( (string) $max_qty ); ?>"
							/>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<?php if ( Settings::field_enabled( 'reason' ) ) : ?>
		<p class="eu-withdrawal__field">
			<label for="eu-withdrawal-return-reason">
				<?php echo esc_html( Legal_String_Catalog::translate( 'return_reason' ) ); ?>
				<span class="eu-withdrawal__optional"><?php echo esc_html( $optional_label ); ?></span>
			</label>
			<select id="eu-withdrawal-return-reason" name="reason_label">
				<option value=""><?php echo esc_html( Legal_String_Catalog::translate( 'return_reason_placeholder' ) ); ?></option>
				<?php foreach ( Settings::reason_options() as $reason_option ) : ?>
					<option value="<?php echo esc_attr( $reason_option ); ?>"><?php echo esc_html( $reason_option ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
	<?php endif; ?>

	<?php if ( Settings::field_enabled( 'product_opened' ) ) : ?>
		<fieldset class="eu-withdrawal__fieldset">
			<legend>
				<?php echo esc_html( Legal_String_Catalog::translate( 'product_opened' ) ); ?>
				<span class="eu-withdrawal__optional"><?php echo esc_html( $optional_label ); ?></span>
			</legend>
			<label class="eu-withdrawal__choice">
				<input type="radio" name="product_opened" value="yes" />
				<span class="eu-withdrawal__choice-title"><?php echo esc_html( Legal_String_Catalog::translate( 'product_opened_yes' ) ); ?></span>
			</label>
			<label class="eu-withdrawal__choice">
				<input type="radio" name="product_opened" value="no" />
				<span class="eu-withdrawal__choice-title"><?php echo esc_html( Legal_String_Catalog::translate( 'product_opened_no' ) ); ?></span>
			</label>
		</fieldset>
	<?php endif; ?>

	<?php if ( Settings::field_enabled( 'details' ) ) : ?>
		<p class="eu-withdrawal__field">
			<label for="eu-withdrawal-reason">
				<?php echo esc_html( Legal_String_Catalog::translate( 'additional_details' ) ); ?>
				<span class="eu-withdrawal__optional"><?php echo esc_html( $optional_label ); ?></span>
			</label>
			<textarea id="eu-withdrawal-reason" name="reason" rows="3"><?php echo esc_textarea( $input->reason ); ?></textarea>
		</p>
	<?php endif; ?>

	<?php if ( Settings::field_enabled( 'photos' ) ) : ?>
		<div class="eu-withdrawal__field eu-withdrawal__photos">
			<label for="eu-withdrawal-photos">
				<?php echo esc_html( Legal_String_Catalog::translate( 'reason_photos' ) ); ?>
				<span class="eu-withdrawal__optional"><?php echo esc_html( $optional_label ); ?></span>
			</label>
			<p class="eu-withdrawal__hint">
				<?php echo esc_html( Legal_String_Catalog::format( 'reason_photos_hint', $max_photos ) ); ?>
			</p>
			<input
				type="file"
				id="eu-withdrawal-photos"
				name="reason_photos[]"
				accept="image/jpeg,image/png,image/gif,image/webp"
				data-max-photos="<?php echo esc_attr( (string) $max_photos ); ?>"
				data-too-many-photos="<?php echo esc_attr( Legal_String_Catalog::format( 'photos_limit_error', $max_photos ) ); ?>"
				<?php echo $max_photos > 1 ? 'multiple' : ''; ?>
			/>
			<ul class="eu-withdrawal__photo-preview" hidden></ul>
			<?php if ( ! empty( $input->attachments ) ) : ?>
				<p class="eu-withdrawal__note eu-withdrawal__photos-kept">
					<?php echo esc_html( Legal_String_Catalog::format( 'reason_photos_kept', count( $input->attachments ) ) ); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="eu-withdrawal__courier-panel">
		<h3 class="eu-withdrawal__subheading"><?php echo esc_html( Legal_String_Catalog::translate( 'courier_heading' ) ); ?></h3>
		<?php if ( '' !== trim( $courier_text ) ) : ?>
			<div class="eu-withdrawal__courier-box">
				<?php echo nl2br( esc_html( $courier_text ) ); ?>
			</div>
		<?php else : ?>
			<p class="eu-withdrawal__note"><?php echo esc_html( Legal_String_Catalog::translate( 'courier_fallback' ) ); ?></p>
		<?php endif; ?>
	</div>

	<div class="eu-withdrawal__iban-panel" data-require-iban="<?php echo esc_attr( $require_iban ); ?>">
		<h3 class="eu-withdrawal__subheading"><?php echo esc_html( Legal_String_Catalog::translate( 'refund_account_heading' ) ); ?></h3>
		<?php if ( '' !== $refund_note ) : ?>
			<p class="eu-withdrawal__note"><?php echo esc_html( $refund_note ); ?></p>
		<?php endif; ?>
		<?php if ( '1' === $require_iban ) : ?>
			<p class="eu-withdrawal__field">
				<label for="eu-withdrawal-iban">
					<?php echo esc_html( Legal_String_Catalog::translate( 'iban' ) ); ?>
					<span class="required" aria-hidden="true">*</span>
				</label>
				<input type="text" id="eu-withdrawal-iban" name="refund_iban" autocomplete="off" />
			</p>
			<p class="eu-withdrawal__field">
				<label for="eu-withdrawal-account-name">
					<?php echo esc_html( Legal_String_Catalog::translate( 'account_holder' ) ); ?>
					<span class="required" aria-hidden="true">*</span>
				</label>
				<input type="text" id="eu-withdrawal-account-name" name="refund_account_name" autocomplete="name" />
			</p>
		<?php endif; ?>
	</div>

	<p class="eu-withdrawal__actions">
		<button type="button" class="eu-withdrawal__back button button-secondary">
			<?php echo esc_html( Legal_String_Catalog::translate( 'back' ) ); ?>
		</button>
		<button type="submit" class="eu-withdrawal__submit button">
			<?php echo esc_html( Legal_String_Catalog::translate( 'continue' ) ); ?>
		</button>
	</p>
</form>
