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
		 * Initialize.
		 */
		init: function() {
			this.initSelectWoo();
			this.bindEvents();
		},

		/**
		 * Initialize SelectWoo on select elements.
		 */
		initSelectWoo: function() {
			$( '.jharudar-select2' ).selectWoo( {
				width: '100%',
				allowClear: true,
				placeholder: function() {
					return $( this ).data( 'placeholder' ) || '';
				}
			} );
		},

		/**
		 * Bind events.
		 */
		bindEvents: function() {
			var self = this;

			// Select all checkbox.
			$( document ).on( 'change', '.jharudar-select-all', function() {
				var checked = $( this ).is( ':checked' );
				$( '.jharudar-item-checkbox' ).prop( 'checked', checked );
				self.updateBulkActionState();
			} );

			// Individual checkbox change.
			$( document ).on( 'change', '.jharudar-item-checkbox', function() {
				self.updateBulkActionState();
				self.updateSelectAllState();
			} );

			// Bulk action button.
			$( document ).on( 'click', '.jharudar-bulk-action-btn', function( e ) {
				e.preventDefault();
				var action = $( '.jharudar-bulk-action-select' ).val();
				if ( ! action ) {
					return;
				}
				self.handleBulkAction( action );
			} );

			// Delete confirmation modal.
			$( document ).on( 'click', '.jharudar-modal-cancel', function( e ) {
				e.preventDefault();
				self.closeModal();
			} );

			$( document ).on( 'click', '.jharudar-modal-overlay', function( e ) {
				if ( $( e.target ).hasClass( 'jharudar-modal-overlay' ) ) {
					self.closeModal();
				}
			} );

			// Confirm delete button.
			$( document ).on( 'click', '.jharudar-modal-confirm', function( e ) {
				e.preventDefault();
				self.processConfirmedAction();
			} );

			// Delete confirmation input.
			$( document ).on( 'input', '.jharudar-delete-confirm-input', function() {
				var value = $( this ).val().trim().toUpperCase();
				var confirmBtn = $( '.jharudar-modal-confirm' );
				if ( value === 'DELETE' ) {
					confirmBtn.prop( 'disabled', false );
				} else {
					confirmBtn.prop( 'disabled', true );
				}
			} );

			// Escape key to close modal.
			$( document ).on( 'keyup', function( e ) {
				if ( e.key === 'Escape' ) {
					self.closeModal();
				}
			} );

			// Filter form submission.
			$( document ).on( 'change', '.jharudar-filter-select', function() {
				$( '.jharudar-filter-form' ).submit();
			} );
		},

		/**
		 * Update bulk action button state.
		 */
		updateBulkActionState: function() {
			var checkedCount = $( '.jharudar-item-checkbox:checked' ).length;
			$( '.jharudar-selected-count' ).text( checkedCount );
			
			if ( checkedCount > 0 ) {
				$( '.jharudar-bulk-action-btn' ).prop( 'disabled', false );
			} else {
				$( '.jharudar-bulk-action-btn' ).prop( 'disabled', true );
			}
		},

		/**
		 * Update select all checkbox state.
		 */
		updateSelectAllState: function() {
			var totalCheckboxes = $( '.jharudar-item-checkbox' ).length;
			var checkedCheckboxes = $( '.jharudar-item-checkbox:checked' ).length;
			
			if ( checkedCheckboxes === 0 ) {
				$( '.jharudar-select-all' ).prop( 'checked', false ).prop( 'indeterminate', false );
			} else if ( checkedCheckboxes === totalCheckboxes ) {
				$( '.jharudar-select-all' ).prop( 'checked', true ).prop( 'indeterminate', false );
			} else {
				$( '.jharudar-select-all' ).prop( 'checked', false ).prop( 'indeterminate', true );
			}
		},

		/**
		 * Handle bulk action.
		 *
		 * @param {string} action The action to perform.
		 */
		handleBulkAction: function( action ) {
			var self = this;
			var selectedIds = [];

			$( '.jharudar-item-checkbox:checked' ).each( function() {
				selectedIds.push( $( this ).val() );
			} );

			if ( selectedIds.length === 0 ) {
				alert( jharudar_admin.i18n.no_items_selected );
				return;
			}

			// Store action data for processing.
			this.pendingAction = {
				action: action,
				ids: selectedIds
			};

			// Check if confirmation is required.
			if ( action === 'delete' && jharudar_admin.require_confirmation ) {
				this.showDeleteConfirmationModal( selectedIds.length );
			} else {
				this.processConfirmedAction();
			}
		},

		/**
		 * Show delete confirmation modal.
		 *
		 * @param {number} count Number of items to delete.
		 */
		showDeleteConfirmationModal: function( count ) {
			var modalHtml = '<div class="jharudar-modal-overlay active">' +
				'<div class="jharudar-modal">' +
				'<h3>' + jharudar_admin.i18n.confirm_delete + '</h3>' +
				'<p>' + jharudar_admin.i18n.type_delete + '</p>' +
				'<div class="jharudar-modal-input">' +
				'<input type="text" class="jharudar-delete-confirm-input regular-text" autocomplete="off" />' +
				'</div>' +
				'<div class="jharudar-modal-actions">' +
				'<button class="button jharudar-modal-cancel">' + 'Cancel' + '</button>' +
				'<button class="button button-primary jharudar-modal-confirm" disabled>' + 'Delete' + '</button>' +
				'</div>' +
				'</div>' +
				'</div>';

			$( 'body' ).append( modalHtml );
			$( '.jharudar-delete-confirm-input' ).focus();
		},

		/**
		 * Close modal.
		 */
		closeModal: function() {
			$( '.jharudar-modal-overlay' ).remove();
			this.pendingAction = null;
		},

		/**
		 * Process confirmed action.
		 */
		processConfirmedAction: function() {
			var self = this;

			if ( ! this.pendingAction ) {
				return;
			}

			this.closeModal();
			this.showProgress();

			// Make AJAX request.
			$.ajax( {
				url: jharudar_admin.ajax_url,
				type: 'POST',
				data: {
					action: 'jharudar_bulk_action',
					nonce: jharudar_admin.nonce,
					bulk_action: this.pendingAction.action,
					ids: this.pendingAction.ids
				},
				success: function( response ) {
					if ( response.success ) {
						self.updateProgress( 100 );
						setTimeout( function() {
							location.reload();
						}, 500 );
					} else {
						self.hideProgress();
						alert( response.data.message || jharudar_admin.i18n.error );
					}
				},
				error: function() {
					self.hideProgress();
					alert( jharudar_admin.i18n.error );
				}
			} );
		},

		/**
		 * Show progress bar.
		 */
		showProgress: function() {
			if ( $( '.jharudar-progress-wrapper' ).length === 0 ) {
				var progressHtml = '<div class="jharudar-progress-wrapper active">' +
					'<div class="jharudar-progress-bar">' +
					'<div class="jharudar-progress-fill"></div>' +
					'</div>' +
					'<div class="jharudar-progress-text">' + jharudar_admin.i18n.processing + '</div>' +
					'</div>';
				$( '.jharudar-actions-bar' ).after( progressHtml );
			} else {
				$( '.jharudar-progress-wrapper' ).addClass( 'active' );
			}
			this.updateProgress( 10 );
		},

		/**
		 * Update progress bar.
		 *
		 * @param {number} percent Progress percentage.
		 */
		updateProgress: function( percent ) {
			$( '.jharudar-progress-fill' ).css( 'width', percent + '%' );
			if ( percent >= 100 ) {
				$( '.jharudar-progress-text' ).text( jharudar_admin.i18n.complete );
			}
		},

		/**
		 * Hide progress bar.
		 */
		hideProgress: function() {
			$( '.jharudar-progress-wrapper' ).removeClass( 'active' );
		}
	};

	// Initialize on document ready.
	$( document ).ready( function() {
		JharudarAdmin.init();
	} );

} )( jQuery );
