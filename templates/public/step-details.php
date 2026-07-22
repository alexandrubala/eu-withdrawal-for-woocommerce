<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
/**
 * Details step – request type, products, IBAN / courier.
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
 */

use EUWithdrawal\Domain\Request_Type;
use EUWithdrawal\Integrations\Legal_String_Catalog;

defined( 'ABSPATH' ) || exit;

if ( ! isset( $input ) || ! $input instanceof \EUWithdrawal\Domain\Step1_Input ) {
	return;
}

$session_token  = $session_token ?? '';
$products       = $products ?? array();
$require_iban   = $require_iban ?? '1';
$courier_text   = $courier_text ?? '';
$refund_note    = $refund_note ?? '';
$days_remaining = $days_remaining ?? 0;
?>
<form class="eu-withdrawal__form eu-withdrawal__form--details" method="post" novalidate>
	<h2 class="eu-withdrawal__heading"><?php echo esc_html( Legal_String_Catalog::translate( 'details_heading' ) ); ?></h2>
	<p class="eu-withdrawal__intro">
		<?php
		echo esc_html(
			sprintf(
				/* translators: 1: order number, 2: days remaining */
				Legal_String_Catalog::translate( 'details_intro' ),
				$input->order_number,
				$days_remaining
			)
		);
		?>
	</p>

	<input type="hidden" name="session_token" value="<?php echo esc_attr( $session_token ); ?>" />

	<fieldset class="eu-withdrawal__fieldset">
		<legend><?php echo esc_html( Legal_String_Catalog::translate( 'request_type_legend' ) ); ?></legend>
		<label class="eu-withdrawal__choice">
			<input type="radio" name="request_type" value="<?php echo esc_attr( Request_Type::RETURN ); ?>" required />
			<span class="eu-withdrawal__choice-title"><?php echo esc_html( Request_Type::label( Request_Type::RETURN ) ); ?></span>
			<span class="eu-withdrawal__choice-desc"><?php echo esc_html( Legal_String_Catalog::translate( 'return_choice_desc' ) ); ?></span>
		</label>
		<label class="eu-withdrawal__choice">
			<input type="radio" name="request_type" value="<?php echo esc_attr( Request_Type::REFUND ); ?>" />
			<span class="eu-withdrawal__choice-title"><?php echo esc_html( Request_Type::label( Request_Type::REFUND ) ); ?></span>
			<span class="eu-withdrawal__choice-desc"><?php echo esc_html( Legal_String_Catalog::translate( 'refund_choice_desc' ) ); ?></span>
		</label>
	</fieldset>

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

	<div class="eu-withdrawal__iban-panel" data-require-iban="<?php echo esc_attr( $require_iban ); ?>" hidden>
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

	<div class="eu-withdrawal__courier-panel" hidden>
		<h3 class="eu-withdrawal__subheading"><?php echo esc_html( Legal_String_Catalog::translate( 'courier_heading' ) ); ?></h3>
		<?php if ( '' !== trim( $courier_text ) ) : ?>
			<div class="eu-withdrawal__courier-box">
				<?php echo nl2br( esc_html( $courier_text ) ); ?>
			</div>
		<?php else : ?>
			<p class="eu-withdrawal__note"><?php echo esc_html( Legal_String_Catalog::translate( 'courier_fallback' ) ); ?></p>
		<?php endif; ?>
	</div>

	<p class="eu-withdrawal__field">
		<label for="eu-withdrawal-reason">
			<?php echo esc_html( Legal_String_Catalog::translate( 'reason_for_withdrawal' ) ); ?>
			<span class="eu-withdrawal__optional"><?php echo esc_html( Legal_String_Catalog::translate( 'optional' ) ); ?></span>
		</label>
		<textarea id="eu-withdrawal-reason" name="reason" rows="3"><?php echo esc_textarea( $input->reason ); ?></textarea>
	</p>

	<div class="eu-withdrawal__field eu-withdrawal__photos">
		<label for="eu-withdrawal-photos">
			<?php echo esc_html( Legal_String_Catalog::translate( 'reason_photos' ) ); ?>
			<span class="eu-withdrawal__optional"><?php echo esc_html( Legal_String_Catalog::translate( 'optional' ) ); ?></span>
		</label>
		<p class="eu-withdrawal__hint">
			<?php echo esc_html( Legal_String_Catalog::translate( 'reason_photos_hint' ) ); ?>
		</p>
		<input
			type="file"
			id="eu-withdrawal-photos"
			name="reason_photos[]"
			accept="image/jpeg,image/png,image/gif,image/webp"
			multiple
		/>
		<ul class="eu-withdrawal__photo-preview" hidden></ul>
		<?php if ( ! empty( $input->attachments ) ) : ?>
			<p class="eu-withdrawal__note eu-withdrawal__photos-kept">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: number of photos already uploaded */
						Legal_String_Catalog::translate( 'reason_photos_kept' ),
						count( $input->attachments )
					)
				);
				?>
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
