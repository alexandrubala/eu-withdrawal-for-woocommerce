<?php
/**
 * CSV and print-friendly export for withdrawal requests.
 *
 * @package EUWithdrawal\Services
 */

namespace EUWithdrawal\Services;

use EUWithdrawal\Data\Withdrawal_Repository;
use EUWithdrawal\Domain\Withdrawal_Status;

defined( 'ABSPATH' ) || exit;

/**
 * Class Export_Service
 */
final class Export_Service {

	/**
	 * Maximum rows included in a single export.
	 */
	private const MAX_EXPORT_ROWS = 5000;

	/**
	 * Withdrawal requests repository.
	 *
	 * @var Withdrawal_Repository
	 */
	private Withdrawal_Repository $withdrawal_repository;

	/**
	 * Constructor.
	 *
	 * @param Withdrawal_Repository $withdrawal_repository Withdrawal persistence.
	 */
	public function __construct( Withdrawal_Repository $withdrawal_repository ) {
		$this->withdrawal_repository = $withdrawal_repository;
	}

	/**
	 * Stream a CSV download for matching withdrawal requests.
	 *
	 * @param array<string, mixed> $query_args Repository query arguments.
	 * @return void
	 */
	public function send_csv_download( array $query_args ): void {
		$items = $this->fetch_export_items( $query_args );
		$filename = 'eu-withdrawals-' . gmdate( 'Y-m-d-His' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		$output = fopen( 'php://output', 'w' );

		if ( false === $output ) {
			return;
		}

		// UTF-8 BOM for Excel compatibility.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fwrite( $output, "\xEF\xBB\xBF" );

		fputcsv(
			$output,
			array(
				__( 'ID', 'eu-withdrawal-for-woocommerce' ),
				__( 'UUID', 'eu-withdrawal-for-woocommerce' ),
				__( 'Customer Name', 'eu-withdrawal-for-woocommerce' ),
				__( 'Customer Email', 'eu-withdrawal-for-woocommerce' ),
				__( 'Customer Phone', 'eu-withdrawal-for-woocommerce' ),
				__( 'Order Number', 'eu-withdrawal-for-woocommerce' ),
				__( 'Type', 'eu-withdrawal-for-woocommerce' ),
				__( 'IBAN', 'eu-withdrawal-for-woocommerce' ),
				__( 'Account holder', 'eu-withdrawal-for-woocommerce' ),
				__( 'Status', 'eu-withdrawal-for-woocommerce' ),
				__( 'Reason', 'eu-withdrawal-for-woocommerce' ),
				__( 'Products', 'eu-withdrawal-for-woocommerce' ),
				__( 'Submitted At', 'eu-withdrawal-for-woocommerce' ),
			)
		);

		foreach ( $items as $item ) {
			$status = (string) ( $item['status'] ?? Withdrawal_Status::PENDING );

			fputcsv(
				$output,
				array(
					(int) ( $item['id'] ?? 0 ),
					(string) ( $item['uuid'] ?? '' ),
					(string) ( $item['customer_name'] ?? '' ),
					(string) ( $item['customer_email'] ?? '' ),
					(string) ( $item['customer_phone'] ?? '' ),
					(string) ( $item['order_number'] ?? '' ),
					(string) ( $item['request_type'] ?? '' ),
					(string) ( $item['refund_iban'] ?? '' ),
					(string) ( $item['refund_account_name'] ?? '' ),
					Withdrawal_Status::label( $status ),
					(string) ( $item['reason'] ?? '' ),
					$this->format_products_for_csv( (string) ( $item['products_json'] ?? '' ) ),
					(string) ( $item['submitted_at'] ?? '' ),
				)
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		fclose( $output );
	}

	/**
	 * Output a print-friendly HTML view and trigger the browser print dialog.
	 *
	 * @param array<string, mixed> $query_args Repository query arguments.
	 * @return void
	 */
	public function send_print_view( array $query_args ): void {
		$items = $this->fetch_export_items( $query_args );
		$html  = $this->generate_print_html( $items );

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is built with escaping helpers below.
		echo $html;
	}

	/**
	 * Build print-friendly HTML for a set of withdrawal rows.
	 *
	 * @param array<int, array<string, mixed>> $items Withdrawal rows.
	 * @return string
	 */
	public function generate_print_html( array $items ): string {
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$title     = __( 'Contract Withdrawals', 'eu-withdrawal-for-woocommerce' );
		$generated = wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );

		ob_start();
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo esc_html( $title ); ?></title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #111; margin: 24px; }
		h1 { font-size: 1.5rem; margin: 0 0 4px; }
		.meta { color: #555; font-size: 0.875rem; margin-bottom: 20px; }
		table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
		th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; vertical-align: top; }
		th { background: #f5f5f5; }
		.no-print { margin-bottom: 16px; }
		@media print {
			.no-print { display: none; }
			body { margin: 0; }
		}
	</style>
</head>
<body>
	<div class="no-print">
		<button type="button" onclick="window.print();"><?php esc_html_e( 'Print', 'eu-withdrawal-for-woocommerce' ); ?></button>
	</div>
	<h1><?php echo esc_html( $title ); ?></h1>
	<p class="meta">
		<?php
		echo esc_html(
			sprintf(
				/* translators: 1: site name, 2: generation datetime, 3: item count */
				__( '%1$s — Generated on %2$s (%3$d requests)', 'eu-withdrawal-for-woocommerce' ),
				$site_name,
				$generated,
				count( $items )
			)
		);
		?>
	</p>
	<table>
		<thead>
			<tr>
				<th><?php esc_html_e( 'ID', 'eu-withdrawal-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'UUID', 'eu-withdrawal-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Customer', 'eu-withdrawal-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Order', 'eu-withdrawal-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Status', 'eu-withdrawal-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Submitted', 'eu-withdrawal-for-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $items ) ) : ?>
			<tr>
				<td colspan="6"><?php esc_html_e( 'No withdrawal requests found.', 'eu-withdrawal-for-woocommerce' ); ?></td>
			</tr>
		<?php else : ?>
			<?php foreach ( $items as $item ) : ?>
				<?php
				$status = (string) ( $item['status'] ?? Withdrawal_Status::PENDING );
				?>
				<tr>
					<td><?php echo esc_html( (string) ( $item['id'] ?? '' ) ); ?></td>
					<td><code><?php echo esc_html( (string) ( $item['uuid'] ?? '' ) ); ?></code></td>
					<td>
						<?php echo esc_html( (string) ( $item['customer_name'] ?? '' ) ); ?><br>
						<?php echo esc_html( (string) ( $item['customer_email'] ?? '' ) ); ?>
					</td>
					<td><?php echo esc_html( (string) ( $item['order_number'] ?? '' ) ); ?></td>
					<td><?php echo esc_html( Withdrawal_Status::label( $status ) ); ?></td>
					<td><?php echo esc_html( $this->format_datetime( (string) ( $item['submitted_at'] ?? '' ) ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
	<script>
		window.addEventListener( 'load', function () {
			window.print();
		} );
	</script>
</body>
</html>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Fetch withdrawal rows for export using list filters.
	 *
	 * @param array<string, mixed> $query_args Repository query arguments.
	 * @return array<int, array<string, mixed>>
	 */
	private function fetch_export_items( array $query_args ): array {
		$query_args['per_page'] = self::MAX_EXPORT_ROWS;
		$query_args['offset']   = 0;

		$result = $this->withdrawal_repository->query( $query_args );

		return is_array( $result['items'] ) ? $result['items'] : array();
	}

	/**
	 * Format products JSON as a compact CSV-friendly string.
	 *
	 * @param string $json Stored products JSON.
	 * @return string
	 */
	private function format_products_for_csv( string $json ): string {
		$products = $this->decode_products( $json );

		if ( empty( $products ) ) {
			return '';
		}

		$parts = array();

		foreach ( $products as $product ) {
			$name     = (string) ( $product['name'] ?? '' );
			$sku      = (string) ( $product['sku'] ?? '' );
			$quantity = (int) ( $product['quantity'] ?? 0 );
			$parts[]  = trim( $name . ( '' !== $sku ? ' [' . $sku . ']' : '' ) . ' x' . $quantity );
		}

		return implode( '; ', $parts );
	}

	/**
	 * Decode products JSON into an array.
	 *
	 * @param string $json Stored products JSON.
	 * @return array<int, array<string, mixed>>
	 */
	private function decode_products( string $json ): array {
		if ( '' === $json ) {
			return array();
		}

		$decoded = json_decode( $json, true );

		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Format a MySQL datetime for display.
	 *
	 * @param string $datetime MySQL datetime string.
	 * @return string
	 */
	private function format_datetime( string $datetime ): string {
		if ( '' === $datetime || '0000-00-00 00:00:00' === $datetime ) {
			return '—';
		}

		$timestamp = strtotime( $datetime );

		if ( false === $timestamp ) {
			return $datetime;
		}

		return wp_date(
			get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			$timestamp
		);
	}
}
