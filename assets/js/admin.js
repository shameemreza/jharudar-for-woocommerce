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
			this.bindCouponEvents();
			this.bindTaxonomyEvents();
			this.bindTaxRateEvents();
			this.bindShippingEvents();
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

			// Load stats and data for coupons page.
			if ( $( '.jharudar-coupons-page' ).length ) {
				self.loadCouponStats();
				if ( $( '#jharudar-coupons-results' ).length ) {
					self.currentOffset = 0;
					self.loadCoupons();
				}
			}

			// Load stats and data for taxonomy page.
			if ( $( '.jharudar-taxonomy-page' ).length ) {
				self.loadTaxonomyStats();
				// Auto-load based on active sub-tab (only one results container exists per sub-tab).
				self.currentOffset = 0;
				if ( $( '#jharudar-categories-results' ).length ) {
					self.loadTaxonomy( 'categories' );
				} else if ( $( '#jharudar-tags-results' ).length ) {
					self.loadTaxonomy( 'tags' );
				} else if ( $( '#jharudar-attributes-results' ).length ) {
					self.loadTaxonomy( 'attributes' );
				}
			}

			// Load stats and data for store page.
			if ( $( '.jharudar-store-page' ).length ) {
				self.loadStoreStats();
				// Auto-load based on active sub-tab (only one results container exists per sub-tab).
				self.currentOffset = 0;
				if ( $( '#jharudar-tax-rates-results' ).length ) {
					self.loadTaxRates();
				} else if ( $( '#jharudar-zones-results' ).length ) {
					self.loadShippingZones();
				} else if ( $( '#jharudar-shipping-classes-results' ).length ) {
					self.loadShippingClasses();
				}
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

			// Clear cache button.
			$( '#jharudar-clear-cache' ).on( 'click', function() {
				self.clearCache( $( this ) );
			} );
		},

		/**
		 * Clear plugin cache.
		 *
		 * @param {jQuery} $button The button element.
		 */
		clearCache: function( $button ) {
			var $status = $( '#jharudar-cache-status' );
			var originalText = $button.html();

			$button.prop( 'disabled', true ).html( '<span class="dashicons dashicons-update spin" style="margin-top: 3px;"></span> Clearing...' );
			$status.html( '' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_clear_cache',
					nonce: jharudar_admin.nonce
				},
				success: function( response ) {
					$button.prop( 'disabled', false ).html( originalText );
					if ( response.success ) {
						$status.html( '<span style="color: #00a32a;">&#10003; ' + response.data.message + '</span>' );
					} else {
						$status.html( '<span style="color: #d63638;">' + response.data.message + '</span>' );
					}
				},
				error: function() {
					$button.prop( 'disabled', false ).html( originalText );
					$status.html( '<span style="color: #d63638;">Failed to clear cache.</span>' );
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
		 * Bind coupon module events.
		 */
		bindCouponEvents: function() {
			var self = this;

			// Filter coupons.
			$( '#jharudar-filter-coupons' ).on( 'click', function() {
				self.currentOffset = 0;
				self.loadCoupons();
			} );

			// Reset filters.
			$( '#jharudar-reset-coupon-filters' ).on( 'click', function() {
				$( '#jharudar-filter-coupon-type' ).val( '' ).trigger( 'change' );
				$( '#jharudar-filter-coupon-date-after, #jharudar-filter-coupon-date-before' ).val( '' );
				self.currentOffset = 0;
				self.loadCoupons();
			} );

			// Select all coupons.
			$( '#jharudar-select-all-coupons' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-coupon-checkbox' ).prop( 'checked', checked );
				self.updateCouponActionState();
			} );

			$( document ).on( 'change', '.jharudar-coupon-checkbox', function() {
				self.updateCouponActionState();
			} );

			// Export coupons.
			$( '#jharudar-export-coupons' ).on( 'click', function() {
				self.exportCoupons();
			} );

			// Delete coupons.
			$( '#jharudar-delete-coupons' ).on( 'click', function() {
				self.showCouponDeleteModal();
			} );

			// Coupon delete confirmation.
			$( '#jharudar-confirm-coupon-delete-input' ).on( 'input', function() {
				var value = $( this ).val().trim().toUpperCase();
				var backupChecked = $( '#jharudar-confirm-coupon-backup' ).is( ':checked' );
				$( '#jharudar-confirm-coupon-delete' ).prop( 'disabled', value !== 'DELETE' || ! backupChecked );
			} );

			$( '#jharudar-confirm-coupon-backup' ).on( 'change', function() {
				var value = $( '#jharudar-confirm-coupon-delete-input' ).val().trim().toUpperCase();
				var backupChecked = $( this ).is( ':checked' );
				$( '#jharudar-confirm-coupon-delete' ).prop( 'disabled', value !== 'DELETE' || ! backupChecked );
			} );

			$( '#jharudar-cancel-coupon-delete' ).on( 'click', function() {
				self.closeModal( $( '#jharudar-coupon-delete-modal' ) );
			} );

			$( '#jharudar-confirm-coupon-delete' ).on( 'click', function() {
				self.deleteCoupons();
			} );

			// Load more coupons.
			$( '#jharudar-load-more-coupons' ).on( 'click', function() {
				self.loadCoupons( true );
			} );
		},

		/**
		 * Load coupon stats.
		 */
		loadCouponStats: function() {
			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_coupon_stats',
					nonce: jharudar_admin.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						$( '#jharudar-total-coupons' ).text( response.data.total );
						$( '#jharudar-expired-coupons' ).text( response.data.expired );
						$( '#jharudar-unused-coupons' ).text( response.data.unused );
						$( '#jharudar-limit-reached-coupons' ).text( response.data.limit_reached );
					}
				}
			} );
		},

		/**
		 * Load coupons via AJAX.
		 */
		loadCoupons: function( append ) {
			var self = this;
			var $container = $( '#jharudar-coupons-results' );
			var $pagination = $( '#jharudar-coupons-pagination' );

			if ( ! append ) {
				$container.html( '<div class="jharudar-loading"><span class="spinner is-active"></span> Loading...</div>' );
				this.currentItems = [];
			}

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_coupons',
					nonce: jharudar_admin.nonce,
					filter_type: $( '#jharudar-filter-coupon-type' ).val(),
					date_after: $( '#jharudar-filter-coupon-date-after' ).val(),
					date_before: $( '#jharudar-filter-coupon-date-before' ).val(),
					limit: 50,
					offset: self.currentOffset
				},
				success: function( response ) {
					if ( response.success ) {
						self.currentTotal = response.data.total;
						self.currentItems = self.currentItems.concat( response.data.coupons );
						self.currentOffset += response.data.coupons.length;

						if ( response.data.coupons.length === 0 && ! append ) {
							$container.html( self.getEmptyState( 'tickets', 'No coupons found matching your filters.' ) );
							$pagination.hide();
							return;
						}

						self.renderCouponsTable( response.data.coupons, append );

						$pagination.find( '.shown' ).text( self.currentOffset );
						$pagination.find( '.total' ).text( self.currentTotal );
						
						if ( self.currentOffset < self.currentTotal ) {
							$pagination.show();
						} else {
							$pagination.hide();
						}
					} else {
						$container.html( '<div class="notice notice-error"><p>' + ( response.data.message || 'Error loading coupons.' ) + '</p></div>' );
					}
				},
				error: function() {
					$container.html( '<div class="notice notice-error"><p>Error loading coupons. Please try again.</p></div>' );
				}
			} );
		},

		/**
		 * Render coupons table.
		 */
		renderCouponsTable: function( coupons, append ) {
			var $container = $( '#jharudar-coupons-results' );
			var html = '';

			if ( ! append ) {
				html = '<table class="wp-list-table widefat fixed striped">' +
					'<thead><tr>' +
					'<th class="check-column"><input type="checkbox" class="jharudar-select-all-in-table" /></th>' +
					'<th>ID</th>' +
					'<th>Code</th>' +
					'<th>Type</th>' +
					'<th>Amount</th>' +
					'<th>Usage</th>' +
					'<th>Expiry</th>' +
					'<th>Status</th>' +
					'<th>Date</th>' +
					'</tr></thead><tbody>';
			}

			$.each( coupons, function( index, coupon ) {
				var statusClass = coupon.status === 'expired' ? 'jharudar-status-expired' : 
								  coupon.status === 'active' ? 'jharudar-status-publish' : 'jharudar-status-draft';
				html += '<tr>' +
					'<td><input type="checkbox" class="jharudar-coupon-checkbox" value="' + coupon.id + '" /></td>' +
					'<td>' + coupon.id + '</td>' +
					'<td><a href="' + coupon.edit_url + '" target="_blank"><code>' + self.escapeHtml( coupon.code ) + '</code></a></td>' +
					'<td>' + coupon.discount_type + '</td>' +
					'<td>' + coupon.amount + '</td>' +
					'<td>' + coupon.usage_count + ( coupon.usage_limit ? '/' + coupon.usage_limit : '' ) + '</td>' +
					'<td>' + ( coupon.expiry_date || '-' ) + '</td>' +
					'<td><span class="jharudar-status ' + statusClass + '">' + coupon.status + '</span></td>' +
					'<td>' + coupon.date + '</td>' +
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
				$( '.jharudar-coupon-checkbox' ).prop( 'checked', checked );
				$( '#jharudar-select-all-coupons' ).prop( 'checked', checked );
				JharudarAdmin.updateCouponActionState();
			} );
		},

		/**
		 * Update coupon action buttons state.
		 */
		updateCouponActionState: function() {
			var count = $( '.jharudar-coupon-checkbox:checked' ).length;
			var $countDisplay = $( '.jharudar-coupons-page .jharudar-selected-count' );

			$countDisplay.find( '.count' ).text( count );
			
			if ( count > 0 ) {
				$countDisplay.show();
				$( '#jharudar-export-coupons, #jharudar-delete-coupons' ).prop( 'disabled', false );
			} else {
				$countDisplay.hide();
				$( '#jharudar-export-coupons, #jharudar-delete-coupons' ).prop( 'disabled', true );
			}
		},

		/**
		 * Export coupons.
		 */
		exportCoupons: function() {
			var ids = this.getSelectedIds( '.jharudar-coupon-checkbox' );
			
			if ( ids.length === 0 ) {
				alert( 'Please select at least one coupon to export.' );
				return;
			}

			this.doExport( 'jharudar_export_coupons', ids, 'coupon_ids' );
		},

		/**
		 * Show coupon delete modal.
		 */
		showCouponDeleteModal: function() {
			var count = $( '.jharudar-coupon-checkbox:checked' ).length;
			$( '#jharudar-coupon-delete-modal .jharudar-delete-summary' ).text( 'You have selected ' + count + ' coupon(s) for deletion.' );
			$( '#jharudar-confirm-coupon-delete-input' ).val( '' );
			$( '#jharudar-confirm-coupon-backup' ).prop( 'checked', false );
			$( '#jharudar-confirm-coupon-delete' ).prop( 'disabled', true );
			$( '#jharudar-coupon-delete-modal' ).addClass( 'active' );
		},

		/**
		 * Delete coupons.
		 */
		deleteCoupons: function() {
			var self = this;
			var ids = this.getSelectedIds( '.jharudar-coupon-checkbox' );

			this.closeModal( $( '#jharudar-coupon-delete-modal' ) );
			this.showProgress( 'jharudar-coupons-progress' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_delete_coupons',
					nonce: jharudar_admin.nonce,
					coupon_ids: ids
				},
				success: function( response ) {
					self.hideProgress( 'jharudar-coupons-progress' );
					if ( response.success ) {
						alert( 'Deleted ' + response.data.deleted + ' coupon(s).' );
						self.currentOffset = 0;
						self.loadCoupons();
						self.loadCouponStats();
					} else {
						alert( response.data.message || 'Error deleting coupons.' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-coupons-progress' );
					alert( 'Error deleting coupons. Please try again.' );
				}
			} );
		},

		/**
		 * Bind taxonomy module events.
		 */
		bindTaxonomyEvents: function() {
			var self = this;

			// Categories tab.
			$( '#jharudar-filter-categories' ).on( 'click', function() {
				self.currentOffset = 0;
				self.loadTaxonomy( 'categories' );
			} );

			$( '#jharudar-reset-category-filters' ).on( 'click', function() {
				$( '#jharudar-filter-category-type' ).val( '' ).trigger( 'change' );
				self.currentOffset = 0;
				self.loadTaxonomy( 'categories' );
			} );

			$( '#jharudar-select-all-categories' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-category-checkbox' ).prop( 'checked', checked );
				self.updateTaxonomyActionState( 'categories' );
			} );

			$( document ).on( 'change', '.jharudar-category-checkbox', function() {
				self.updateTaxonomyActionState( 'categories' );
			} );

			$( '#jharudar-delete-categories' ).on( 'click', function() {
				self.showTaxonomyDeleteModal( 'categories' );
			} );

			$( '#jharudar-load-more-categories' ).on( 'click', function() {
				self.loadTaxonomy( 'categories', true );
			} );

			// Tags tab.
			$( '#jharudar-filter-tags' ).on( 'click', function() {
				self.currentOffset = 0;
				self.loadTaxonomy( 'tags' );
			} );

			$( '#jharudar-reset-tag-filters' ).on( 'click', function() {
				$( '#jharudar-filter-tag-type' ).val( '' ).trigger( 'change' );
				self.currentOffset = 0;
				self.loadTaxonomy( 'tags' );
			} );

			$( '#jharudar-select-all-tags' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-tag-checkbox' ).prop( 'checked', checked );
				self.updateTaxonomyActionState( 'tags' );
			} );

			$( document ).on( 'change', '.jharudar-tag-checkbox', function() {
				self.updateTaxonomyActionState( 'tags' );
			} );

			$( '#jharudar-delete-tags' ).on( 'click', function() {
				self.showTaxonomyDeleteModal( 'tags' );
			} );

			$( '#jharudar-load-more-tags' ).on( 'click', function() {
				self.loadTaxonomy( 'tags', true );
			} );

			// Attributes tab.
			$( '#jharudar-filter-attributes' ).on( 'click', function() {
				self.currentOffset = 0;
				self.loadTaxonomy( 'attributes' );
			} );

			$( '#jharudar-reset-attribute-filters' ).on( 'click', function() {
				$( '#jharudar-filter-attribute-type' ).val( '' ).trigger( 'change' );
				self.currentOffset = 0;
				self.loadTaxonomy( 'attributes' );
			} );

			$( '#jharudar-select-all-attributes' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-attribute-checkbox' ).prop( 'checked', checked );
				self.updateTaxonomyActionState( 'attributes' );
			} );

			$( document ).on( 'change', '.jharudar-attribute-checkbox', function() {
				self.updateTaxonomyActionState( 'attributes' );
			} );

			$( '#jharudar-delete-attributes' ).on( 'click', function() {
				self.showTaxonomyDeleteModal( 'attributes' );
			} );

			$( '#jharudar-load-more-attributes' ).on( 'click', function() {
				self.loadTaxonomy( 'attributes', true );
			} );

			// Taxonomy delete confirmation modal.
			$( '#jharudar-confirm-taxonomy-delete-input' ).on( 'input', function() {
				var value = $( this ).val().trim().toUpperCase();
				$( '#jharudar-confirm-taxonomy-delete' ).prop( 'disabled', value !== 'DELETE' );
			} );

			$( '#jharudar-cancel-taxonomy-delete' ).on( 'click', function() {
				self.closeModal( $( '#jharudar-taxonomy-delete-modal' ) );
			} );

			$( '#jharudar-confirm-taxonomy-delete' ).on( 'click', function() {
				self.deleteTaxonomyItems();
			} );
		},

		/**
		 * Load taxonomy stats.
		 */
		loadTaxonomyStats: function() {
			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_taxonomy_stats',
					nonce: jharudar_admin.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						$( '#jharudar-total-categories' ).text( response.data.total_categories );
						$( '#jharudar-empty-categories' ).text( response.data.empty_categories );
						$( '#jharudar-total-tags' ).text( response.data.total_tags );
						$( '#jharudar-unused-tags' ).text( response.data.unused_tags );
						$( '#jharudar-total-attributes' ).text( response.data.total_attributes );
						$( '#jharudar-unused-attributes' ).text( response.data.unused_attributes );
					}
				}
			} );
		},

		/**
		 * Current taxonomy type being edited.
		 */
		currentTaxonomyType: '',

		/**
		 * Load taxonomy items via AJAX.
		 */
		loadTaxonomy: function( type, append ) {
			var self = this;
			var $container = $( '#jharudar-' + type + '-results' );
			var $pagination = $( '#jharudar-' + type + '-pagination' );

			if ( ! append ) {
				$container.html( '<div class="jharudar-loading"><span class="spinner is-active"></span> Loading...</div>' );
				this.currentItems = [];
			}

			var filterType = $( '#jharudar-filter-' + type.replace( 'ies', 'y' ).replace( 's', '' ) + '-type' ).val() || '';

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_taxonomy_items',
					nonce: jharudar_admin.nonce,
					taxonomy_type: type,
					filter_type: filterType,
					limit: 50,
					offset: self.currentOffset
				},
				success: function( response ) {
					if ( response.success ) {
						self.currentTotal = response.data.total;
						self.currentItems = self.currentItems.concat( response.data.items );
						self.currentOffset += response.data.items.length;

						if ( response.data.items.length === 0 && ! append ) {
							var icon = type === 'categories' ? 'category' : ( type === 'tags' ? 'tag' : 'admin-generic' );
							$container.html( self.getEmptyState( icon, 'No ' + type + ' found matching your filters.' ) );
							$pagination.hide();
							return;
						}

						self.renderTaxonomyTable( type, response.data.items, append );

						$pagination.find( '.shown' ).text( self.currentOffset );
						$pagination.find( '.total' ).text( self.currentTotal );
						
						if ( self.currentOffset < self.currentTotal ) {
							$pagination.show();
						} else {
							$pagination.hide();
						}
					} else {
						$container.html( '<div class="notice notice-error"><p>' + ( response.data.message || 'Error loading ' + type + '.' ) + '</p></div>' );
					}
				},
				error: function() {
					$container.html( '<div class="notice notice-error"><p>Error loading ' + type + '. Please try again.</p></div>' );
				}
			} );
		},

		/**
		 * Render taxonomy table.
		 */
		renderTaxonomyTable: function( type, items, append ) {
			var $container = $( '#jharudar-' + type + '-results' );
			var html = '';
			var checkboxClass = 'jharudar-' + type.replace( 'ies', 'y' ).replace( 's', '' ) + '-checkbox';

			if ( ! append ) {
				if ( type === 'attributes' ) {
					html = '<table class="wp-list-table widefat fixed striped">' +
						'<thead><tr>' +
						'<th class="check-column"><input type="checkbox" class="jharudar-select-all-in-table" /></th>' +
						'<th>ID</th>' +
						'<th>Name</th>' +
						'<th>Slug</th>' +
						'<th>Type</th>' +
						'<th>Terms</th>' +
						'<th>Status</th>' +
						'</tr></thead><tbody>';
				} else {
					html = '<table class="wp-list-table widefat fixed striped">' +
						'<thead><tr>' +
						'<th class="check-column"><input type="checkbox" class="jharudar-select-all-in-table" /></th>' +
						'<th>ID</th>' +
						'<th>Name</th>' +
						'<th>Slug</th>' +
						'<th>Products</th>' +
						'<th>Status</th>' +
						'</tr></thead><tbody>';
				}
			}

			$.each( items, function( index, item ) {
				var statusClass = item.is_empty ? 'jharudar-status-draft' : 'jharudar-status-publish';
				var statusText = item.is_empty ? 'Empty' : 'In Use';
				
				if ( type === 'attributes' ) {
					// Build terms preview HTML.
					var termsHtml = self.buildTermsPreviewHtml( item );
					
					html += '<tr>' +
						'<td><input type="checkbox" class="' + checkboxClass + '" value="' + item.id + '" /></td>' +
						'<td>' + item.id + '</td>' +
						'<td><a href="' + item.edit_url + '" target="_blank">' + self.escapeHtml( item.name ) + '</a></td>' +
						'<td>' + item.slug + '</td>' +
						'<td>' + item.type + '</td>' +
						'<td>' + termsHtml + '</td>' +
						'<td><span class="jharudar-status ' + statusClass + '">' + statusText + '</span></td>' +
						'</tr>';
				} else {
					html += '<tr>' +
						'<td><input type="checkbox" class="' + checkboxClass + '" value="' + item.id + '"' + ( item.is_default ? ' disabled title="Default category cannot be deleted"' : '' ) + ' /></td>' +
						'<td>' + item.id + '</td>' +
						'<td><a href="' + item.edit_url + '" target="_blank">' + self.escapeHtml( item.name ) + '</a></td>' +
						'<td>' + item.slug + '</td>' +
						'<td>' + item.count + '</td>' +
						'<td><span class="jharudar-status ' + statusClass + '">' + statusText + ( item.is_default ? ' (Default)' : '' ) + '</span></td>' +
						'</tr>';
				}
			} );

			if ( ! append ) {
				html += '</tbody></table>';
				$container.html( html );
			} else {
				$container.find( 'tbody' ).append( html );
			}

			$( '.jharudar-select-all-in-table' ).off( 'change' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.' + checkboxClass + ':not(:disabled)' ).prop( 'checked', checked );
				JharudarAdmin.updateTaxonomyActionState( type );
			} );
		},

		/**
		 * Build terms preview HTML for attributes.
		 *
		 * @param {Object} item Attribute item with terms_preview data.
		 * @return {string} HTML string for terms display.
		 */
		buildTermsPreviewHtml: function( item ) {
			var self = this;
			var termsCount = item.terms_count || 0;
			var preview = item.terms_preview || { terms: [], has_more: false };
			
			// If no terms, show count only.
			if ( ! preview.terms || preview.terms.length === 0 ) {
				return '<span class="jharudar-terms-count">' + termsCount + '</span>';
			}
			
			// Build preview tags.
			var tagsHtml = '';
			$.each( preview.terms, function( i, term ) {
				tagsHtml += '<span class="jharudar-term-tag">' + self.escapeHtml( term ) + '</span>';
			} );
			
			// Add "more" indicator if there are more terms.
			if ( preview.has_more && termsCount > preview.terms.length ) {
				var remaining = termsCount - preview.terms.length;
				tagsHtml += '<span class="jharudar-term-more" title="' + remaining + ' more terms">+' + remaining + '</span>';
			}
			
			return '<div class="jharudar-terms-preview">' + tagsHtml + '</div>';
		},

		/**
		 * Update taxonomy action buttons state.
		 */
		updateTaxonomyActionState: function( type ) {
			var checkboxClass = '.jharudar-' + type.replace( 'ies', 'y' ).replace( 's', '' ) + '-checkbox';
			var count = $( checkboxClass + ':checked' ).length;
			var $countDisplay = $( '.jharudar-taxonomy-page .jharudar-selected-count' );

			$countDisplay.find( '.count' ).text( count );
			
			if ( count > 0 ) {
				$countDisplay.filter( ':visible' ).show();
				$( '#jharudar-delete-' + type ).prop( 'disabled', false );
			} else {
				$countDisplay.hide();
				$( '#jharudar-delete-' + type ).prop( 'disabled', true );
			}
		},

		/**
		 * Show taxonomy delete modal.
		 */
		showTaxonomyDeleteModal: function( type ) {
			var checkboxClass = '.jharudar-' + type.replace( 'ies', 'y' ).replace( 's', '' ) + '-checkbox';
			var count = $( checkboxClass + ':checked' ).length;
			this.currentTaxonomyType = type;
			$( '#jharudar-taxonomy-delete-modal .jharudar-delete-summary' ).text( 'You have selected ' + count + ' ' + type + ' for deletion.' );
			$( '#jharudar-confirm-taxonomy-delete-input' ).val( '' );
			$( '#jharudar-confirm-taxonomy-delete' ).prop( 'disabled', true );
			$( '#jharudar-taxonomy-delete-modal' ).addClass( 'active' );
		},

		/**
		 * Delete taxonomy items.
		 */
		deleteTaxonomyItems: function() {
			var self = this;
			var type = this.currentTaxonomyType;
			var checkboxClass = '.jharudar-' + type.replace( 'ies', 'y' ).replace( 's', '' ) + '-checkbox';
			var ids = this.getSelectedIds( checkboxClass );
			var progressId = 'jharudar-' + type + '-progress';

			this.closeModal( $( '#jharudar-taxonomy-delete-modal' ) );
			this.showProgress( progressId );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_delete_taxonomy_items',
					nonce: jharudar_admin.nonce,
					taxonomy_type: type,
					item_ids: ids
				},
				success: function( response ) {
					self.hideProgress( progressId );
					if ( response.success ) {
						var msg = 'Deleted ' + response.data.deleted + ' ' + type + '.';
						if ( response.data.failed > 0 ) {
							msg += ' ' + response.data.failed + ' failed.';
						}
						alert( msg );
						self.currentOffset = 0;
						self.loadTaxonomy( type );
						self.loadTaxonomyStats();
					} else {
						alert( response.data.message || 'Error deleting ' + type + '.' );
					}
				},
				error: function() {
					self.hideProgress( progressId );
					alert( 'Error deleting ' + type + '. Please try again.' );
				}
			} );
		},

		/**
		 * Bind tax rate module events.
		 */
		bindTaxRateEvents: function() {
			var self = this;

			// Filter tax rates.
			$( '#jharudar-filter-tax-rates' ).on( 'click', function() {
				self.currentOffset = 0;
				self.loadTaxRates();
			} );

			// Reset filters.
			$( '#jharudar-reset-tax-filters' ).on( 'click', function() {
				$( '#jharudar-filter-tax-country, #jharudar-filter-tax-class' ).val( '' ).trigger( 'change' );
				self.currentOffset = 0;
				self.loadTaxRates();
			} );

			// Select all tax rates.
			$( '#jharudar-select-all-tax-rates' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-tax-rate-checkbox' ).prop( 'checked', checked );
				self.updateTaxRateActionState();
			} );

			$( document ).on( 'change', '.jharudar-tax-rate-checkbox', function() {
				self.updateTaxRateActionState();
			} );

			// Export tax rates.
			$( '#jharudar-export-tax-rates' ).on( 'click', function() {
				self.exportTaxRates();
			} );

			// Delete tax rates.
			$( '#jharudar-delete-tax-rates' ).on( 'click', function() {
				self.showStoreDeleteModal( 'tax rates' );
			} );

			// Load more tax rates.
			$( '#jharudar-load-more-tax-rates' ).on( 'click', function() {
				self.loadTaxRates( true );
			} );
		},

		/**
		 * Bind shipping module events.
		 */
		bindShippingEvents: function() {
			var self = this;

			// Shipping Zones.
			$( '#jharudar-filter-zones' ).on( 'click', function() {
				self.currentOffset = 0;
				self.loadShippingZones();
			} );

			$( '#jharudar-reset-zone-filters' ).on( 'click', function() {
				$( '#jharudar-filter-zone-type' ).val( '' ).trigger( 'change' );
				self.currentOffset = 0;
				self.loadShippingZones();
			} );

			$( '#jharudar-select-all-zones' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-zone-checkbox' ).prop( 'checked', checked );
				self.updateZoneActionState();
			} );

			$( document ).on( 'change', '.jharudar-zone-checkbox', function() {
				self.updateZoneActionState();
			} );

			$( '#jharudar-delete-zones' ).on( 'click', function() {
				self.showStoreDeleteModal( 'shipping zones' );
			} );

			$( '#jharudar-load-more-zones' ).on( 'click', function() {
				self.loadShippingZones( true );
			} );

			// Shipping Classes.
			$( '#jharudar-filter-shipping-classes' ).on( 'click', function() {
				self.currentOffset = 0;
				self.loadShippingClasses();
			} );

			$( '#jharudar-reset-class-filters' ).on( 'click', function() {
				$( '#jharudar-filter-class-type' ).val( '' ).trigger( 'change' );
				self.currentOffset = 0;
				self.loadShippingClasses();
			} );

			$( '#jharudar-select-all-shipping-classes' ).on( 'change', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-shipping-class-checkbox' ).prop( 'checked', checked );
				self.updateShippingClassActionState();
			} );

			$( document ).on( 'change', '.jharudar-shipping-class-checkbox', function() {
				self.updateShippingClassActionState();
			} );

			$( '#jharudar-delete-shipping-classes' ).on( 'click', function() {
				self.showStoreDeleteModal( 'shipping classes' );
			} );

			$( '#jharudar-load-more-shipping-classes' ).on( 'click', function() {
				self.loadShippingClasses( true );
			} );

			// Store delete confirmation modal.
			$( '#jharudar-confirm-store-delete-input' ).on( 'input', function() {
				var value = $( this ).val().trim().toUpperCase();
				var backupChecked = $( '#jharudar-confirm-store-backup' ).is( ':checked' );
				$( '#jharudar-confirm-store-delete' ).prop( 'disabled', value !== 'DELETE' || ! backupChecked );
			} );

			$( '#jharudar-confirm-store-backup' ).on( 'change', function() {
				var value = $( '#jharudar-confirm-store-delete-input' ).val().trim().toUpperCase();
				var backupChecked = $( this ).is( ':checked' );
				$( '#jharudar-confirm-store-delete' ).prop( 'disabled', value !== 'DELETE' || ! backupChecked );
			} );

			$( '#jharudar-cancel-store-delete' ).on( 'click', function() {
				self.closeModal( $( '#jharudar-store-delete-modal' ) );
			} );

			$( '#jharudar-confirm-store-delete' ).on( 'click', function() {
				self.executeStoreDelete();
			} );
		},

		/**
		 * Current store delete action type.
		 */
		currentStoreDeleteType: '',

		/**
		 * Load store stats.
		 */
		loadStoreStats: function() {
			var self = this;

			// Tax rates stats.
			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_tax_rate_stats',
					nonce: jharudar_admin.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						$( '#jharudar-total-tax-rates' ).text( response.data.total );
						$( '#jharudar-tax-countries' ).text( response.data.countries );
						$( '#jharudar-tax-classes' ).text( response.data.tax_classes );
					}
				}
			} );

			// Shipping stats.
			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_shipping_stats',
					nonce: jharudar_admin.nonce
				},
				success: function( response ) {
					if ( response.success ) {
						$( '#jharudar-total-zones' ).text( response.data.total_zones );
						$( '#jharudar-empty-zones' ).text( response.data.empty_zones );
						$( '#jharudar-total-shipping-classes' ).text( response.data.total_classes );
						$( '#jharudar-unused-shipping-classes' ).text( response.data.unused_classes );
					}
				}
			} );
		},

		/**
		 * Load tax rates via AJAX.
		 */
		loadTaxRates: function( append ) {
			var self = this;
			var $container = $( '#jharudar-tax-rates-results' );
			var $pagination = $( '#jharudar-tax-rates-pagination' );

			if ( ! append ) {
				$container.html( '<div class="jharudar-loading"><span class="spinner is-active"></span> Loading...</div>' );
				this.currentItems = [];
			}

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_tax_rates',
					nonce: jharudar_admin.nonce,
					country: $( '#jharudar-filter-tax-country' ).val(),
					tax_class: $( '#jharudar-filter-tax-class' ).val(),
					limit: 50,
					offset: self.currentOffset
				},
				success: function( response ) {
					if ( response.success ) {
						self.currentTotal = response.data.total;
						self.currentItems = self.currentItems.concat( response.data.tax_rates );
						self.currentOffset += response.data.tax_rates.length;

						if ( response.data.tax_rates.length === 0 && ! append ) {
							$container.html( self.getEmptyState( 'chart-area', 'No tax rates found matching your filters.' ) );
							$pagination.hide();
							return;
						}

						self.renderTaxRatesTable( response.data.tax_rates, append );

						$pagination.find( '.shown' ).text( self.currentOffset );
						$pagination.find( '.total' ).text( self.currentTotal );
						
						if ( self.currentOffset < self.currentTotal ) {
							$pagination.show();
						} else {
							$pagination.hide();
						}
					} else {
						$container.html( '<div class="notice notice-error"><p>' + ( response.data.message || 'Error loading tax rates.' ) + '</p></div>' );
					}
				},
				error: function() {
					$container.html( '<div class="notice notice-error"><p>Error loading tax rates. Please try again.</p></div>' );
				}
			} );
		},

		/**
		 * Render tax rates table.
		 */
		renderTaxRatesTable: function( taxRates, append ) {
			var $container = $( '#jharudar-tax-rates-results' );
			var html = '';

			if ( ! append ) {
				html = '<table class="wp-list-table widefat fixed striped">' +
					'<thead><tr>' +
					'<th class="check-column"><input type="checkbox" class="jharudar-select-all-in-table" /></th>' +
					'<th>ID</th>' +
					'<th>Country</th>' +
					'<th>State</th>' +
					'<th>Postcode</th>' +
					'<th>City</th>' +
					'<th>Rate %</th>' +
					'<th>Name</th>' +
					'<th>Class</th>' +
					'<th>Compound</th>' +
					'<th>Shipping</th>' +
					'</tr></thead><tbody>';
			}

			$.each( taxRates, function( index, rate ) {
				html += '<tr>' +
					'<td><input type="checkbox" class="jharudar-tax-rate-checkbox" value="' + rate.id + '" /></td>' +
					'<td>' + rate.id + '</td>' +
					'<td>' + ( rate.country || '*' ) + '</td>' +
					'<td>' + ( rate.state || '*' ) + '</td>' +
					'<td>' + ( rate.postcode || '*' ) + '</td>' +
					'<td>' + ( rate.city || '*' ) + '</td>' +
					'<td>' + rate.rate + '%</td>' +
					'<td>' + self.escapeHtml( rate.name ) + '</td>' +
					'<td>' + ( rate.tax_class || 'Standard' ) + '</td>' +
					'<td>' + ( rate.compound ? 'Yes' : 'No' ) + '</td>' +
					'<td>' + ( rate.shipping ? 'Yes' : 'No' ) + '</td>' +
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
				$( '.jharudar-tax-rate-checkbox' ).prop( 'checked', checked );
				$( '#jharudar-select-all-tax-rates' ).prop( 'checked', checked );
				JharudarAdmin.updateTaxRateActionState();
			} );
		},

		/**
		 * Update tax rate action buttons state.
		 */
		updateTaxRateActionState: function() {
			var count = $( '.jharudar-tax-rate-checkbox:checked' ).length;
			var $countDisplay = $( '.jharudar-store-page .jharudar-selected-count' );

			$countDisplay.find( '.count' ).text( count );
			
			if ( count > 0 ) {
				$countDisplay.show();
				$( '#jharudar-export-tax-rates, #jharudar-delete-tax-rates' ).prop( 'disabled', false );
			} else {
				$countDisplay.hide();
				$( '#jharudar-export-tax-rates, #jharudar-delete-tax-rates' ).prop( 'disabled', true );
			}
		},

		/**
		 * Export tax rates.
		 */
		exportTaxRates: function() {
			var ids = this.getSelectedIds( '.jharudar-tax-rate-checkbox' );
			
			if ( ids.length === 0 ) {
				alert( 'Please select at least one tax rate to export.' );
				return;
			}

			this.doExport( 'jharudar_export_tax_rates', ids, 'tax_rate_ids' );
		},

		/**
		 * Load shipping zones via AJAX.
		 */
		loadShippingZones: function( append ) {
			var self = this;
			var $container = $( '#jharudar-zones-results' );
			var $pagination = $( '#jharudar-zones-pagination' );

			if ( ! append ) {
				$container.html( '<div class="jharudar-loading"><span class="spinner is-active"></span> Loading...</div>' );
				this.currentItems = [];
			}

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_shipping_zones',
					nonce: jharudar_admin.nonce,
					filter_type: $( '#jharudar-filter-zone-type' ).val(),
					limit: 50,
					offset: self.currentOffset
				},
				success: function( response ) {
					if ( response.success ) {
						self.currentTotal = response.data.total;
						self.currentItems = self.currentItems.concat( response.data.zones );
						self.currentOffset += response.data.zones.length;

						if ( response.data.zones.length === 0 && ! append ) {
							$container.html( self.getEmptyState( 'car', 'No shipping zones found matching your filters.' ) );
							$pagination.hide();
							return;
						}

						self.renderShippingZonesTable( response.data.zones, append );

						$pagination.find( '.shown' ).text( self.currentOffset );
						$pagination.find( '.total' ).text( self.currentTotal );
						
						if ( self.currentOffset < self.currentTotal ) {
							$pagination.show();
						} else {
							$pagination.hide();
						}
					} else {
						$container.html( '<div class="notice notice-error"><p>' + ( response.data.message || 'Error loading shipping zones.' ) + '</p></div>' );
					}
				},
				error: function() {
					$container.html( '<div class="notice notice-error"><p>Error loading shipping zones. Please try again.</p></div>' );
				}
			} );
		},

		/**
		 * Render shipping zones table.
		 */
		renderShippingZonesTable: function( zones, append ) {
			var $container = $( '#jharudar-zones-results' );
			var html = '';

			if ( ! append ) {
				html = '<table class="wp-list-table widefat fixed striped">' +
					'<thead><tr>' +
					'<th class="check-column"><input type="checkbox" class="jharudar-select-all-in-table" /></th>' +
					'<th>ID</th>' +
					'<th>Name</th>' +
					'<th>Regions</th>' +
					'<th>Methods</th>' +
					'<th>Status</th>' +
					'</tr></thead><tbody>';
			}

			$.each( zones, function( index, zone ) {
				var statusClass = zone.methods_count === 0 ? 'jharudar-status-draft' : 'jharudar-status-publish';
				var statusText = zone.methods_count === 0 ? 'Empty' : 'Active';
				var isProtected = zone.is_rest_of_world;

				html += '<tr>' +
					'<td><input type="checkbox" class="jharudar-zone-checkbox" value="' + zone.id + '"' + ( isProtected ? ' disabled title="Rest of the World zone cannot be deleted"' : '' ) + ' /></td>' +
					'<td>' + zone.id + '</td>' +
					'<td><a href="' + zone.edit_url + '" target="_blank">' + self.escapeHtml( zone.name ) + '</a></td>' +
					'<td>' + zone.regions + '</td>' +
					'<td>' + zone.methods_count + '</td>' +
					'<td><span class="jharudar-status ' + statusClass + '">' + statusText + ( isProtected ? ' (Protected)' : '' ) + '</span></td>' +
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
				$( '.jharudar-zone-checkbox:not(:disabled)' ).prop( 'checked', checked );
				$( '#jharudar-select-all-zones' ).prop( 'checked', checked );
				JharudarAdmin.updateZoneActionState();
			} );
		},

		/**
		 * Update zone action buttons state.
		 */
		updateZoneActionState: function() {
			var count = $( '.jharudar-zone-checkbox:checked' ).length;
			var $countDisplay = $( '.jharudar-store-page .jharudar-selected-count' );

			$countDisplay.find( '.count' ).text( count );
			
			if ( count > 0 ) {
				$countDisplay.show();
				$( '#jharudar-delete-zones' ).prop( 'disabled', false );
			} else {
				$countDisplay.hide();
				$( '#jharudar-delete-zones' ).prop( 'disabled', true );
			}
		},

		/**
		 * Load shipping classes via AJAX.
		 */
		loadShippingClasses: function( append ) {
			var self = this;
			var $container = $( '#jharudar-shipping-classes-results' );
			var $pagination = $( '#jharudar-shipping-classes-pagination' );

			if ( ! append ) {
				$container.html( '<div class="jharudar-loading"><span class="spinner is-active"></span> Loading...</div>' );
				this.currentItems = [];
			}

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_shipping_classes',
					nonce: jharudar_admin.nonce,
					filter_type: $( '#jharudar-filter-class-type' ).val(),
					limit: 50,
					offset: self.currentOffset
				},
				success: function( response ) {
					if ( response.success ) {
						self.currentTotal = response.data.total;
						self.currentItems = self.currentItems.concat( response.data.classes );
						self.currentOffset += response.data.classes.length;

						if ( response.data.classes.length === 0 && ! append ) {
							$container.html( self.getEmptyState( 'archive', 'No shipping classes found matching your filters.' ) );
							$pagination.hide();
							return;
						}

						self.renderShippingClassesTable( response.data.classes, append );

						$pagination.find( '.shown' ).text( self.currentOffset );
						$pagination.find( '.total' ).text( self.currentTotal );
						
						if ( self.currentOffset < self.currentTotal ) {
							$pagination.show();
						} else {
							$pagination.hide();
						}
					} else {
						$container.html( '<div class="notice notice-error"><p>' + ( response.data.message || 'Error loading shipping classes.' ) + '</p></div>' );
					}
				},
				error: function() {
					$container.html( '<div class="notice notice-error"><p>Error loading shipping classes. Please try again.</p></div>' );
				}
			} );
		},

		/**
		 * Render shipping classes table.
		 */
		renderShippingClassesTable: function( classes, append ) {
			var $container = $( '#jharudar-shipping-classes-results' );
			var html = '';

			if ( ! append ) {
				html = '<table class="wp-list-table widefat fixed striped">' +
					'<thead><tr>' +
					'<th class="check-column"><input type="checkbox" class="jharudar-select-all-in-table" /></th>' +
					'<th>ID</th>' +
					'<th>Name</th>' +
					'<th>Slug</th>' +
					'<th>Description</th>' +
					'<th>Products</th>' +
					'<th>Status</th>' +
					'</tr></thead><tbody>';
			}

			$.each( classes, function( index, cls ) {
				var statusClass = cls.is_used ? 'jharudar-status-publish' : 'jharudar-status-draft';
				var statusText = cls.is_used ? 'In Use' : 'Unused';

				html += '<tr>' +
					'<td><input type="checkbox" class="jharudar-shipping-class-checkbox" value="' + cls.id + '" /></td>' +
					'<td>' + cls.id + '</td>' +
					'<td>' + self.escapeHtml( cls.name ) + '</td>' +
					'<td>' + cls.slug + '</td>' +
					'<td>' + ( cls.description || '-' ) + '</td>' +
					'<td>' + cls.product_count + '</td>' +
					'<td><span class="jharudar-status ' + statusClass + '">' + statusText + '</span></td>' +
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
				$( '.jharudar-shipping-class-checkbox' ).prop( 'checked', checked );
				$( '#jharudar-select-all-shipping-classes' ).prop( 'checked', checked );
				JharudarAdmin.updateShippingClassActionState();
			} );
		},

		/**
		 * Update shipping class action buttons state.
		 */
		updateShippingClassActionState: function() {
			var count = $( '.jharudar-shipping-class-checkbox:checked' ).length;
			var $countDisplay = $( '.jharudar-store-page .jharudar-selected-count' );

			$countDisplay.find( '.count' ).text( count );
			
			if ( count > 0 ) {
				$countDisplay.show();
				$( '#jharudar-delete-shipping-classes' ).prop( 'disabled', false );
			} else {
				$countDisplay.hide();
				$( '#jharudar-delete-shipping-classes' ).prop( 'disabled', true );
			}
		},

		/**
		 * Show store delete modal.
		 */
		showStoreDeleteModal: function( itemType ) {
			var count = 0;
			
			if ( itemType === 'tax rates' ) {
				count = $( '.jharudar-tax-rate-checkbox:checked' ).length;
			} else if ( itemType === 'shipping zones' ) {
				count = $( '.jharudar-zone-checkbox:checked' ).length;
			} else if ( itemType === 'shipping classes' ) {
				count = $( '.jharudar-shipping-class-checkbox:checked' ).length;
			}

			this.currentStoreDeleteType = itemType;
			$( '#jharudar-store-delete-modal .jharudar-delete-summary' ).text( 'You have selected ' + count + ' ' + itemType + ' for deletion.' );
			$( '#jharudar-confirm-store-delete-input' ).val( '' );
			$( '#jharudar-confirm-store-backup' ).prop( 'checked', false );
			$( '#jharudar-confirm-store-delete' ).prop( 'disabled', true );
			$( '#jharudar-store-delete-modal' ).addClass( 'active' );
		},

		/**
		 * Execute store delete action.
		 */
		executeStoreDelete: function() {
			var self = this;
			var itemType = this.currentStoreDeleteType;

			this.closeModal( $( '#jharudar-store-delete-modal' ) );

			if ( itemType === 'tax rates' ) {
				this.deleteTaxRates();
			} else if ( itemType === 'shipping zones' ) {
				this.deleteShippingZones();
			} else if ( itemType === 'shipping classes' ) {
				this.deleteShippingClasses();
			}
		},

		/**
		 * Delete tax rates.
		 */
		deleteTaxRates: function() {
			var self = this;
			var ids = this.getSelectedIds( '.jharudar-tax-rate-checkbox' );

			this.showProgress( 'jharudar-tax-rates-progress' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_delete_tax_rates',
					nonce: jharudar_admin.nonce,
					tax_rate_ids: ids
				},
				success: function( response ) {
					self.hideProgress( 'jharudar-tax-rates-progress' );
					if ( response.success ) {
						alert( 'Deleted ' + response.data.deleted + ' tax rate(s).' );
						self.currentOffset = 0;
						self.loadTaxRates();
						self.loadStoreStats();
					} else {
						alert( response.data.message || 'Error deleting tax rates.' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-tax-rates-progress' );
					alert( 'Error deleting tax rates. Please try again.' );
				}
			} );
		},

		/**
		 * Delete shipping zones.
		 */
		deleteShippingZones: function() {
			var self = this;
			var ids = this.getSelectedIds( '.jharudar-zone-checkbox' );

			this.showProgress( 'jharudar-zones-progress' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_delete_shipping_zones',
					nonce: jharudar_admin.nonce,
					zone_ids: ids
				},
				success: function( response ) {
					self.hideProgress( 'jharudar-zones-progress' );
					if ( response.success ) {
						var msg = 'Deleted ' + response.data.deleted + ' shipping zone(s).';
						if ( response.data.skipped > 0 ) {
							msg += ' ' + response.data.skipped + ' skipped (protected zones).';
						}
						alert( msg );
						self.currentOffset = 0;
						self.loadShippingZones();
						self.loadStoreStats();
					} else {
						alert( response.data.message || 'Error deleting shipping zones.' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-zones-progress' );
					alert( 'Error deleting shipping zones. Please try again.' );
				}
			} );
		},

		/**
		 * Delete shipping classes.
		 */
		deleteShippingClasses: function() {
			var self = this;
			var ids = this.getSelectedIds( '.jharudar-shipping-class-checkbox' );

			this.showProgress( 'jharudar-shipping-classes-progress' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_delete_shipping_classes',
					nonce: jharudar_admin.nonce,
					class_ids: ids
				},
				success: function( response ) {
					self.hideProgress( 'jharudar-shipping-classes-progress' );
					if ( response.success ) {
						alert( 'Deleted ' + response.data.deleted + ' shipping class(es).' );
						self.currentOffset = 0;
						self.loadShippingClasses();
						self.loadStoreStats();
					} else {
						alert( response.data.message || 'Error deleting shipping classes.' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-shipping-classes-progress' );
					alert( 'Error deleting shipping classes. Please try again.' );
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
