/**
 * Jharudar Admin JavaScript
 *
 * @package Jharudar
 * @since   0.0.1
 */

( function( $ ) {
	'use strict';

	/**
	 * Jharudar Admin object.
	 */
	var JharudarAdmin = {

		/**
		 * Current module data.
		 */
		currentItems: [],
		currentOffset: 0,
		currentTotal: 0,

		/**
		 * Initialize.
		 */
		init: function() {
			this.initSelectWoo();
			this.bindEvents();
			this.bindProductEvents();
			this.bindOrderEvents();
			this.bindCustomerEvents();
			this.autoLoadData();
		},

		/**
		 * Auto-load data on page load based on current tab.
		 */
		autoLoadData: function() {
			var self = this;

			// Check which page we're on and auto-load data.
			if ( $( '.jharudar-products-page' ).length && $( '#jharudar-products-results' ).length ) {
				// Only auto-load on "All Products" sub-tab, not orphaned images.
				if ( ! window.location.href.includes( 'subtab=orphaned' ) ) {
					self.currentOffset = 0;
					self.loadProducts();
				}
			}

			if ( $( '.jharudar-orders-page' ).length && $( '#jharudar-orders-results' ).length ) {
				self.currentOffset = 0;
				self.loadOrders();
			}

			if ( $( '.jharudar-customers-page' ).length && $( '#jharudar-customers-results' ).length ) {
				self.currentOffset = 0;
				self.loadCustomers();
			}
		},

		/**
		 * Initialize SelectWoo on select elements.
		 */
		initSelectWoo: function() {
			$( '.jharudar-select, .jharudar-select2' ).selectWoo( {
				width: '200px',
				allowClear: true,
				placeholder: function() {
					return $( this ).data( 'placeholder' ) || '';
				}
			} );
		},

		/**
		 * Bind general events.
		 */
		bindEvents: function() {
			var self = this;

			// Close modal on cancel.
			$( document ).on( 'click', '[id$="-cancel-delete"], [id$="-cancel-anonymize"]', function( e ) {
				e.preventDefault();
				self.closeModal( $( this ).closest( '.jharudar-modal-overlay' ) );
			} );

			// Close modal on overlay click.
			$( document ).on( 'click', '.jharudar-modal-overlay', function( e ) {
				if ( $( e.target ).hasClass( 'jharudar-modal-overlay' ) ) {
					self.closeModal( $( this ) );
				}
			} );

			// Escape key to close modal.
			$( document ).on( 'keyup', function( e ) {
				if ( e.key === 'Escape' ) {
					$( '.jharudar-modal-overlay.active' ).removeClass( 'active' );
				}
			} );
		},

		/**
		 * Bind product module events.
		 */
		bindProductEvents: function() {
			var self = this;

			// Filter products.
			$( '#jharudar-filter-products' ).on( 'click', function() {
				self.currentOffset = 0;
				self.loadProducts();
			} );

			// Reset filters.
			$( '#jharudar-reset-filters' ).on( 'click', function() {
				$( '#jharudar-filter-category, #jharudar-filter-status, #jharudar-filter-stock, #jharudar-filter-type' ).val( '' ).trigger( 'change' );
				$( '#jharudar-filter-date-after, #jharudar-filter-date-before' ).val( '' );
				self.currentOffset = 0;
				self.loadProducts();
			} );

			// Select all products.
			$( '#jharudar-select-all-products' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-product-checkbox' ).prop( 'checked', checked );
				self.updateProductActionState();
			} );

			// Individual product checkbox.
			$( document ).on( 'change', '.jharudar-product-checkbox', function() {
				self.updateProductActionState();
			} );

			// Export products.
			$( '#jharudar-export-products' ).on( 'click', function() {
				self.exportProducts();
			} );

			// Delete products button.
			$( '#jharudar-delete-products' ).on( 'click', function() {
				self.showProductDeleteModal();
			} );

			// Confirm delete input.
			$( '#jharudar-confirm-delete-input' ).on( 'input', function() {
				var value = $( this ).val().trim().toUpperCase();
				var backupChecked = $( '#jharudar-confirm-backup' ).is( ':checked' );
				$( '#jharudar-confirm-delete' ).prop( 'disabled', value !== 'DELETE' || ! backupChecked );
			} );

			$( '#jharudar-confirm-backup' ).on( 'change', function() {
				var value = $( '#jharudar-confirm-delete-input' ).val().trim().toUpperCase();
				var backupChecked = $( this ).is( ':checked' );
				$( '#jharudar-confirm-delete' ).prop( 'disabled', value !== 'DELETE' || ! backupChecked );
			} );

			// Cancel delete.
			$( '#jharudar-cancel-delete' ).on( 'click', function() {
				self.closeModal( $( '#jharudar-delete-modal' ) );
			} );

			// Confirm delete.
			$( '#jharudar-confirm-delete' ).on( 'click', function() {
				self.deleteProducts();
			} );

			// Load more products.
			$( '#jharudar-load-more-products' ).on( 'click', function() {
				self.loadProducts( true );
			} );

			// Orphaned images.
			$( '#jharudar-scan-orphaned-images' ).on( 'click', function() {
				self.scanOrphanedImages();
			} );

			$( '#jharudar-select-all-images' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-image-checkbox' ).prop( 'checked', checked );
				self.updateImageActionState();
			} );

			$( document ).on( 'change', '.jharudar-image-checkbox', function() {
				self.updateImageActionState();
			} );

			$( '#jharudar-delete-orphaned-images' ).on( 'click', function() {
				self.deleteOrphanedImages();
			} );
		},

		/**
		 * Bind order module events.
		 */
		bindOrderEvents: function() {
			var self = this;

			// Filter orders.
			$( '#jharudar-filter-orders' ).on( 'click', function() {
				self.currentOffset = 0;
				self.loadOrders();
			} );

			// Reset filters.
			$( '#jharudar-reset-order-filters' ).on( 'click', function() {
				$( '#jharudar-filter-order-status, #jharudar-filter-payment-method' ).val( '' ).trigger( 'change' );
				$( '#jharudar-filter-order-date-after, #jharudar-filter-order-date-before' ).val( '' );
				self.currentOffset = 0;
				self.loadOrders();
			} );

			// Select all orders.
			$( '#jharudar-select-all-orders' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-order-checkbox' ).prop( 'checked', checked );
				self.updateOrderActionState();
			} );

			$( document ).on( 'change', '.jharudar-order-checkbox', function() {
				self.updateOrderActionState();
			} );

			// Export orders.
			$( '#jharudar-export-orders' ).on( 'click', function() {
				self.exportOrders();
			} );

			// Anonymize orders.
			$( '#jharudar-anonymize-orders' ).on( 'click', function() {
				self.showOrderAnonymizeModal();
			} );

			// Delete orders.
			$( '#jharudar-delete-orders' ).on( 'click', function() {
				self.showOrderDeleteModal();
			} );

			// Order delete confirmation.
			$( '#jharudar-confirm-order-delete-input' ).on( 'input', function() {
				var value = $( this ).val().trim().toUpperCase();
				var backupChecked = $( '#jharudar-confirm-order-backup' ).is( ':checked' );
				$( '#jharudar-confirm-order-delete' ).prop( 'disabled', value !== 'DELETE' || ! backupChecked );
			} );

			$( '#jharudar-confirm-order-backup' ).on( 'change', function() {
				var value = $( '#jharudar-confirm-order-delete-input' ).val().trim().toUpperCase();
				var backupChecked = $( this ).is( ':checked' );
				$( '#jharudar-confirm-order-delete' ).prop( 'disabled', value !== 'DELETE' || ! backupChecked );
			} );

			$( '#jharudar-cancel-order-delete' ).on( 'click', function() {
				self.closeModal( $( '#jharudar-order-delete-modal' ) );
			} );

			$( '#jharudar-confirm-order-delete' ).on( 'click', function() {
				self.deleteOrders();
			} );

			// Order anonymize confirmation.
			$( '#jharudar-confirm-anonymize-input' ).on( 'input', function() {
				var value = $( this ).val().trim().toUpperCase();
				$( '#jharudar-confirm-anonymize' ).prop( 'disabled', value !== 'ANONYMIZE' );
			} );

			$( '#jharudar-cancel-anonymize' ).on( 'click', function() {
				self.closeModal( $( '#jharudar-order-anonymize-modal' ) );
			} );

			$( '#jharudar-confirm-anonymize' ).on( 'click', function() {
				self.anonymizeOrders();
			} );

			// Load more orders.
			$( '#jharudar-load-more-orders' ).on( 'click', function() {
				self.loadOrders( true );
			} );
		},

		/**
		 * Bind customer module events.
		 */
		bindCustomerEvents: function() {
			var self = this;

			// Toggle inactive months filter.
			$( '#jharudar-filter-customer-type' ).on( 'change', function() {
				if ( $( this ).val() === 'inactive' ) {
					$( '.jharudar-inactive-months-filter' ).show();
				} else {
					$( '.jharudar-inactive-months-filter' ).hide();
				}
			} );

			// Filter customers.
			$( '#jharudar-filter-customers' ).on( 'click', function() {
				self.currentOffset = 0;
				self.loadCustomers();
			} );

			// Reset filters.
			$( '#jharudar-reset-customer-filters' ).on( 'click', function() {
				$( '#jharudar-filter-customer-type' ).val( '' ).trigger( 'change' );
				$( '#jharudar-filter-inactive-months' ).val( '12' );
				$( '#jharudar-filter-customer-date' ).val( '' );
				$( '.jharudar-inactive-months-filter' ).hide();
				self.currentOffset = 0;
				self.loadCustomers();
			} );

			// Select all customers.
			$( '#jharudar-select-all-customers' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-customer-checkbox' ).prop( 'checked', checked );
				self.updateCustomerActionState();
			} );

			$( document ).on( 'change', '.jharudar-customer-checkbox', function() {
				self.updateCustomerActionState();
			} );

			// Export customers.
			$( '#jharudar-export-customers' ).on( 'click', function() {
				self.exportCustomers();
			} );

			// Anonymize customers.
			$( '#jharudar-anonymize-customers' ).on( 'click', function() {
				self.showCustomerAnonymizeModal();
			} );

			// Delete customers.
			$( '#jharudar-delete-customers' ).on( 'click', function() {
				self.showCustomerDeleteModal();
			} );

			// Customer delete confirmation.
			$( '#jharudar-confirm-customer-delete-input' ).on( 'input', function() {
				var value = $( this ).val().trim().toUpperCase();
				var backupChecked = $( '#jharudar-confirm-customer-backup' ).is( ':checked' );
				$( '#jharudar-confirm-customer-delete' ).prop( 'disabled', value !== 'DELETE' || ! backupChecked );
			} );

			$( '#jharudar-confirm-customer-backup' ).on( 'change', function() {
				var value = $( '#jharudar-confirm-customer-delete-input' ).val().trim().toUpperCase();
				var backupChecked = $( this ).is( ':checked' );
				$( '#jharudar-confirm-customer-delete' ).prop( 'disabled', value !== 'DELETE' || ! backupChecked );
			} );

			$( '#jharudar-cancel-customer-delete' ).on( 'click', function() {
				self.closeModal( $( '#jharudar-customer-delete-modal' ) );
			} );

			$( '#jharudar-confirm-customer-delete' ).on( 'click', function() {
				self.deleteCustomers();
			} );

			// Customer anonymize confirmation.
			$( '#jharudar-confirm-customer-anonymize-input' ).on( 'input', function() {
				var value = $( this ).val().trim().toUpperCase();
				$( '#jharudar-confirm-customer-anonymize' ).prop( 'disabled', value !== 'ANONYMIZE' );
			} );

			$( '#jharudar-cancel-customer-anonymize' ).on( 'click', function() {
				self.closeModal( $( '#jharudar-customer-anonymize-modal' ) );
			} );

			$( '#jharudar-confirm-customer-anonymize' ).on( 'click', function() {
				self.anonymizeCustomers();
			} );

			// Load more customers.
			$( '#jharudar-load-more-customers' ).on( 'click', function() {
				self.loadCustomers( true );
			} );
		},

		/**
		 * Load products via AJAX.
		 */
		loadProducts: function( append ) {
			var self = this;
			var $container = $( '#jharudar-products-results' );
			var $pagination = $( '#jharudar-products-pagination' );

			if ( ! append ) {
				$container.html( '<div class="jharudar-loading"><span class="spinner is-active"></span> Loading...</div>' );
				this.currentItems = [];
			}

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_products',
					nonce: jharudar_admin.nonce,
					category: $( '#jharudar-filter-category' ).val(),
					status: $( '#jharudar-filter-status' ).val(),
					stock_status: $( '#jharudar-filter-stock' ).val(),
					product_type: $( '#jharudar-filter-type' ).val(),
					date_after: $( '#jharudar-filter-date-after' ).val(),
					date_before: $( '#jharudar-filter-date-before' ).val(),
					limit: 50,
					offset: self.currentOffset
				},
				success: function( response ) {
					if ( response.success ) {
						self.currentTotal = response.data.total;
						self.currentItems = self.currentItems.concat( response.data.products );
						self.currentOffset += response.data.products.length;

						if ( response.data.products.length === 0 && ! append ) {
							$container.html( self.getEmptyState( 'archive', 'No products found matching your filters.' ) );
							$pagination.hide();
							return;
						}

						self.renderProductsTable( response.data.products, append );

						// Update pagination.
						$pagination.find( '.shown' ).text( self.currentOffset );
						$pagination.find( '.total' ).text( self.currentTotal );
						
						if ( self.currentOffset < self.currentTotal ) {
							$pagination.show();
						} else {
							$pagination.hide();
						}
					} else {
						$container.html( '<div class="notice notice-error"><p>' + ( response.data.message || 'Error loading products.' ) + '</p></div>' );
					}
				},
				error: function() {
					$container.html( '<div class="notice notice-error"><p>Error loading products. Please try again.</p></div>' );
				}
			} );
		},

		/**
		 * Render products table.
		 */
		renderProductsTable: function( products, append ) {
			var $container = $( '#jharudar-products-results' );
			var html = '';

			if ( ! append ) {
				html = '<table class="wp-list-table widefat fixed striped">' +
					'<thead><tr>' +
					'<th class="check-column"><input type="checkbox" class="jharudar-select-all-in-table" /></th>' +
					'<th>ID</th>' +
					'<th>Name</th>' +
					'<th>SKU</th>' +
					'<th>Status</th>' +
					'<th>Price</th>' +
					'<th>Stock</th>' +
					'<th>Type</th>' +
					'<th>Categories</th>' +
					'<th>Date</th>' +
					'</tr></thead><tbody>';
			}

			$.each( products, function( index, product ) {
				html += '<tr>' +
					'<td><input type="checkbox" class="jharudar-product-checkbox" value="' + product.id + '" /></td>' +
					'<td>' + product.id + '</td>' +
					'<td><a href="' + product.edit_url + '" target="_blank">' + self.escapeHtml( product.name ) + '</a></td>' +
					'<td>' + ( product.sku || '-' ) + '</td>' +
					'<td><span class="jharudar-status jharudar-status-' + product.status + '">' + product.status + '</span></td>' +
					'<td>' + product.price + '</td>' +
					'<td>' + product.stock_status + '</td>' +
					'<td>' + product.type + '</td>' +
					'<td>' + ( product.categories || '-' ) + '</td>' +
					'<td>' + product.date + '</td>' +
					'</tr>';
			} );

			if ( ! append ) {
				html += '</tbody></table>';
				$container.html( html );
			} else {
				$container.find( 'tbody' ).append( html );
			}

			// Bind select all in table.
			$( '.jharudar-select-all-in-table' ).off( 'change' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-product-checkbox' ).prop( 'checked', checked );
				$( '#jharudar-select-all-products' ).prop( 'checked', checked );
				JharudarAdmin.updateProductActionState();
			} );
		},

		/**
		 * Update product action buttons state.
		 */
		updateProductActionState: function() {
			var count = $( '.jharudar-product-checkbox:checked' ).length;
			var $countDisplay = $( '.jharudar-products-page .jharudar-selected-count' );

			$countDisplay.find( '.count' ).text( count );
			
			if ( count > 0 ) {
				$countDisplay.show();
				$( '#jharudar-export-products, #jharudar-delete-products' ).prop( 'disabled', false );
			} else {
				$countDisplay.hide();
				$( '#jharudar-export-products, #jharudar-delete-products' ).prop( 'disabled', true );
			}
		},

		/**
		 * Export products.
		 */
		exportProducts: function() {
			var ids = this.getSelectedIds( '.jharudar-product-checkbox' );
			
			if ( ids.length === 0 ) {
				alert( 'Please select at least one product to export.' );
				return;
			}

			this.doExport( 'jharudar_export_products', ids, 'product_ids' );
		},

		/**
		 * Show product delete modal.
		 */
		showProductDeleteModal: function() {
			var count = $( '.jharudar-product-checkbox:checked' ).length;
			$( '#jharudar-delete-modal .jharudar-delete-summary' ).text( 'You have selected ' + count + ' product(s) for deletion.' );
			$( '#jharudar-confirm-delete-input' ).val( '' );
			$( '#jharudar-confirm-backup' ).prop( 'checked', false );
			$( '#jharudar-confirm-delete' ).prop( 'disabled', true );
			$( '#jharudar-delete-modal' ).addClass( 'active' );
		},

		/**
		 * Delete products.
		 */
		deleteProducts: function() {
			var self = this;
			var ids = this.getSelectedIds( '.jharudar-product-checkbox' );
			var deleteImages = $( '#jharudar-delete-images' ).is( ':checked' );

			this.closeModal( $( '#jharudar-delete-modal' ) );
			this.showProgress( 'jharudar-products-progress' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_delete_products',
					nonce: jharudar_admin.nonce,
					product_ids: ids,
					delete_action: 'delete',
					delete_images: deleteImages ? 'true' : 'false'
				},
				success: function( response ) {
					self.hideProgress( 'jharudar-products-progress' );
					if ( response.success ) {
						var msg = 'Deleted ' + response.data.deleted + ' product(s).';
						if ( response.data.failed > 0 ) {
							msg += ' ' + response.data.failed + ' failed.';
						}
						alert( msg );
						self.currentOffset = 0;
						self.loadProducts();
					} else {
						alert( response.data.message || 'Error deleting products.' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-products-progress' );
					alert( 'Error deleting products. Please try again.' );
				}
			} );
		},

		/**
		 * Scan orphaned images.
		 */
		scanOrphanedImages: function() {
			var self = this;
			var $container = $( '#jharudar-orphaned-images-results' );

			$container.html( '<div class="jharudar-loading"><span class="spinner is-active"></span> Scanning for orphaned images...</div>' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_orphaned_images',
					nonce: jharudar_admin.nonce,
					limit: 100,
					offset: 0
				},
				success: function( response ) {
					if ( response.success && response.data.images.length > 0 ) {
						self.renderOrphanedImages( response.data.images );
					} else {
						$container.html( self.getEmptyState( 'format-gallery', 'No orphaned images found. Your product images are all in use.' ) );
					}
				},
				error: function() {
					$container.html( '<div class="notice notice-error"><p>Error scanning for orphaned images.</p></div>' );
				}
			} );
		},

		/**
		 * Render orphaned images.
		 */
		renderOrphanedImages: function( images ) {
			var html = '<div class="jharudar-images-grid">';

			$.each( images, function( index, image ) {
				html += '<div class="jharudar-image-item">' +
					'<input type="checkbox" class="jharudar-image-checkbox" value="' + image.id + '" />' +
					'<img src="' + image.thumbnail + '" alt="' + self.escapeHtml( image.title ) + '" />' +
					'<div class="jharudar-image-info">' +
					'<span class="jharudar-image-title">' + self.escapeHtml( image.title ) + '</span>' +
					'<span class="jharudar-image-size">' + image.size + '</span>' +
					'</div>' +
					'</div>';
			} );

			html += '</div>';
			$( '#jharudar-orphaned-images-results' ).html( html );
		},

		/**
		 * Update image action state.
		 */
		updateImageActionState: function() {
			var count = $( '.jharudar-image-checkbox:checked' ).length;
			var $countDisplay = $( '.jharudar-products-page .jharudar-selected-count' );

			$countDisplay.find( '.count' ).text( count );
			
			if ( count > 0 ) {
				$countDisplay.show();
				$( '#jharudar-delete-orphaned-images' ).prop( 'disabled', false );
			} else {
				$countDisplay.hide();
				$( '#jharudar-delete-orphaned-images' ).prop( 'disabled', true );
			}
		},

		/**
		 * Delete orphaned images.
		 */
		deleteOrphanedImages: function() {
			var self = this;
			var ids = this.getSelectedIds( '.jharudar-image-checkbox' );

			if ( ! confirm( 'Are you sure you want to delete ' + ids.length + ' orphaned image(s)? This cannot be undone.' ) ) {
				return;
			}

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_delete_orphaned_images',
					nonce: jharudar_admin.nonce,
					image_ids: ids
				},
				success: function( response ) {
					if ( response.success ) {
						alert( 'Deleted ' + response.data.deleted + ' image(s).' );
						self.scanOrphanedImages();
					} else {
						alert( response.data.message || 'Error deleting images.' );
					}
				},
				error: function() {
					alert( 'Error deleting images. Please try again.' );
				}
			} );
		},

		/**
		 * Load orders via AJAX.
		 */
		loadOrders: function( append ) {
			var self = this;
			var $container = $( '#jharudar-orders-results' );
			var $pagination = $( '#jharudar-orders-pagination' );

			if ( ! append ) {
				$container.html( '<div class="jharudar-loading"><span class="spinner is-active"></span> Loading...</div>' );
				this.currentItems = [];
			}

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_orders',
					nonce: jharudar_admin.nonce,
					status: $( '#jharudar-filter-order-status' ).val(),
					payment_method: $( '#jharudar-filter-payment-method' ).val(),
					date_after: $( '#jharudar-filter-order-date-after' ).val(),
					date_before: $( '#jharudar-filter-order-date-before' ).val(),
					limit: 50,
					offset: self.currentOffset
				},
				success: function( response ) {
					if ( response.success ) {
						self.currentTotal = response.data.total;
						self.currentItems = self.currentItems.concat( response.data.orders );
						self.currentOffset += response.data.orders.length;

						if ( response.data.orders.length === 0 && ! append ) {
							$container.html( self.getEmptyState( 'cart', 'No orders found matching your filters.' ) );
							$pagination.hide();
							return;
						}

						self.renderOrdersTable( response.data.orders, append );

						$pagination.find( '.shown' ).text( self.currentOffset );
						$pagination.find( '.total' ).text( self.currentTotal );
						
						if ( self.currentOffset < self.currentTotal ) {
							$pagination.show();
						} else {
							$pagination.hide();
						}
					} else {
						$container.html( '<div class="notice notice-error"><p>' + ( response.data.message || 'Error loading orders.' ) + '</p></div>' );
					}
				},
				error: function() {
					$container.html( '<div class="notice notice-error"><p>Error loading orders. Please try again.</p></div>' );
				}
			} );
		},

		/**
		 * Render orders table.
		 */
		renderOrdersTable: function( orders, append ) {
			var $container = $( '#jharudar-orders-results' );
			var html = '';

			if ( ! append ) {
				html = '<table class="wp-list-table widefat fixed striped">' +
					'<thead><tr>' +
					'<th class="check-column"><input type="checkbox" class="jharudar-select-all-in-table" /></th>' +
					'<th>Order</th>' +
					'<th>Status</th>' +
					'<th>Date</th>' +
					'<th>Customer</th>' +
					'<th>Email</th>' +
					'<th>Total</th>' +
					'<th>Payment</th>' +
					'<th>Items</th>' +
					'</tr></thead><tbody>';
			}

			$.each( orders, function( index, order ) {
				html += '<tr>' +
					'<td><input type="checkbox" class="jharudar-order-checkbox" value="' + order.id + '" /></td>' +
					'<td><a href="' + order.edit_url + '" target="_blank">#' + order.order_number + '</a></td>' +
					'<td><span class="jharudar-status jharudar-status-' + order.status_key + '">' + order.status + '</span></td>' +
					'<td>' + order.date + '</td>' +
					'<td>' + self.escapeHtml( order.customer ) + '</td>' +
					'<td>' + order.customer_email + '</td>' +
					'<td>' + order.total + '</td>' +
					'<td>' + ( order.payment_method || '-' ) + '</td>' +
					'<td>' + order.items_count + '</td>' +
					'</tr>';
			} );

			if ( ! append ) {
				html += '</tbody></table>';
				$container.html( html );
			} else {
				$container.find( 'tbody' ).append( html );
			}

			$( '.jharudar-select-all-in-table' ).off( 'change' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-order-checkbox' ).prop( 'checked', checked );
				$( '#jharudar-select-all-orders' ).prop( 'checked', checked );
				JharudarAdmin.updateOrderActionState();
			} );
		},

		/**
		 * Update order action buttons state.
		 */
		updateOrderActionState: function() {
			var count = $( '.jharudar-order-checkbox:checked' ).length;
			var $countDisplay = $( '.jharudar-orders-page .jharudar-selected-count' );

			$countDisplay.find( '.count' ).text( count );
			
			if ( count > 0 ) {
				$countDisplay.show();
				$( '#jharudar-export-orders, #jharudar-anonymize-orders, #jharudar-delete-orders' ).prop( 'disabled', false );
			} else {
				$countDisplay.hide();
				$( '#jharudar-export-orders, #jharudar-anonymize-orders, #jharudar-delete-orders' ).prop( 'disabled', true );
			}
		},

		/**
		 * Export orders.
		 */
		exportOrders: function() {
			var ids = this.getSelectedIds( '.jharudar-order-checkbox' );
			
			if ( ids.length === 0 ) {
				alert( 'Please select at least one order to export.' );
				return;
			}

			this.doExport( 'jharudar_export_orders', ids, 'order_ids' );
		},

		/**
		 * Show order delete modal.
		 */
		showOrderDeleteModal: function() {
			var count = $( '.jharudar-order-checkbox:checked' ).length;
			$( '#jharudar-order-delete-modal .jharudar-delete-summary' ).text( 'You have selected ' + count + ' order(s) for deletion.' );
			$( '#jharudar-confirm-order-delete-input' ).val( '' );
			$( '#jharudar-confirm-order-backup' ).prop( 'checked', false );
			$( '#jharudar-confirm-order-delete' ).prop( 'disabled', true );
			$( '#jharudar-order-delete-modal' ).addClass( 'active' );
		},

		/**
		 * Show order anonymize modal.
		 */
		showOrderAnonymizeModal: function() {
			var count = $( '.jharudar-order-checkbox:checked' ).length;
			$( '#jharudar-order-anonymize-modal .jharudar-anonymize-summary' ).text( 'You have selected ' + count + ' order(s) for anonymization.' );
			$( '#jharudar-confirm-anonymize-input' ).val( '' );
			$( '#jharudar-confirm-anonymize' ).prop( 'disabled', true );
			$( '#jharudar-order-anonymize-modal' ).addClass( 'active' );
		},

		/**
		 * Delete orders.
		 */
		deleteOrders: function() {
			var self = this;
			var ids = this.getSelectedIds( '.jharudar-order-checkbox' );

			this.closeModal( $( '#jharudar-order-delete-modal' ) );
			this.showProgress( 'jharudar-orders-progress' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_delete_orders',
					nonce: jharudar_admin.nonce,
					order_ids: ids,
					delete_action: 'delete'
				},
				success: function( response ) {
					self.hideProgress( 'jharudar-orders-progress' );
					if ( response.success ) {
						alert( 'Deleted ' + response.data.deleted + ' order(s).' );
						self.currentOffset = 0;
						self.loadOrders();
					} else {
						alert( response.data.message || 'Error deleting orders.' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-orders-progress' );
					alert( 'Error deleting orders. Please try again.' );
				}
			} );
		},

		/**
		 * Anonymize orders.
		 */
		anonymizeOrders: function() {
			var self = this;
			var ids = this.getSelectedIds( '.jharudar-order-checkbox' );

			this.closeModal( $( '#jharudar-order-anonymize-modal' ) );
			this.showProgress( 'jharudar-orders-progress' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_anonymize_orders',
					nonce: jharudar_admin.nonce,
					order_ids: ids
				},
				success: function( response ) {
					self.hideProgress( 'jharudar-orders-progress' );
					if ( response.success ) {
						alert( 'Anonymized ' + response.data.anonymized + ' order(s).' );
						self.currentOffset = 0;
						self.loadOrders();
					} else {
						alert( response.data.message || 'Error anonymizing orders.' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-orders-progress' );
					alert( 'Error anonymizing orders. Please try again.' );
				}
			} );
		},

		/**
		 * Load customers via AJAX.
		 */
		loadCustomers: function( append ) {
			var self = this;
			var $container = $( '#jharudar-customers-results' );
			var $pagination = $( '#jharudar-customers-pagination' );

			if ( ! append ) {
				$container.html( '<div class="jharudar-loading"><span class="spinner is-active"></span> Loading...</div>' );
				this.currentItems = [];
			}

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_customers',
					nonce: jharudar_admin.nonce,
					filter_type: $( '#jharudar-filter-customer-type' ).val(),
					inactive_months: $( '#jharudar-filter-inactive-months' ).val(),
					date_before: $( '#jharudar-filter-customer-date' ).val(),
					limit: 50,
					offset: self.currentOffset
				},
				success: function( response ) {
					if ( response.success ) {
						self.currentTotal = response.data.total;
						self.currentItems = self.currentItems.concat( response.data.customers );
						self.currentOffset += response.data.customers.length;

						if ( response.data.customers.length === 0 && ! append ) {
							$container.html( self.getEmptyState( 'groups', 'No customers found matching your filters.' ) );
							$pagination.hide();
							return;
						}

						self.renderCustomersTable( response.data.customers, append );

						$pagination.find( '.shown' ).text( self.currentOffset );
						$pagination.find( '.total' ).text( self.currentTotal );
						
						if ( self.currentOffset < self.currentTotal ) {
							$pagination.show();
						} else {
							$pagination.hide();
						}
					} else {
						$container.html( '<div class="notice notice-error"><p>' + ( response.data.message || 'Error loading customers.' ) + '</p></div>' );
					}
				},
				error: function() {
					$container.html( '<div class="notice notice-error"><p>Error loading customers. Please try again.</p></div>' );
				}
			} );
		},

		/**
		 * Render customers table.
		 */
		renderCustomersTable: function( customers, append ) {
			var $container = $( '#jharudar-customers-results' );
			var html = '';

			if ( ! append ) {
				html = '<table class="wp-list-table widefat fixed striped">' +
					'<thead><tr>' +
					'<th class="check-column"><input type="checkbox" class="jharudar-select-all-in-table" /></th>' +
					'<th>ID</th>' +
					'<th>Name</th>' +
					'<th>Email</th>' +
					'<th>Registered</th>' +
					'<th>Orders</th>' +
					'<th>Total Spent</th>' +
					'<th>Last Order</th>' +
					'</tr></thead><tbody>';
			}

			$.each( customers, function( index, customer ) {
				html += '<tr>' +
					'<td><input type="checkbox" class="jharudar-customer-checkbox" value="' + customer.id + '" /></td>' +
					'<td>' + customer.id + '</td>' +
					'<td><a href="' + customer.edit_url + '" target="_blank">' + self.escapeHtml( customer.name ) + '</a></td>' +
					'<td>' + customer.email + '</td>' +
					'<td>' + customer.date_registered + '</td>' +
					'<td>' + customer.orders_count + '</td>' +
					'<td>' + customer.total_spent + '</td>' +
					'<td>' + customer.last_order_date + '</td>' +
					'</tr>';
			} );

			if ( ! append ) {
				html += '</tbody></table>';
				$container.html( html );
			} else {
				$container.find( 'tbody' ).append( html );
			}

			$( '.jharudar-select-all-in-table' ).off( 'change' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-customer-checkbox' ).prop( 'checked', checked );
				$( '#jharudar-select-all-customers' ).prop( 'checked', checked );
				JharudarAdmin.updateCustomerActionState();
			} );
		},

		/**
		 * Update customer action buttons state.
		 */
		updateCustomerActionState: function() {
			var count = $( '.jharudar-customer-checkbox:checked' ).length;
			var $countDisplay = $( '.jharudar-customers-page .jharudar-selected-count' );

			$countDisplay.find( '.count' ).text( count );
			
			if ( count > 0 ) {
				$countDisplay.show();
				$( '#jharudar-export-customers, #jharudar-anonymize-customers, #jharudar-delete-customers' ).prop( 'disabled', false );
			} else {
				$countDisplay.hide();
				$( '#jharudar-export-customers, #jharudar-anonymize-customers, #jharudar-delete-customers' ).prop( 'disabled', true );
			}
		},

		/**
		 * Export customers.
		 */
		exportCustomers: function() {
			var ids = this.getSelectedIds( '.jharudar-customer-checkbox' );
			
			if ( ids.length === 0 ) {
				alert( 'Please select at least one customer to export.' );
				return;
			}

			this.doExport( 'jharudar_export_customers', ids, 'customer_ids' );
		},

		/**
		 * Show customer delete modal.
		 */
		showCustomerDeleteModal: function() {
			var count = $( '.jharudar-customer-checkbox:checked' ).length;
			$( '#jharudar-customer-delete-modal .jharudar-delete-summary' ).text( 'You have selected ' + count + ' customer(s) for deletion.' );
			$( '#jharudar-confirm-customer-delete-input' ).val( '' );
			$( '#jharudar-confirm-customer-backup' ).prop( 'checked', false );
			$( '#jharudar-confirm-customer-delete' ).prop( 'disabled', true );
			$( '#jharudar-customer-delete-modal' ).addClass( 'active' );
		},

		/**
		 * Show customer anonymize modal.
		 */
		showCustomerAnonymizeModal: function() {
			var count = $( '.jharudar-customer-checkbox:checked' ).length;
			$( '#jharudar-customer-anonymize-modal .jharudar-anonymize-summary' ).text( 'You have selected ' + count + ' customer(s) for anonymization.' );
			$( '#jharudar-confirm-customer-anonymize-input' ).val( '' );
			$( '#jharudar-confirm-customer-anonymize' ).prop( 'disabled', true );
			$( '#jharudar-customer-anonymize-modal' ).addClass( 'active' );
		},

		/**
		 * Delete customers.
		 */
		deleteCustomers: function() {
			var self = this;
			var ids = this.getSelectedIds( '.jharudar-customer-checkbox' );

			this.closeModal( $( '#jharudar-customer-delete-modal' ) );
			this.showProgress( 'jharudar-customers-progress' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_delete_customers',
					nonce: jharudar_admin.nonce,
					customer_ids: ids
				},
				success: function( response ) {
					self.hideProgress( 'jharudar-customers-progress' );
					if ( response.success ) {
						var msg = 'Deleted ' + response.data.deleted + ' customer(s).';
						if ( response.data.skipped > 0 ) {
							msg += ' ' + response.data.skipped + ' skipped (admin/manager accounts).';
						}
						alert( msg );
						self.currentOffset = 0;
						self.loadCustomers();
					} else {
						alert( response.data.message || 'Error deleting customers.' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-customers-progress' );
					alert( 'Error deleting customers. Please try again.' );
				}
			} );
		},

		/**
		 * Anonymize customers.
		 */
		anonymizeCustomers: function() {
			var self = this;
			var ids = this.getSelectedIds( '.jharudar-customer-checkbox' );

			this.closeModal( $( '#jharudar-customer-anonymize-modal' ) );
			this.showProgress( 'jharudar-customers-progress' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_anonymize_customers',
					nonce: jharudar_admin.nonce,
					customer_ids: ids
				},
				success: function( response ) {
					self.hideProgress( 'jharudar-customers-progress' );
					if ( response.success ) {
						var msg = 'Anonymized ' + response.data.anonymized + ' customer(s).';
						if ( response.data.skipped > 0 ) {
							msg += ' ' + response.data.skipped + ' skipped (admin/manager accounts).';
						}
						alert( msg );
						self.currentOffset = 0;
						self.loadCustomers();
					} else {
						alert( response.data.message || 'Error anonymizing customers.' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-customers-progress' );
					alert( 'Error anonymizing customers. Please try again.' );
				}
			} );
		},

		/**
		 * Generic export function.
		 */
		doExport: function( action, ids, idsKey ) {
			var self = this;
			var data = {
				action: action,
				nonce: jharudar_admin.nonce,
				format: 'csv'
			};
			data[ idsKey ] = ids;

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: data,
				success: function( response ) {
					if ( response.success && response.data.file_url ) {
						window.location.href = response.data.file_url;
					} else {
						alert( response.data.message || 'Export failed.' );
					}
				},
				error: function() {
					alert( 'Export failed. Please try again.' );
				}
			} );
		},

		/**
		 * Get selected item IDs.
		 */
		getSelectedIds: function( selector ) {
			var ids = [];
			$( selector + ':checked' ).each( function() {
				ids.push( $( this ).val() );
			} );
			return ids;
		},

		/**
		 * Close modal.
		 */
		closeModal: function( $modal ) {
			$modal.removeClass( 'active' );
		},

		/**
		 * Show progress bar.
		 */
		showProgress: function( id ) {
			$( '#' + id ).addClass( 'active' );
			$( '#' + id + ' .jharudar-progress-fill' ).css( 'width', '30%' );
		},

		/**
		 * Hide progress bar.
		 */
		hideProgress: function( id ) {
			$( '#' + id ).removeClass( 'active' );
			$( '#' + id + ' .jharudar-progress-fill' ).css( 'width', '0' );
		},

		/**
		 * Get empty state HTML.
		 */
		getEmptyState: function( icon, message ) {
			return '<div class="jharudar-empty-state">' +
				'<span class="dashicons dashicons-' + icon + '"></span>' +
				'<p>' + message + '</p>' +
				'</div>';
		},

		/**
		 * Escape HTML special characters.
		 */
		escapeHtml: function( text ) {
			if ( ! text ) return '';
			var div = document.createElement( 'div' );
			div.appendChild( document.createTextNode( text ) );
			return div.innerHTML;
		}
	};

	// Make escapeHtml available in the global scope for use in table rendering.
	var self = JharudarAdmin;

	// Initialize on document ready.
	$( document ).ready( function() {
		JharudarAdmin.init();
	} );

} )( jQuery );
