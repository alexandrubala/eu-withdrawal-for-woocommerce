<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
/**
 * Confirmation step before final submission.
 *
 * @package EUWithdrawal
 *
 * @var \EUWithdrawal\Domain\Step1_Input $input
 * @var string                           $session_token
 */

use EUWithdrawal\Domain\Request_Type;
use EUWithdrawal\Integrations\Legal_String_Catalog;

defined( 'ABSPATH' ) || exit;

if ( ! isset( $input ) || ! $input instanceof \EUWithdrawal\Domain\Step1_Input ) {
	return;
}

$session_token = $session_token ?? '';
?>
<div class="eu-withdrawal__confirm eu-withdrawal__form--step2">
	<h2 class="eu-withdrawal__heading"><?php echo esc_html( Legal_String_Catalog::translate( 'confirm_heading' ) ); ?></h2>
	<p class="eu-withdrawal__intro">
		<?php echo esc_html( Legal_String_Catalog::translate( 'confirm_intro' ) ); ?>
	</p>

	<dl class="eu-withdrawal__summary">
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'full_name' ) ); ?></dt>
			<dd><?php echo esc_html( $input->name ); ?></dd>
		</div>
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'email' ) ); ?></dt>
			<dd><?php echo esc_html( $input->email ); ?></dd>
		</div>
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'order_number' ) ); ?></dt>
			<dd><?php echo esc_html( $input->order_number ); ?></dd>
		</div>
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'request_type_legend' ) ); ?></dt>
			<dd><?php echo esc_html( Request_Type::label( $input->request_type ) ); ?></dd>
		</div>
		<?php if ( '' !== $input->phone ) : ?>
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'phone' ) ); ?></dt>
			<dd><?php echo esc_html( $input->phone ); ?></dd>
		</div>
		<?php endif; ?>
		<?php if ( Request_Type::REFUND === $input->request_type && '' !== $input->refund_iban ) : ?>
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'iban' ) ); ?></dt>
			<dd><?php echo esc_html( $input->refund_iban ); ?></dd>
		</div>
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'account_holder' ) ); ?></dt>
			<dd><?php echo esc_html( $input->refund_account_name ); ?></dd>
		</div>
		<?php endif; ?>
		<?php if ( '' !== $input->reason ) : ?>
		<div class="eu-withdrawal__summary-row">
			<dt><?php echo esc_html( Legal_String_Catalog::translate( 'reason_for_withdrawal' ) ); ?></dt>
			<dd><?php echo esc_html( $input->reason ); ?></dd>
		</div>
		<?php endif; ?>
	</dl>

	<?php if ( ! empty( $input->selected_products ) ) : ?>
		<h3 class="eu-withdrawal__subheading"><?php echo esc_html( Legal_String_Catalog::translate( 'products' ) ); ?></h3>
		<ul class="eu-withdrawal__product-list eu-withdrawal__product-list--readonly">
			<?php foreach ( $input->selected_products as $product ) : ?>
				<li class="eu-withdrawal__product-card">
					<span class="eu-withdrawal__product-body">
						<?php if ( ! empty( $product['image'] ) ) : ?>
							<img class="eu-withdrawal__product-image" src="<?php echo esc_url( (string) $product['image'] ); ?>" alt="" width="56" height="56" loading="lazy" />
						<?php endif; ?>
						<span class="eu-withdrawal__product-meta">
							<span class="eu-withdrawal__product-name"><?php echo esc_html( (string) ( $product['name'] ?? '' ) ); ?></span>
							<span class="eu-withdrawal__product-price">
								× <?php echo esc_html( (string) ( $product['quantity'] ?? 1 ) ); ?>
								<?php if ( ! empty( $product['price_html'] ) ) : ?>
									— <?php echo wp_kses_post( (string) $product['price_html'] ); ?>
								<?php endif; ?>
							</span>
						</span>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( Request_Type::RETURN === $input->request_type && '' !== trim( $input->courier_notes ) ) : ?>
		<h3 class="eu-withdrawal__subheading"><?php echo esc_html( Legal_String_Catalog::translate( 'courier_heading' ) ); ?></h3>
		<div class="eu-withdrawal__courier-box">
			<?php echo nl2br( esc_html( $input->courier_notes ) ); ?>
		</div>
	<?php endif; ?>

	<form class="eu-withdrawal__form eu-withdrawal__form--confirm" method="post">
		<input type="hidden" name="session_token" value="<?php echo esc_attr( $session_token ); ?>" />

		<p class="eu-withdrawal__actions">
			<button type="button" class="eu-withdrawal__back button button-secondary">
				<?php echo esc_html( Legal_String_Catalog::translate( 'back' ) ); ?>
			</button>
			<button type="submit" class="eu-withdrawal__confirm button">
				<?php echo esc_html( Legal_String_Catalog::translate( 'confirm_withdrawal' ) ); ?>
			</button>
		</p>
	</form>
</div>
