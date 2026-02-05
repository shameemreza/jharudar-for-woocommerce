<?php
/**
 * Exporter base class.
 *
 * @package Jharudar
 * @since   0.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Exporter class.
 *
 * Handles exporting data before cleanup operations.
 *
 * @since 0.0.1
 */
class Jharudar_Exporter {

	/**
	 * Export type.
	 *
	 * @var string
	 */
	protected $export_type = 'csv';

	/**
	 * Export data.
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Column headers.
	 *
	 * @var array
	 */
	protected $columns = array();

	/**
	 * Filename.
	 *
	 * @var string
	 */
	protected $filename = 'export';

	/**
	 * Constructor.
	 *
	 * @since 0.0.1
	 * @param string $export_type Export type (csv or json).
	 */
	public function __construct( $export_type = 'csv' ) {
		$this->export_type = in_array( $export_type, array( 'csv', 'json' ), true ) ? $export_type : 'csv';
	}

	/**
	 * Set columns.
	 *
	 * @since 0.0.1
	 * @param array $columns Column headers.
	 * @return self
	 */
	public function set_columns( $columns ) {
		$this->columns = $columns;
		return $this;
	}

	/**
	 * Set data.
	 *
	 * @since 0.0.1
	 * @param array $data Data to export.
	 * @return self
	 */
	public function set_data( $data ) {
		$this->data = $data;
		return $this;
	}

	/**
	 * Set filename.
	 *
	 * @since 0.0.1
	 * @param string $filename Filename without extension.
	 * @return self
	 */
	public function set_filename( $filename ) {
		$this->filename = sanitize_file_name( $filename );
		return $this;
	}

	/**
	 * Generate export content.
	 *
	 * @since 0.0.1
	 * @return string Export content.
	 */
	public function generate() {
		if ( 'json' === $this->export_type ) {
			return $this->generate_json();
		}

		return $this->generate_csv();
	}

	/**
	 * Generate CSV content.
	 *
	 * @since 0.0.1
	 * @return string CSV content.
	 */
	protected function generate_csv() {
		$lines = array();

		// Add headers.
		if ( ! empty( $this->columns ) ) {
			$lines[] = $this->array_to_csv_line( array_values( $this->columns ) );
		}

		// Add data rows.
		foreach ( $this->data as $row ) {
			$csv_row = array();
			foreach ( $this->columns as $key => $label ) {
				$value = isset( $row[ $key ] ) ? $row[ $key ] : '';

				// Handle arrays.
				if ( is_array( $value ) ) {
					$value = implode( ', ', $value );
				}

				$csv_row[] = $value;
			}
			$lines[] = $this->array_to_csv_line( $csv_row );
		}

		// Add BOM for UTF-8 and join lines.
		return "\xEF\xBB\xBF" . implode( "\n", $lines );
	}

	/**
	 * Convert array to CSV line.
	 *
	 * @since 0.0.1
	 * @param array $fields Array of field values.
	 * @return string CSV formatted line.
	 */
	protected function array_to_csv_line( $fields ) {
		$escaped = array();
		foreach ( $fields as $field ) {
			$field = (string) $field;
			// Escape double quotes and wrap in quotes if needed.
			if ( strpos( $field, ',' ) !== false || strpos( $field, '"' ) !== false || strpos( $field, "\n" ) !== false ) {
				$field = '"' . str_replace( '"', '""', $field ) . '"';
			}
			$escaped[] = $field;
		}
		return implode( ',', $escaped );
	}

	/**
	 * Generate JSON content.
	 *
	 * @since 0.0.1
	 * @return string JSON content.
	 */
	protected function generate_json() {
		return wp_json_encode( $this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Download the export file.
	 *
	 * @since 0.0.1
	 * @return void
	 */
	public function download() {
		$content = $this->generate();

		$filename  = $this->filename . '-' . gmdate( 'Y-m-d-H-i-s' );
		$filename .= 'json' === $this->export_type ? '.json' : '.csv';

		$content_type = 'json' === $this->export_type ? 'application/json' : 'text/csv';

		header( 'Content-Type: ' . $content_type . '; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/**
	 * Save export to file.
	 *
	 * @since 0.0.1
	 * @param string $directory Directory path.
	 * @return string|false File path or false on failure.
	 */
	public function save( $directory = '' ) {
		global $wp_filesystem;

		// Initialize WP_Filesystem.
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		if ( ! $wp_filesystem ) {
			return false;
		}

		if ( empty( $directory ) ) {
			$upload_dir = wp_upload_dir();
			$directory  = $upload_dir['basedir'] . '/jharudar-exports';
		}

		// Create directory if it doesn't exist.
		if ( ! $wp_filesystem->is_dir( $directory ) ) {
			wp_mkdir_p( $directory );

			// Add index.php for security.
			$index_file = $directory . '/index.php';
			if ( ! $wp_filesystem->exists( $index_file ) ) {
				$wp_filesystem->put_contents( $index_file, '<?php // Silence is golden.', FS_CHMOD_FILE );
			}

			// Add .htaccess to prevent direct access.
			$htaccess_file = $directory . '/.htaccess';
			if ( ! $wp_filesystem->exists( $htaccess_file ) ) {
				$wp_filesystem->put_contents( $htaccess_file, 'deny from all', FS_CHMOD_FILE );
			}
		}

		$content = $this->generate();

		$filename  = $this->filename . '-' . gmdate( 'Y-m-d-H-i-s' );
		$filename .= 'json' === $this->export_type ? '.json' : '.csv';

		$filepath = trailingslashit( $directory ) . $filename;

		$result = $wp_filesystem->put_contents( $filepath, $content, FS_CHMOD_FILE );

		if ( false === $result ) {
			return false;
		}

		// Log the export.
		jharudar_log_activity( 'export', $this->filename, 0, array( 'file' => $filepath ) );

		return $filepath;
	}

	/**
	 * Export products.
	 *
	 * @since 0.0.1
	 * @param array $product_ids Product IDs.
	 * @return self
	 */
	public function export_products( $product_ids ) {
		$this->set_columns(
			array(
				'id'           => __( 'ID', 'jharudar-for-woocommerce' ),
				'name'         => __( 'Name', 'jharudar-for-woocommerce' ),
				'sku'          => __( 'SKU', 'jharudar-for-woocommerce' ),
				'status'       => __( 'Status', 'jharudar-for-woocommerce' ),
				'price'        => __( 'Price', 'jharudar-for-woocommerce' ),
				'stock_status' => __( 'Stock Status', 'jharudar-for-woocommerce' ),
				'type'         => __( 'Type', 'jharudar-for-woocommerce' ),
				'categories'   => __( 'Categories', 'jharudar-for-woocommerce' ),
			)
		);

		$data = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );

			$data[] = array(
				'id'           => $product->get_id(),
				'name'         => $product->get_name(),
				'sku'          => $product->get_sku(),
				'status'       => $product->get_status(),
				'price'        => $product->get_price(),
				'stock_status' => $product->get_stock_status(),
				'type'         => $product->get_type(),
				'categories'   => is_array( $categories ) ? $categories : array(),
			);
		}

		$this->set_data( $data );
		$this->set_filename( 'products-export' );

		return $this;
	}

	/**
	 * Export orders.
	 *
	 * @since 0.0.1
	 * @param array $order_ids Order IDs.
	 * @return self
	 */
	public function export_orders( $order_ids ) {
		$this->set_columns(
			array(
				'id'             => __( 'ID', 'jharudar-for-woocommerce' ),
				'status'         => __( 'Status', 'jharudar-for-woocommerce' ),
				'date_created'   => __( 'Date Created', 'jharudar-for-woocommerce' ),
				'total'          => __( 'Total', 'jharudar-for-woocommerce' ),
				'billing_email'  => __( 'Billing Email', 'jharudar-for-woocommerce' ),
				'billing_name'   => __( 'Billing Name', 'jharudar-for-woocommerce' ),
				'payment_method' => __( 'Payment Method', 'jharudar-for-woocommerce' ),
			)
		);

		$data = array();

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				continue;
			}

			$data[] = array(
				'id'             => $order->get_id(),
				'status'         => $order->get_status(),
				'date_created'   => $order->get_date_created() ? $order->get_date_created()->format( 'Y-m-d H:i:s' ) : '',
				'total'          => $order->get_total(),
				'billing_email'  => $order->get_billing_email(),
				'billing_name'   => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				'payment_method' => $order->get_payment_method_title(),
			);
		}

		$this->set_data( $data );
		$this->set_filename( 'orders-export' );

		return $this;
	}

	/**
	 * Export customers.
	 *
	 * @since 0.0.1
	 * @param array $customer_ids Customer IDs.
	 * @return self
	 */
	public function export_customers( $customer_ids ) {
		$this->set_columns(
			array(
				'id'            => __( 'ID', 'jharudar-for-woocommerce' ),
				'email'         => __( 'Email', 'jharudar-for-woocommerce' ),
				'first_name'    => __( 'First Name', 'jharudar-for-woocommerce' ),
				'last_name'     => __( 'Last Name', 'jharudar-for-woocommerce' ),
				'date_created'  => __( 'Date Created', 'jharudar-for-woocommerce' ),
				'orders_count'  => __( 'Orders Count', 'jharudar-for-woocommerce' ),
				'total_spent'   => __( 'Total Spent', 'jharudar-for-woocommerce' ),
			)
		);

		$data = array();

		foreach ( $customer_ids as $customer_id ) {
			$customer = new WC_Customer( $customer_id );
			if ( ! $customer->get_id() ) {
				continue;
			}

			$data[] = array(
				'id'           => $customer->get_id(),
				'email'        => $customer->get_email(),
				'first_name'   => $customer->get_first_name(),
				'last_name'    => $customer->get_last_name(),
				'date_created' => $customer->get_date_created() ? $customer->get_date_created()->format( 'Y-m-d H:i:s' ) : '',
				'orders_count' => $customer->get_order_count(),
				'total_spent'  => $customer->get_total_spent(),
			);
		}

		$this->set_data( $data );
		$this->set_filename( 'customers-export' );

		return $this;
	}

	/**
	 * Export coupons.
	 *
	 * @since 0.0.1
	 * @param array $coupon_ids Coupon IDs.
	 * @return self
	 */
	public function export_coupons( $coupon_ids ) {
		$this->set_columns(
			array(
				'id'           => __( 'ID', 'jharudar-for-woocommerce' ),
				'code'         => __( 'Code', 'jharudar-for-woocommerce' ),
				'discount'     => __( 'Discount', 'jharudar-for-woocommerce' ),
				'type'         => __( 'Type', 'jharudar-for-woocommerce' ),
				'usage_count'  => __( 'Usage Count', 'jharudar-for-woocommerce' ),
				'expiry_date'  => __( 'Expiry Date', 'jharudar-for-woocommerce' ),
			)
		);

		$data = array();

		foreach ( $coupon_ids as $coupon_id ) {
			$coupon = new WC_Coupon( $coupon_id );
			if ( ! $coupon->get_id() ) {
				continue;
			}

			$data[] = array(
				'id'          => $coupon->get_id(),
				'code'        => $coupon->get_code(),
				'discount'    => $coupon->get_amount(),
				'type'        => $coupon->get_discount_type(),
				'usage_count' => $coupon->get_usage_count(),
				'expiry_date' => $coupon->get_date_expires() ? $coupon->get_date_expires()->format( 'Y-m-d' ) : '',
			);
		}

		$this->set_data( $data );
		$this->set_filename( 'coupons-export' );

		return $this;
	}
}
