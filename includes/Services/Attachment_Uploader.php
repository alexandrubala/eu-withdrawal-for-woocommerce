<?php
/**
 * Secure image upload handling for withdrawal reason photos.
 *
 * @package EUWithdrawal\Services
 */

namespace EUWithdrawal\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Class Attachment_Uploader
 */
final class Attachment_Uploader {

	/**
	 * Maximum number of photos per request.
	 */
	public const MAX_FILES = 5;

	/**
	 * Maximum file size in bytes (5 MB).
	 */
	public const MAX_BYTES = 5242880;

	/**
	 * Allowed MIME types.
	 *
	 * @var array<string, string>
	 */
	private const ALLOWED_MIMES = array(
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'gif'          => 'image/gif',
		'webp'         => 'image/webp',
	);

	/**
	 * Meta key marking a temporary upload before final submission.
	 */
	public const META_TEMP = '_eu_withdrawal_temp';

	/**
	 * Meta key linking an attachment to a withdrawal request UUID.
	 */
	public const META_REQUEST = '_eu_withdrawal_request';

	/**
	 * Upload reason photos from $_FILES and return attachment IDs.
	 *
	 * @param array<string, mixed> $files   Typically $_FILES['reason_photos'].
	 * @param array<int, int>      $keep_ids Existing attachment IDs to retain when no new files are sent.
	 * @return array{ids: array<int, int>, error: string}
	 */
	public function process( array $files, array $keep_ids = array() ): array {
		$keep_ids = $this->sanitize_ids( $keep_ids );

		if ( empty( $files ) || empty( $files['name'] ) ) {
			return array(
				'ids'   => $keep_ids,
				'error' => '',
			);
		}

		$normalized = $this->normalize_files( $files );

		if ( empty( $normalized ) ) {
			return array(
				'ids'   => $keep_ids,
				'error' => '',
			);
		}

		if ( count( $normalized ) > self::MAX_FILES ) {
			return array(
				'ids'   => $keep_ids,
				'error' => sprintf(
					/* translators: %d: maximum number of photos */
					__( 'You can upload a maximum of %d photos.', 'eu-withdrawal-for-woocommerce' ),
					self::MAX_FILES
				),
			);
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$uploaded = array();

		foreach ( $normalized as $file ) {
			$error = $this->validate_file( $file );

			if ( '' !== $error ) {
				$this->delete_attachments( $uploaded );

				return array(
					'ids'   => $keep_ids,
					'error' => $error,
				);
			}

			$overrides = array(
				'test_form' => false,
				'mimes'     => self::ALLOWED_MIMES,
			);

			$move = wp_handle_upload( $file, $overrides );

			if ( isset( $move['error'] ) ) {
				$this->delete_attachments( $uploaded );

				return array(
					'ids'   => $keep_ids,
					'error' => (string) $move['error'],
				);
			}

			$attachment = array(
				'post_mime_type' => $move['type'],
				'post_title'     => sanitize_file_name( pathinfo( $move['file'], PATHINFO_FILENAME ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);

			$attachment_id = wp_insert_attachment( $attachment, $move['file'] );

			if ( is_wp_error( $attachment_id ) || ! $attachment_id ) {
				$this->delete_attachments( $uploaded );

				return array(
					'ids'   => $keep_ids,
					'error' => __( 'Could not save one of the uploaded photos. Please try again.', 'eu-withdrawal-for-woocommerce' ),
				);
			}

			$metadata = wp_generate_attachment_metadata( (int) $attachment_id, $move['file'] );
			wp_update_attachment_metadata( (int) $attachment_id, $metadata );
			update_post_meta( (int) $attachment_id, self::META_TEMP, '1' );

			$uploaded[] = (int) $attachment_id;
		}

		// New uploads replace previous ones for this session.
		$this->delete_attachments( $keep_ids );

		return array(
			'ids'   => $uploaded,
			'error' => '',
		);
	}

	/**
	 * Mark temporary attachments as belonging to a submitted request.
	 *
	 * @param array<int, int> $ids          Attachment IDs.
	 * @param string          $request_uuid Request UUID.
	 * @return void
	 */
	public function attach_to_request( array $ids, string $request_uuid ): void {
		foreach ( $this->sanitize_ids( $ids ) as $id ) {
			delete_post_meta( $id, self::META_TEMP );
			update_post_meta( $id, self::META_REQUEST, $request_uuid );
		}
	}

	/**
	 * Delete attachment posts (and files).
	 *
	 * @param array<int, int> $ids Attachment IDs.
	 * @return void
	 */
	public function delete_attachments( array $ids ): void {
		foreach ( $this->sanitize_ids( $ids ) as $id ) {
			wp_delete_attachment( $id, true );
		}
	}

	/**
	 * Build display data for attachment IDs.
	 *
	 * @param array<int, int> $ids Attachment IDs.
	 * @return array<int, array{id: int, url: string, thumb: string, name: string}>
	 */
	public function describe( array $ids ): array {
		$items = array();

		foreach ( $this->sanitize_ids( $ids ) as $id ) {
			$url = wp_get_attachment_url( $id );

			if ( ! $url ) {
				continue;
			}

			$thumb = wp_get_attachment_image_url( $id, 'thumbnail' );

			$items[] = array(
				'id'    => $id,
				'url'   => $url,
				'thumb' => $thumb ? $thumb : $url,
				'name'  => get_the_title( $id ),
			);
		}

		return $items;
	}

	/**
	 * Decode stored attachments JSON into attachment IDs.
	 *
	 * @param string $json Stored JSON.
	 * @return array<int, int>
	 */
	public static function decode_ids( string $json ): array {
		if ( '' === $json ) {
			return array();
		}

		$decoded = json_decode( $json, true );

		if ( ! is_array( $decoded ) ) {
			return array();
		}

		$ids = array();

		foreach ( $decoded as $value ) {
			$id = absint( $value );

			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Normalize a multi-file $_FILES entry into a list of single-file arrays.
	 *
	 * @param array<string, mixed> $files $_FILES entry.
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_files( array $files ): array {
		$normalized = array();

		if ( ! is_array( $files['name'] ) ) {
			if ( UPLOAD_ERR_NO_FILE === (int) ( $files['error'] ?? UPLOAD_ERR_NO_FILE ) ) {
				return array();
			}

			return array( $files );
		}

		$count = count( $files['name'] );

		for ( $i = 0; $i < $count; $i++ ) {
			$error = (int) ( $files['error'][ $i ] ?? UPLOAD_ERR_NO_FILE );

			if ( UPLOAD_ERR_NO_FILE === $error ) {
				continue;
			}

			$normalized[] = array(
				'name'     => (string) ( $files['name'][ $i ] ?? '' ),
				'type'     => (string) ( $files['type'][ $i ] ?? '' ),
				'tmp_name' => (string) ( $files['tmp_name'][ $i ] ?? '' ),
				'error'    => $error,
				'size'     => (int) ( $files['size'][ $i ] ?? 0 ),
			);
		}

		return $normalized;
	}

	/**
	 * Validate a single uploaded file.
	 *
	 * @param array<string, mixed> $file Single file array.
	 * @return string Error message, or empty string when valid.
	 */
	private function validate_file( array $file ): string {
		$error = (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE );

		if ( UPLOAD_ERR_OK !== $error ) {
			return __( 'One of the photos could not be uploaded. Please try again.', 'eu-withdrawal-for-woocommerce' );
		}

		$size = (int) ( $file['size'] ?? 0 );

		if ( $size <= 0 || $size > self::MAX_BYTES ) {
			return sprintf(
				/* translators: %d: maximum size in MB */
				__( 'Each photo must be at most %d MB.', 'eu-withdrawal-for-woocommerce' ),
				(int) ( self::MAX_BYTES / 1048576 )
			);
		}

		$check = wp_check_filetype_and_ext(
			(string) ( $file['tmp_name'] ?? '' ),
			(string) ( $file['name'] ?? '' ),
			self::ALLOWED_MIMES
		);

		if ( empty( $check['type'] ) || empty( $check['ext'] ) ) {
			return __( 'Only JPG, PNG, GIF, or WebP images are allowed.', 'eu-withdrawal-for-woocommerce' );
		}

		return '';
	}

	/**
	 * Sanitize a list of attachment IDs.
	 *
	 * @param array<int, mixed> $ids Raw IDs.
	 * @return array<int, int>
	 */
	private function sanitize_ids( array $ids ): array {
		$clean = array();

		foreach ( $ids as $id ) {
			$id = absint( $id );

			if ( $id > 0 && get_post_type( $id ) === 'attachment' ) {
				$clean[] = $id;
			}
		}

		return array_values( array_unique( $clean ) );
	}
}
