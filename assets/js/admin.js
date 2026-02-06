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
			this.bindDatabaseEvents();
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

			// Load stats for database page.
			if ( $( '.jharudar-database-page' ).length ) {
				self.loadDatabaseStats();
			}
		},

		/**
		 * Initialize SelectWoo on select elements.
		 */
		initSelectWoo: function() {
			if ( typeof $.fn.selectWoo !== 'function' ) {
				return;
			}
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

			$button.prop( 'disabled', true ).html( '<span class="dashicons dashicons-update spin jharudar-icon-align"></span> Clearing...' );
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
						$status.html( '<span class="jharudar-text-success">&#10003; ' + response.data.message + '</span>' );
					} else {
						$status.html( '<span class="jharudar-text-danger">' + ( response.data && response.data.message ? response.data.message : 'An error occurred.' ) + '</span>' );
					}
				},
				error: function() {
					$button.prop( 'disabled', false ).html( originalText );
					$status.html( '<span class="jharudar-text-danger">Failed to clear cache.</span>' );
				}
			} );
		},

		/**
		 * Show a WordPress-style confirmation modal.
		 *
		 * Returns a jQuery Deferred. Call .then( onConfirm ) to react.
		 *
		 * @param {Object} opts Modal options.
		 * @param {string} opts.title   Modal title text.
		 * @param {string} opts.message Body message (HTML allowed).
		 * @param {string} [opts.confirmText] Confirm button label. Default "Confirm".
		 * @param {string} [opts.cancelText]  Cancel button label. Default "Cancel".
		 * @param {boolean} [opts.destructive] Use red destructive button style. Default false.
		 * @return {jQuery.Deferred}
		 */
		showConfirmModal: function( opts ) {
			var deferred = $.Deferred();
			var title       = opts.title || 'Are you sure?';
			var message     = opts.message || '';
			var confirmText = opts.confirmText || 'Confirm';
			var cancelText  = opts.cancelText || 'Cancel';
			var destructive = opts.destructive || false;

			// Remove any existing confirm modal.
			$( '#jharudar-confirm-modal' ).remove();

			var btnClass = 'button button-primary' + ( destructive ? ' jharudar-btn-destructive' : '' );

			var html = '<div id="jharudar-confirm-modal" class="jharudar-modal-overlay">' +
				'<div class="jharudar-modal">' +
					'<div class="jharudar-modal-header">' +
						'<h3>' + title + '</h3>' +
						'<button type="button" class="jharudar-modal-close" id="jharudar-confirm-close">&times;</button>' +
					'</div>' +
					'<div class="jharudar-modal-body">' +
						'<p>' + message + '</p>' +
					'</div>' +
					'<div class="jharudar-modal-footer">' +
						'<button type="button" class="button" id="jharudar-confirm-cancel">' + cancelText + '</button>' +
						'<button type="button" class="' + btnClass + '" id="jharudar-confirm-ok">' + confirmText + '</button>' +
					'</div>' +
				'</div>' +
			'</div>';

			$( 'body' ).append( html );

			// Trigger reflow then show.
			var $overlay = $( '#jharudar-confirm-modal' );
			// Force reflow before adding class.
			$overlay[0].offsetHeight; // jshint ignore:line
			$overlay.addClass( 'active' );

			function cleanup( result ) {
				$overlay.removeClass( 'active' );
				setTimeout( function() {
					$overlay.remove();
				}, 160 );
				if ( result ) {
					deferred.resolve();
				} else {
					deferred.reject();
				}
			}

			$( '#jharudar-confirm-ok' ).on( 'click', function() {
				cleanup( true );
			} );

			$( '#jharudar-confirm-cancel, #jharudar-confirm-close' ).on( 'click', function() {
				cleanup( false );
			} );

			$overlay.on( 'click', function( e ) {
				if ( $( e.target ).hasClass( 'jharudar-modal-overlay' ) ) {
					cleanup( false );
				}
			} );

			$( document ).one( 'keyup.jharudarConfirm', function( e ) {
				if ( e.key === 'Escape' ) {
					cleanup( false );
				}
			} );

			return deferred.promise();
		},

		/**
		 * Show a WordPress-style admin notice banner.
		 *
		 * @param {string} message   Notice text.
		 * @param {string} [type]    One of: success, error, warning, info. Default "success".
		 * @param {number} [autoDismiss] Auto-dismiss after ms. 0 = manual only. Default 6000.
		 */
		showAdminNotice: function( message, type, autoDismiss ) {
			type        = type || 'success';
			autoDismiss = autoDismiss !== undefined ? autoDismiss : 6000;

			// Find the best insertion point (inside the plugin wrap, after the header).
			var $target = $( '.jharudar-module-content' ).first();
			if ( ! $target.length ) {
				$target = $( '.wrap' ).first();
			}

			var html = '<div class="jharudar-admin-notice notice-' + type + '">' +
				'<p>' + message + '</p>' +
				'<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>' +
			'</div>';

			var $notice = $( html );

			// Prepend so it appears at the top of the content area.
			$target.before( $notice );

			// Scroll to notice.
			$( 'html, body' ).animate( { scrollTop: $notice.offset().top - 50 }, 200 );

			// Dismiss handler.
			$notice.find( '.notice-dismiss' ).on( 'click', function() {
				$notice.addClass( 'jharudar-notice-dismissing' );
				setTimeout( function() {
					$notice.remove();
				}, 220 );
			} );

			// Auto-dismiss.
			if ( autoDismiss > 0 ) {
				setTimeout( function() {
					if ( $notice.length && $notice.parent().length ) {
						$notice.addClass( 'jharudar-notice-dismissing' );
						setTimeout( function() {
							$notice.remove();
						}, 220 );
					}
				}, autoDismiss );
			}
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
				this.showAdminNotice( 'Please select at least one product to export.', 'warning' );
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
						self.showAdminNotice( msg, 'success' );
						self.currentOffset = 0;
						self.loadProducts();
					} else {
						self.showAdminNotice( response.data.message || 'Error deleting products.', 'error' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-products-progress' );
					self.showAdminNotice( 'Error deleting products. Please try again.', 'error' );
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

			self.showConfirmModal( {
				title: 'Delete Orphaned Images',
				message: 'Are you sure you want to delete ' + ids.length + ' orphaned image(s)? This cannot be undone.',
				confirmText: 'Delete',
				destructive: true
			} ).then( function() {
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
							self.showAdminNotice( 'Deleted ' + response.data.deleted + ' image(s).', 'success' );
							self.scanOrphanedImages();
						} else {
							self.showAdminNotice( response.data.message || 'Error deleting images.', 'error' );
						}
					},
					error: function() {
						self.showAdminNotice( 'Error deleting images. Please try again.', 'error' );
					}
				} );
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
				this.showAdminNotice( 'Please select at least one order to export.', 'warning' );
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
						self.showAdminNotice( 'Deleted ' + response.data.deleted + ' order(s).', 'success' );
						self.currentOffset = 0;
						self.loadOrders();
					} else {
						self.showAdminNotice( response.data.message || 'Error deleting orders.', 'error' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-orders-progress' );
					self.showAdminNotice( 'Error deleting orders. Please try again.', 'error' );
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
						self.showAdminNotice( 'Anonymized ' + response.data.anonymized + ' order(s).', 'success' );
						self.currentOffset = 0;
						self.loadOrders();
					} else {
						self.showAdminNotice( response.data.message || 'Error anonymizing orders.', 'error' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-orders-progress' );
					self.showAdminNotice( 'Error anonymizing orders. Please try again.', 'error' );
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
				this.showAdminNotice( 'Please select at least one customer to export.', 'warning' );
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
						self.showAdminNotice( msg, 'success' );
						self.currentOffset = 0;
						self.loadCustomers();
					} else {
						self.showAdminNotice( response.data.message || 'Error deleting customers.', 'error' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-customers-progress' );
					self.showAdminNotice( 'Error deleting customers. Please try again.', 'error' );
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
						self.showAdminNotice( msg, 'success' );
						self.currentOffset = 0;
						self.loadCustomers();
					} else {
						self.showAdminNotice( response.data.message || 'Error anonymizing customers.', 'error' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-customers-progress' );
					self.showAdminNotice( 'Error anonymizing customers. Please try again.', 'error' );
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
					if ( response.success && response.data ) {
						$( '#jharudar-total-coupons' ).text( response.data.total );
						$( '#jharudar-expired-coupons' ).text( response.data.expired );
						$( '#jharudar-unused-coupons' ).text( response.data.unused );
						$( '#jharudar-limit-reached-coupons' ).text( response.data.limit_reached );
					}
				},
				error: function() {
					// Stats will remain at default values on network error.
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
				this.showAdminNotice( 'Please select at least one coupon to export.', 'warning' );
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
						self.showAdminNotice( 'Deleted ' + response.data.deleted + ' coupon(s).', 'success' );
						self.currentOffset = 0;
						self.loadCoupons();
						self.loadCouponStats();
					} else {
						self.showAdminNotice( response.data.message || 'Error deleting coupons.', 'error' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-coupons-progress' );
					self.showAdminNotice( 'Error deleting coupons. Please try again.', 'error' );
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
					if ( response.success && response.data ) {
						$( '#jharudar-total-categories' ).text( response.data.total_categories );
						$( '#jharudar-empty-categories' ).text( response.data.empty_categories );
						$( '#jharudar-total-tags' ).text( response.data.total_tags );
						$( '#jharudar-unused-tags' ).text( response.data.unused_tags );
						$( '#jharudar-total-attributes' ).text( response.data.total_attributes );
						$( '#jharudar-unused-attributes' ).text( response.data.unused_attributes );
					}
				},
				error: function() {
					// Stats will remain at default values on network error.
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
						self.showAdminNotice( msg, 'success' );
						self.currentOffset = 0;
						self.loadTaxonomy( type );
						self.loadTaxonomyStats();
					} else {
						self.showAdminNotice( response.data.message || 'Error deleting ' + type + '.', 'error' );
					}
				},
				error: function() {
					self.hideProgress( progressId );
					self.showAdminNotice( 'Error deleting ' + type + '. Please try again.', 'error' );
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
		 * Bind database module events.
		 */
		bindDatabaseEvents: function() {
			var self = this;

			if ( ! $( '.jharudar-database-page' ).length ) {
				return;
			}

			// Clean expired transients.
			$( '#jharudar-clean-transients' ).on( 'click', function() {
				self.showConfirmModal( {
					title: 'Clean Expired Transients',
					message: 'This will remove all expired transients from the database. This only clears cached data and is completely safe.',
					confirmText: 'Clean Transients'
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_clean_transients' );
				} );
			} );

			// Clean WooCommerce transients.
			$( '#jharudar-clean-wc-transients' ).on( 'click', function() {
				self.showConfirmModal( {
					title: 'Clear WooCommerce Transients',
					message: 'This will clear WooCommerce-specific transients used for caching. WooCommerce will regenerate them as needed.',
					confirmText: 'Clear WC Transients'
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_clean_wc_transients' );
				} );
			} );

			// Clean all transients.
			$( '#jharudar-clean-all-transients' ).on( 'click', function() {
				self.showConfirmModal( {
					title: 'Clean All Transients',
					message: 'This will remove <strong>all</strong> transients from the database, including active caches from WordPress core and other plugins. Everything will be regenerated on the next page load.',
					confirmText: 'Clean All',
					destructive: true
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_clean_all_transients' );
				} );
			} );

			// Clean orphaned meta.
			$( document ).on( 'click', '.jharudar-clean-orphaned-meta', function() {
				var type = $( this ).data( 'meta-type' );
				if ( ! type ) {
					return;
				}

				self.showConfirmModal( {
					title: 'Clean Orphaned Data',
					message: 'This will permanently remove orphaned ' + type + ' entries from the database. Please ensure you have a recent backup before continuing.',
					confirmText: 'Clean Data',
					destructive: true
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_clean_orphaned_meta', { meta_type: type } );
				} );
			} );

			// Regenerate customer lookup table.
			$( '#jharudar-regenerate-customer-lookup' ).on( 'click', function() {
				self.showConfirmModal( {
					title: 'Regenerate Customer Lookup Table',
					message: 'This will truncate and rebuild the WooCommerce customer lookup table. The operation may take a while on stores with many customers.',
					confirmText: 'Regenerate'
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_regenerate_customer_lookup' );
				} );
			} );

			// Repair order stats.
			$( '#jharudar-repair-order-stats' ).on( 'click', function() {
				self.showConfirmModal( {
					title: 'Repair Order Stats',
					message: 'This will truncate and rebuild the WooCommerce order stats table. The operation may take a while on stores with many orders.',
					confirmText: 'Repair Stats'
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_repair_order_stats' );
				} );
			} );

			// Clean expired sessions.
			$( '#jharudar-clean-sessions' ).on( 'click', function() {
				self.showConfirmModal( {
					title: 'Clean Expired Sessions',
					message: 'This will remove expired WooCommerce sessions. Active customer sessions will not be affected.',
					confirmText: 'Clean Sessions'
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_clean_sessions' );
				} );
			} );

			// Clean oEmbed caches.
			$( '#jharudar-clean-oembed' ).on( 'click', function() {
				self.showConfirmModal( {
					title: 'Clean oEmbed Caches',
					message: 'This will remove all cached oEmbed data from post meta. WordPress will re-fetch embed previews when needed.',
					confirmText: 'Clean oEmbed'
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_clean_oembed_caches' );
				} );
			} );

			// Clean duplicate meta.
			$( '#jharudar-clean-duplicate-meta' ).on( 'click', function() {
				self.showConfirmModal( {
					title: 'Clean Duplicate Meta',
					message: 'This will remove duplicate postmeta rows (same post_id, meta_key, and meta_value). The original entry is always kept. Please back up your database first.',
					confirmText: 'Clean Duplicates',
					destructive: true
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_clean_duplicate_meta' );
				} );
			} );

			// Load table analysis.
			$( '#jharudar-load-table-analysis' ).on( 'click', function() {
				self.loadTableAnalysis();
			} );

			// Delete orphaned tables.
			$( '#jharudar-delete-orphaned-tables' ).on( 'click', function() {
				var tables = [];
				$( '.jharudar-orphaned-table-checkbox:checked' ).each( function() {
					tables.push( $( this ).val() );
				} );

				if ( tables.length === 0 ) {
					self.showAdminNotice( 'Please select at least one table to delete.', 'warning' );
					return;
				}

				self.showConfirmModal( {
					title: 'Delete Orphaned Tables',
					message: 'This will permanently <strong>DROP</strong> ' + tables.length + ' table(s) from the database. This cannot be undone. Are you absolutely sure?',
					confirmText: 'Delete Tables',
					destructive: true
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_delete_orphaned_tables', { tables: tables } );
				} );
			} );

			// Optimize all tables.
			$( '#jharudar-optimize-all-tables' ).on( 'click', function() {
				self.showConfirmModal( {
					title: 'Optimize All Tables',
					message: 'This will run OPTIMIZE TABLE on all database tables with your prefix. This reclaims unused space and may take a few moments.',
					confirmText: 'Optimize Tables'
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_optimize_tables', { tables: [ '_all_' ] } );
				} );
			} );

			// Repair all tables.
			$( '#jharudar-repair-all-tables' ).on( 'click', function() {
				self.showConfirmModal( {
					title: 'Repair All Tables',
					message: 'This will run REPAIR TABLE on all database tables with your prefix. Use this if you suspect table corruption.',
					confirmText: 'Repair Tables'
				} ).then( function() {
					self.runDatabaseAction( 'jharudar_repair_tables', { tables: [ '_all_' ] } );
				} );
			} );

			// Toggle autoload.
			$( document ).on( 'click', '.jharudar-toggle-autoload', function() {
				var $btn = $( this );
				var optionName = $btn.data( 'option' );
				var newAutoload = $btn.data( 'autoload' ) === 'yes' ? 'no' : 'yes';

				$btn.prop( 'disabled', true );

				$.ajax( {
					url: jharudar_admin.ajax_url,
					type: 'POST',
					data: {
						action: 'jharudar_toggle_autoload',
						nonce: jharudar_admin.nonce,
						option_name: optionName,
						autoload: newAutoload
					},
					success: function( response ) {
						$btn.prop( 'disabled', false );
						if ( response.success ) {
							$btn.data( 'autoload', newAutoload );
							$btn.text( newAutoload );
							$btn.removeClass( 'jharudar-autoload-yes jharudar-autoload-no' )
								.addClass( 'jharudar-autoload-' + newAutoload );
							self.showAdminNotice( response.data.message, 'success' );
						} else {
							self.showAdminNotice( response.data.message || 'Failed to update autoload.', 'error' );
						}
					},
					error: function() {
						$btn.prop( 'disabled', false );
						self.showAdminNotice( 'Failed to update autoload.', 'error' );
					}
				} );
			} );
		},

		/**
		 * Load table analysis data.
		 */
		loadTableAnalysis: function() {
			var self = this;
			var $btn = $( '#jharudar-load-table-analysis' );
			var $results = $( '#jharudar-table-analysis-results' );

			$btn.prop( 'disabled', true ).text( 'Loading...' );

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_table_analysis',
					nonce: jharudar_admin.nonce
				},
				success: function( response ) {
					$btn.prop( 'disabled', false ).text( 'Load Analysis' );

					if ( ! response.success ) {
						self.showAdminNotice( response.data.message || 'Failed to load analysis.', 'error' );
						return;
					}

					var d = response.data;

					// Update summary stats.
					$( '#jharudar-db-total-size' ).text( self.formatBytes( d.total_size ) );
					$( '#jharudar-db-total-tables' ).text( d.tables.length );
					$( '#jharudar-db-overhead' ).text( self.formatBytes( d.overhead ) );
					$( '#jharudar-db-autoload-size' ).text( self.formatBytes( d.autoload_total ) );
					$( '#jharudar-db-orphaned-tables' ).text( d.orphaned_tables.length );

					// Render tables list.
					var html = '';
					$.each( d.tables, function( i, t ) {
						html += '<tr>' +
							'<td>' + self.escapeHtml( t.name ) + '</td>' +
							'<td>' + ( t.engine || '-' ) + '</td>' +
							'<td>' + t.rows.toLocaleString() + '</td>' +
							'<td>' + self.formatBytes( t.total_size ) + '</td>' +
							'<td>' + ( t.overhead > 0 ? self.formatBytes( t.overhead ) : '-' ) + '</td>' +
							'</tr>';
					} );
					$( '#jharudar-tables-list tbody' ).html( html );

					// Render large options list.
					var optHtml = '';
					$.each( d.large_options, function( i, opt ) {
						optHtml += '<tr>' +
							'<td>' + self.escapeHtml( opt.option_name ) + '</td>' +
							'<td>' + self.formatBytes( parseInt( opt.size, 10 ) ) + '</td>' +
							'<td><button type="button" class="button button-small jharudar-toggle-autoload jharudar-autoload-' + opt.autoload + '" data-option="' + self.escapeHtml( opt.option_name ) + '" data-autoload="' + opt.autoload + '">' + opt.autoload + '</button></td>' +
							'</tr>';
					} );
					$( '#jharudar-large-options-list tbody' ).html( optHtml );

					// Render orphaned tables.
					if ( d.orphaned_tables.length > 0 ) {
						var orphHtml = '<table class="wp-list-table widefat fixed striped"><thead><tr>' +
							'<th class="check-column"><input type="checkbox" id="jharudar-select-all-orphaned-tables" /></th>' +
							'<th>Table Name</th></tr></thead><tbody>';
						$.each( d.orphaned_tables, function( i, tableName ) {
							orphHtml += '<tr>' +
								'<td><input type="checkbox" class="jharudar-orphaned-table-checkbox" value="' + self.escapeHtml( tableName ) + '" /></td>' +
								'<td>' + self.escapeHtml( tableName ) + '</td>' +
								'</tr>';
						} );
						orphHtml += '</tbody></table>';
						$( '#jharudar-orphaned-tables-list' ).html( orphHtml );
						$( '#jharudar-orphaned-tables-section' ).removeClass( 'jharudar-hidden' );
						$( '#jharudar-delete-orphaned-tables' ).prop( 'disabled', true );

						// Select all orphaned tables (rebind on the new elements, not document).
						$( '#jharudar-select-all-orphaned-tables' ).on( 'change', function() {
							var checked = $( this ).is( ':checked' );
							$( '.jharudar-orphaned-table-checkbox' ).prop( 'checked', checked );
							$( '#jharudar-delete-orphaned-tables' ).prop( 'disabled', ! checked );
						} );
						// Use off/on to prevent stacking handlers from repeated loads.
						$( document ).off( 'change.jharudarOrphanedCheckbox' ).on( 'change.jharudarOrphanedCheckbox', '.jharudar-orphaned-table-checkbox', function() {
							$( '#jharudar-delete-orphaned-tables' ).prop( 'disabled', $( '.jharudar-orphaned-table-checkbox:checked' ).length === 0 );
						} );
					} else {
						$( '#jharudar-orphaned-tables-section' ).addClass( 'jharudar-hidden' );
					}

					$results.removeClass( 'jharudar-hidden' );
				},
				error: function() {
					$btn.prop( 'disabled', false ).text( 'Load Analysis' );
					self.showAdminNotice( 'Failed to load table analysis.', 'error' );
				}
			} );
		},

		/**
		 * Format bytes to human readable string.
		 *
		 * @param {number} bytes The byte count.
		 * @return {string} Formatted string.
		 */
		formatBytes: function( bytes ) {
			if ( ! bytes || bytes === 0 ) {
				return '0 B';
			}
			var units = [ 'B', 'KB', 'MB', 'GB' ];
			var i = Math.floor( Math.log( bytes ) / Math.log( 1024 ) );
			return ( bytes / Math.pow( 1024, i ) ).toFixed( 1 ) + ' ' + units[ i ];
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
					if ( response.success && response.data ) {
						$( '#jharudar-total-tax-rates' ).text( response.data.total );
						$( '#jharudar-tax-countries' ).text( response.data.countries );
						$( '#jharudar-tax-classes' ).text( response.data.tax_classes );
					}
				},
				error: function() {
					// Stats will remain at default values on network error.
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
					if ( response.success && response.data ) {
						$( '#jharudar-total-zones' ).text( response.data.total_zones );
						$( '#jharudar-empty-zones' ).text( response.data.empty_zones );
						$( '#jharudar-total-shipping-classes' ).text( response.data.total_classes );
						$( '#jharudar-unused-shipping-classes' ).text( response.data.unused_classes );
					}
				},
				error: function() {
					// Stats will remain at default values on network error.
				}
			} );
		},

		/**
		 * Load database statistics.
		 */
		loadDatabaseStats: function() {
			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_get_database_stats',
					nonce: jharudar_admin.nonce
				},
				success: function( response ) {
					if ( response.success && response.data ) {
						if ( response.data.transients_total !== undefined ) {
							$( '#jharudar-transients-total' ).text( response.data.transients_total.toLocaleString() );
						}
						if ( response.data.expired_transients !== undefined ) {
							$( '#jharudar-transients-expired' ).text( response.data.expired_transients.toLocaleString() );
						}
						if ( response.data.wc_transients_total !== undefined ) {
							$( '#jharudar-transients-wc-total' ).text( response.data.wc_transients_total.toLocaleString() );
						}

						// Sessions & oEmbed stats.
						if ( response.data.expired_sessions !== undefined ) {
							$( '#jharudar-expired-sessions' ).text( response.data.expired_sessions.toLocaleString() );
						}
						if ( response.data.oembed_caches !== undefined ) {
							$( '#jharudar-oembed-caches' ).text( response.data.oembed_caches.toLocaleString() );
						}

						// Orphaned meta stats.
						if ( response.data.orphaned_postmeta !== undefined ) {
							$( '#jharudar-orphaned-postmeta-count' ).text( response.data.orphaned_postmeta.toLocaleString() );
						}
						if ( response.data.orphaned_usermeta !== undefined ) {
							$( '#jharudar-orphaned-usermeta-count' ).text( response.data.orphaned_usermeta.toLocaleString() );
						}
						if ( response.data.orphaned_termmeta !== undefined ) {
							$( '#jharudar-orphaned-termmeta-count' ).text( response.data.orphaned_termmeta.toLocaleString() );
						}
						if ( response.data.orphaned_commentmeta !== undefined ) {
							$( '#jharudar-orphaned-commentmeta-count' ).text( response.data.orphaned_commentmeta.toLocaleString() );
						}
						if ( response.data.orphaned_order_itemmeta !== undefined ) {
							$( '#jharudar-orphaned-order-itemmeta-count' ).text( response.data.orphaned_order_itemmeta.toLocaleString() );
						}
						if ( response.data.orphaned_relationships !== undefined ) {
							$( '#jharudar-orphaned-relationships-count' ).text( response.data.orphaned_relationships.toLocaleString() );
						}
						if ( response.data.duplicate_postmeta !== undefined ) {
							$( '#jharudar-duplicate-postmeta-count' ).text( response.data.duplicate_postmeta.toLocaleString() );
						}
					}
				},
				error: function() {
					// Stats will remain at default values on network error.
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
				this.showAdminNotice( 'Please select at least one tax rate to export.', 'warning' );
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
						self.showAdminNotice( 'Deleted ' + response.data.deleted + ' tax rate(s).', 'success' );
						self.currentOffset = 0;
						self.loadTaxRates();
						self.loadStoreStats();
					} else {
						self.showAdminNotice( response.data.message || 'Error deleting tax rates.', 'error' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-tax-rates-progress' );
					self.showAdminNotice( 'Error deleting tax rates. Please try again.', 'error' );
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
						self.showAdminNotice( msg, 'success' );
						self.currentOffset = 0;
						self.loadShippingZones();
						self.loadStoreStats();
					} else {
						self.showAdminNotice( response.data.message || 'Error deleting shipping zones.', 'error' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-zones-progress' );
					self.showAdminNotice( 'Error deleting shipping zones. Please try again.', 'error' );
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
						self.showAdminNotice( 'Deleted ' + response.data.deleted + ' shipping class(es).', 'success' );
						self.currentOffset = 0;
						self.loadShippingClasses();
						self.loadStoreStats();
					} else {
						self.showAdminNotice( response.data.message || 'Error deleting shipping classes.', 'error' );
					}
				},
				error: function() {
					self.hideProgress( 'jharudar-shipping-classes-progress' );
					self.showAdminNotice( 'Error deleting shipping classes. Please try again.', 'error' );
				}
			} );
		},

		/**
		 * Run a generic database action with progress feedback.
		 *
		 * @param {string} action The AJAX action name.
		 * @param {Object} extraData Extra data to send with the request.
		 */
		runDatabaseAction: function( action, extraData ) {
			var self = this;
			var $progressWrapper = $( '#jharudar-database-progress' );
			var $progressFill = $progressWrapper.find( '.jharudar-progress-fill' );
			var $processed = $progressWrapper.find( '.processed' );

			if ( ! $progressWrapper.length ) {
				// Fallback if progress element is not present.
				$progressWrapper = null;
			}

			if ( $progressWrapper ) {
				$progressWrapper.addClass( 'active' );
				$progressFill.css( 'width', '35%' );
				$processed.text( '0' );
			}

			var data = {
				action: action,
				nonce: jharudar_admin.nonce
			};

			if ( extraData ) {
				data = $.extend( data, extraData );
			}

			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: data,
				success: function( response ) {
					if ( $progressWrapper ) {
						$progressFill.css( 'width', '100%' );
					}

					if ( response.success ) {
						if ( response.data && response.data.deleted !== undefined && $processed.length ) {
							$processed.text( response.data.deleted );
						}

						if ( response.data && response.data.message ) {
							self.showAdminNotice( response.data.message, 'success' );
						}

						// Refresh stats after any database action.
						if ( $( '.jharudar-database-page' ).length ) {
							self.loadDatabaseStats();
						}
					} else if ( response.data && response.data.message ) {
						self.showAdminNotice( response.data.message, 'error' );
					} else {
						self.showAdminNotice( 'The operation could not be completed. Please try again.', 'error' );
					}

					if ( $progressWrapper ) {
						setTimeout( function() {
							$progressWrapper.removeClass( 'active' );
							$progressFill.css( 'width', '0' );
						}, 800 );
					}
				},
				error: function() {
					if ( $progressWrapper ) {
						$progressWrapper.removeClass( 'active' );
						$progressFill.css( 'width', '0' );
						$processed.text( '0' );
					}

					self.showAdminNotice( 'The operation failed due to a network error. Please try again.', 'error' );
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
						self.showAdminNotice( response.data.message || 'Export failed.', 'error' );
					}
				},
				error: function() {
					self.showAdminNotice( 'Export failed. Please try again.', 'error' );
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

	// Initialize on document ready (only when localized data is available).
	$( document ).ready( function() {
		if ( typeof jharudar_admin === 'undefined' ) {
			return;
		}
		JharudarAdmin.init();
	} );

} )( jQuery );
